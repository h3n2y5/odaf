<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Designer Workflow Berjenjang (N-Level Approval).
 * Disederhanakan untuk Studio:
 * Level 1 -> Level 2 -> Level 3 -> dst.
 */
#[Layout('layouts.odaf')]
final class WorkflowDesigner extends Component
{
    use NormalizesRows;

    public string $datasetId;

    /** @var array<string, mixed> */
    public array $dataset = [];

    /** @var array<string, mixed>|null */
    public ?array $workflow = null;

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /**
     * Konfigurasi level approval:
     * [
     *   ['id' => '1', 'name' => 'Approval L1', 'role_id' => '...', 'pending_state' => 'Pending L1'],
     * ]
     */
    public array $levels = [];

    public function mount(StudioAccess $access, string $datasetId): void
    {
        $access->ensureAdmin();

        $this->datasetId = $datasetId;
        $this->loadDataset();
        $this->loadRoles();
        $this->loadWorkflow();
    }

    public function render()
    {
        return view('livewire.studio.designer.workflow-designer');
    }

    private function loadDataset(): void
    {
        $row = DB::selectOne("
            SELECT RAWTOHEX(D.OBJECT_ID) AS ID, D.OBJECT_CODE, D.OBJECT_NAME,
                   RAWTOHEX(P.APPLICATION_ID) AS APPLICATION_ID
            FROM DS_DATASET D
            LEFT JOIN UI_PAGE P ON P.DATASET_ID = D.OBJECT_ID
            WHERE D.OBJECT_ID = HEXTORAW(?)
            FETCH FIRST 1 ROW ONLY
        ", [$this->datasetId]);

        if (!$row) {
            abort(404, 'Dataset not found');
        }

        $this->dataset = $this->normalizeRow($row);
    }

    private function loadRoles(): void
    {
        $rows = DB::select("SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME FROM SEC_ROLE ORDER BY OBJECT_CODE");
        $this->roles = $this->normalizeRows($rows);
    }

    private function loadWorkflow(): void
    {
        $wf = DB::selectOne("
            SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME, STATUS
            FROM WF_WORKFLOW
            WHERE DATASET_ID = HEXTORAW(?)
        ", [$this->datasetId]);

        if ($wf) {
            $this->workflow = $this->normalizeRow($wf);
            $this->loadLevels();
        }
    }

    private function loadLevels(): void
    {
        // FSM: START -> PENDING_L1 -> PENDING_L2 -> END
        // Transisinya yang menentukan Required Role ID
        if (!$this->workflow) {
            return;
        }

        $activities = DB::select("
            SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME, ACTIVITY_TYPE, DISPLAY_ORDER
            FROM WF_ACTIVITY
            WHERE WORKFLOW_ID = HEXTORAW(?) AND ACTIVITY_TYPE = 'APPROVAL'
            ORDER BY DISPLAY_ORDER ASC
        ", [$this->workflow['ID']]);

        $this->levels = [];
        foreach ($activities as $act) {
            $act = $this->normalizeRow($act);
            
            // Cari transisi ACTION_CODE = 'APPROVE' yang berasal dari aktivitas ini,
            // untuk mendapatkan ROLE apa yang berwenang (REQUIRED_ROLE_ID).
            // Kalau tidak ada APPROVE, cari SUBMIT yang menuju ke aktivitas ini (untuk kasus level 1).
            // Atau lebih mudah, kita lihat transition apa saja.
            
            // Untuk desain N-Level linier kita:
            // State: PENDING_L1 (Activity)
            // Transisi dari START ke PENDING_L1 (Submit)
            // Transisi dari PENDING_L1 ke PENDING_L2 (Approve) -> required_role_id
            
            $trans = DB::selectOne("
                SELECT RAWTOHEX(REQUIRED_ROLE_ID) AS ROLE_ID
                FROM WF_TRANSITION
                WHERE FROM_ACTIVITY_ID = HEXTORAW(?) AND ACTION_CODE = 'APPROVE'
            ", [$act['ID']]);

            $roleId = $trans ? $this->normalizeRow($trans)['ROLE_ID'] : null;

            $this->levels[] = [
                'id' => uniqid(),
                'name' => $act['OBJECT_NAME'],
                'pending_state' => $act['OBJECT_CODE'],
                'role_id' => $roleId,
                'activity_id' => $act['ID'],
            ];
        }
    }

    public function enableWorkflow(): void
    {
        if ($this->workflow) {
            return;
        }

        DB::transaction(function () {
            $wfId = (string) \Illuminate\Support\Str::uuid();
            $wfIdRaw = str_replace('-', '', $wfId);
            $code = $this->dataset['OBJECT_CODE'] . '_WF';

            DB::insert("
                INSERT INTO WF_WORKFLOW (OBJECT_ID, OBJECT_CODE, OBJECT_NAME, DATASET_ID, STATUS)
                VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), 'PUBLISHED')
            ", [
                $wfIdRaw,
                $code,
                'Workflow ' . $this->dataset['OBJECT_NAME'],
                $this->dataset['ID'],
            ]);

            // Buat START state
            DB::insert("
                INSERT INTO WF_ACTIVITY (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, ACTIVITY_TYPE, INITIAL_FLAG)
                VALUES (HEXTORAW(?), 'DRAFT', 'Draft', 'START', 1)
            ", [$wfIdRaw]);

            // Buat END state
            DB::insert("
                INSERT INTO WF_ACTIVITY (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, ACTIVITY_TYPE, FINAL_FLAG)
                VALUES (HEXTORAW(?), 'COMPLETED', 'Selesai', 'END', 1)
            ", [$wfIdRaw]);
        });

        $this->loadWorkflow();
    }

    public function addLevel(): void
    {
        $num = count($this->levels) + 1;
        $this->levels[] = [
            'id' => uniqid(),
            'name' => "Approval Level {$num}",
            'pending_state' => "PENDING_L{$num}",
            'role_id' => '',
        ];
    }

    public function removeLevel(int $index): void
    {
        unset($this->levels[$index]);
        $this->levels = array_values($this->levels);
    }

    public function saveWorkflow(): void
    {
        if (!$this->workflow) {
            return;
        }

        // Validasi
        foreach ($this->levels as $idx => $lvl) {
            if (empty($lvl['name']) || empty($lvl['pending_state']) || empty($lvl['role_id'])) {
                session()->flash('error', "Level " . ($idx + 1) . " tidak lengkap.");
                return;
            }
        }

        $wfIdRaw = $this->workflow['ID'];

        DB::transaction(function () use ($wfIdRaw) {
            // Hapus semua activity APPROVAL dan semua transisi
            DB::delete("DELETE FROM WF_TRANSITION WHERE WORKFLOW_ID = HEXTORAW(?)", [$wfIdRaw]);
            DB::delete("DELETE FROM WF_ACTIVITY WHERE WORKFLOW_ID = HEXTORAW(?) AND ACTIVITY_TYPE = 'APPROVAL'", [$wfIdRaw]);

            $startAct = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS ID FROM WF_ACTIVITY WHERE WORKFLOW_ID = HEXTORAW(?) AND ACTIVITY_TYPE = 'START'", [$wfIdRaw]);
            $endAct = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS ID FROM WF_ACTIVITY WHERE WORKFLOW_ID = HEXTORAW(?) AND ACTIVITY_TYPE = 'END'", [$wfIdRaw]);
            $startId = $this->normalizeRow($startAct)['ID'];
            $endId = $this->normalizeRow($endAct)['ID'];

            $prevId = $startId;
            $prevStatus = 'DRAFT';

            foreach ($this->levels as $idx => $lvl) {
                $actId = (string) \Illuminate\Support\Str::uuid();
                $actIdRaw = str_replace('-', '', $actId);
                $stateCode = strtoupper(\Illuminate\Support\Str::slug($lvl['pending_state'], '_'));
                
                DB::insert("
                    INSERT INTO WF_ACTIVITY (OBJECT_ID, WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, ACTIVITY_TYPE, DISPLAY_ORDER)
                    VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, 'APPROVAL', ?)
                ", [$actIdRaw, $wfIdRaw, $stateCode, $lvl['name'], $idx]);

                // Transisi dari prev ke current
                $actionCode = $idx === 0 ? 'SUBMIT' : 'APPROVE';
                $actionName = $idx === 0 ? 'Submit' : 'Approve';

                if ($idx === 0) {
                    // Level pertama: SUBMIT tanpa role requirement
                    DB::insert("
                        INSERT INTO WF_TRANSITION (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, FROM_ACTIVITY_ID, TO_ACTIVITY_ID, ACTION_CODE, REQUIRED_ROLE_ID, DISPLAY_ORDER)
                        VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), HEXTORAW(?), ?, NULL, ?)
                    ", [
                        $wfIdRaw,
                        $wfIdRaw . '_' . $prevStatus . '_' . $stateCode,
                        $actionName,
                        $prevId,
                        $actIdRaw,
                        $actionCode,
                        $idx * 10,
                    ]);
                } else {
                    // Level berikutnya: APPROVE, role level sebelumnya yang berwenang
                    DB::insert("
                        INSERT INTO WF_TRANSITION (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, FROM_ACTIVITY_ID, TO_ACTIVITY_ID, ACTION_CODE, REQUIRED_ROLE_ID, DISPLAY_ORDER)
                        VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), HEXTORAW(?), ?, HEXTORAW(?), ?)
                    ", [
                        $wfIdRaw,
                        $wfIdRaw . '_' . $prevStatus . '_' . $stateCode,
                        $actionName,
                        $prevId,
                        $actIdRaw,
                        $actionCode,
                        $this->levels[$idx - 1]['role_id'],
                        $idx * 10,
                    ]);
                }

                // Reject Transisi: Dari current balik ke DRAFT
                DB::insert("
                    INSERT INTO WF_TRANSITION (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, FROM_ACTIVITY_ID, TO_ACTIVITY_ID, ACTION_CODE, REQUIRED_ROLE_ID, DISPLAY_ORDER)
                    VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), HEXTORAW(?), 'REJECT', HEXTORAW(?), ?)
                ", [
                    $wfIdRaw,
                    $wfIdRaw . '_' . $stateCode . '_REJECT',
                    'Reject',
                    $actIdRaw,
                    $startId,
                    $lvl['role_id'],
                    ($idx * 10) + 1
                ]);

                $prevId = $actIdRaw;
                $prevStatus = $stateCode;
            }

            // Transisi terakhir ke END
            if (count($this->levels) > 0) {
                $lastRole = $this->levels[count($this->levels) - 1]['role_id'];
                DB::insert("
                    INSERT INTO WF_TRANSITION (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, FROM_ACTIVITY_ID, TO_ACTIVITY_ID, ACTION_CODE, REQUIRED_ROLE_ID, DISPLAY_ORDER)
                    VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), HEXTORAW(?), 'APPROVE', HEXTORAW(?), 999)
                ", [
                    $wfIdRaw,
                    $wfIdRaw . '_' . $prevStatus . '_END',
                    'Approve (Final)',
                    $prevId,
                    $endId,
                    $lastRole
                ]);
            } else {
                // Langsung START ke END
                DB::insert("
                    INSERT INTO WF_TRANSITION (WORKFLOW_ID, OBJECT_CODE, OBJECT_NAME, FROM_ACTIVITY_ID, TO_ACTIVITY_ID, ACTION_CODE, DISPLAY_ORDER)
                    VALUES (HEXTORAW(?), ?, ?, HEXTORAW(?), HEXTORAW(?), 'SUBMIT', 999)
                ", [
                    $wfIdRaw,
                    $wfIdRaw . '_START_END',
                    'Submit',
                    $startId,
                    $endId
                ]);
            }
        });

        session()->flash('success', 'Konfigurasi Workflow berhasil disimpan.');
        $this->loadLevels(); // Refresh id
    }
}
