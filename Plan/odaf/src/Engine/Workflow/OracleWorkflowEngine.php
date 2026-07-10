<?php

declare(strict_types=1);

namespace Odaf\Engine\Workflow;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Engine\Notification\Contracts\NotificationEngineInterface;
use Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface;
use Odaf\Engine\Workflow\Contracts\WorkflowException;
use Odaf\Engine\Workflow\Contracts\WorkflowStateInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\UnifiedRuntimeKernel;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use Throwable;

/**
 * BB-06 Workflow Engine — implementasi Oracle (state machine approval).
 *
 * Mengeksekusi graph workflow terkompilasi (dari package). Instance dan riwayat
 * disimpan pada RT_WORKFLOW_INSTANCE / RT_WORKFLOW_HISTORY. Engine tidak memuat
 * logika bisnis (WFE-002) dan tidak pernah membaca metadata design-time.
 */
final class OracleWorkflowEngine implements WorkflowEngineInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly UnifiedRuntimeKernel $kernel,
        private readonly IdentityGeneratorInterface $identity,
        private readonly AuditEngineInterface $audit,
        private readonly NotificationEngineInterface $notifications,
    ) {}

    public function hasWorkflow(ExecutionContextInterface $context, string $datasetId): bool
    {
        return $this->kernel->workflowForDataset($context->applicationId(), $datasetId) !== null;
    }

    public function start(ExecutionContextInterface $context, string $datasetId, string $entityKey): ?WorkflowStateInterface
    {
        $wf = $this->workflowOrNull($context, $datasetId);
        if ($wf === null) {
            return null;
        }

        $entityKey = strtoupper($entityKey);
        $existing = $this->instanceRow($wf['id'], $entityKey);
        if ($existing !== null) {
            return $this->stateFromInstance($wf, $existing);
        }

        $initialId = (string) ($wf['initialActivityId'] ?? '');
        if ($initialId === '' || ! isset($wf['activities'][$initialId])) {
            throw new WorkflowException("Workflow {$wf['code']} tidak memiliki activity awal.");
        }
        $initial = $wf['activities'][$initialId];

        $instanceId = $this->identity->generate();
        $this->connection->insert(
            'INSERT INTO RT_WORKFLOW_INSTANCE
                (OBJECT_ID, WORKFLOW_ID, DATASET_CODE, ENTITY_KEY, CURRENT_ACTIVITY_ID, CURRENT_STATE_CODE, INSTANCE_STATUS, STARTED_BY)
             VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, HEXTORAW(?), ?, ?, ?)',
            [
                strtoupper($instanceId),
                strtoupper((string) $wf['id']),
                $this->datasetCode($context, $datasetId),
                $entityKey,
                strtoupper($initialId),
                (string) $initial['code'],
                $initial['final'] ? 'COMPLETED' : 'RUNNING',
                $this->rawUser($context),
            ],
        );

        $this->writeHistory($instanceId, null, 'START', null, (string) $initial['code'], $context, null);
        $this->mirrorState($context, $wf, $entityKey, (string) $initial['code']);
        $this->audit->record($context, 'WORKFLOW_START', $entityKey, [], ['state' => $initial['code']]);

        return $this->stateFromActivity($initial);
    }

    public function currentState(ExecutionContextInterface $context, string $datasetId, string $entityKey): ?WorkflowStateInterface
    {
        $wf = $this->workflowOrNull($context, $datasetId);
        if ($wf === null) {
            return null;
        }
        $row = $this->instanceRow($wf['id'], strtoupper($entityKey));

        return $row === null ? null : $this->stateFromInstance($wf, $row);
    }

    public function availableTransitions(ExecutionContextInterface $context, string $datasetId, string $entityKey): array
    {
        $wf = $this->workflowOrNull($context, $datasetId);
        if ($wf === null) {
            return [];
        }
        $row = $this->instanceRow($wf['id'], strtoupper($entityKey));
        if ($row === null || (string) $row['INSTANCE_STATUS'] !== 'RUNNING') {
            return [];
        }

        $currentActivityId = strtoupper((string) $row['CURRENT_ACTIVITY_ID']);
        $out = [];
        foreach ($wf['transitions'] as $tr) {
            if (strtoupper((string) $tr['fromId']) !== $currentActivityId) {
                continue;
            }
            if (! $this->roleAllowed($context, $tr['requiredRoleId'] ?? null)) {
                continue;
            }
            $target = $wf['activities'][$tr['toId']] ?? null;
            $out[] = [
                'action' => (string) $tr['action'],
                'label' => (string) $tr['label'],
                'toStateCode' => $target !== null ? (string) $target['code'] : null,
                'toStateName' => $target !== null ? (string) $target['name'] : null,
            ];
        }

        return $out;
    }

    public function perform(
        ExecutionContextInterface $context,
        string $datasetId,
        string $entityKey,
        string $actionCode,
        ?string $comment = null,
    ): WorkflowStateInterface {
        $wf = $this->workflowOrNull($context, $datasetId);
        if ($wf === null) {
            throw new WorkflowException("Dataset {$datasetId} tidak diatur oleh workflow.");
        }

        $entityKey = strtoupper($entityKey);
        $row = $this->instanceRow($wf['id'], $entityKey);
        if ($row === null) {
            throw new WorkflowException('Instance workflow tidak ditemukan.');
        }
        if ((string) $row['INSTANCE_STATUS'] !== 'RUNNING') {
            throw new WorkflowException('Instance workflow sudah selesai.');
        }

        $currentActivityId = strtoupper((string) $row['CURRENT_ACTIVITY_ID']);
        $fromCode = (string) $row['CURRENT_STATE_CODE'];

        $transition = null;
        foreach ($wf['transitions'] as $tr) {
            if (strtoupper((string) $tr['fromId']) === $currentActivityId
                && strtoupper((string) $tr['action']) === strtoupper($actionCode)) {
                $transition = $tr;
                break;
            }
        }
        if ($transition === null) {
            throw new WorkflowException("Aksi '{$actionCode}' tidak tersedia dari state '{$fromCode}'.");
        }
        if (! $this->roleAllowed($context, $transition['requiredRoleId'] ?? null)) {
            throw new WorkflowException("Anda tidak memiliki hak untuk aksi '{$actionCode}'.");
        }

        $target = $wf['activities'][$transition['toId']] ?? null;
        if ($target === null) {
            throw new WorkflowException('Activity tujuan tidak dikenal pada package.');
        }

        $newStatus = $target['final'] ? 'COMPLETED' : 'RUNNING';

        $this->connection->update(
            'UPDATE RT_WORKFLOW_INSTANCE
             SET CURRENT_ACTIVITY_ID = HEXTORAW(?), CURRENT_STATE_CODE = ?, INSTANCE_STATUS = ?, UPDATED_AT = SYSTIMESTAMP
             WHERE OBJECT_ID = HEXTORAW(?)',
            [
                strtoupper((string) $target['id']),
                (string) $target['code'],
                $newStatus,
                strtoupper((string) $row['OBJECT_ID']),
            ],
        );

        $this->writeHistory(
            (string) $row['OBJECT_ID'],
            (string) $transition['id'],
            (string) $transition['action'],
            $fromCode,
            (string) $target['code'],
            $context,
            $comment,
        );
        $this->mirrorState($context, $wf, $entityKey, (string) $target['code']);
        $this->audit->record(
            $context,
            'WORKFLOW_'.strtoupper((string) $transition['action']),
            $entityKey,
            ['state' => $fromCode],
            ['state' => $target['code']],
        );

        // Publikasikan event bisnis untuk Notification Engine (BB-10).
        $this->publishEvent($context, $wf, $entityKey, (string) $transition['action'], (string) $target['code'], $fromCode, (string) ($row['STARTED_BY'] ?? ''));

        return $this->stateFromActivity($target);
    }

    public function history(ExecutionContextInterface $context, string $datasetId, string $entityKey): array
    {
        $wf = $this->workflowOrNull($context, $datasetId);
        if ($wf === null) {
            return [];
        }
        $instance = $this->instanceRow($wf['id'], strtoupper($entityKey));
        if ($instance === null) {
            return [];
        }

        $rows = $this->connection->select(
            "SELECT h.ACTION_CODE, h.FROM_STATE_CODE, h.TO_STATE_CODE, h.COMMENTS,
                    TO_CHAR(h.EVENT_AT, 'YYYY-MM-DD HH24:MI:SS') AS EVENT_AT,
                    u.OBJECT_NAME AS ACTOR_NAME
             FROM RT_WORKFLOW_HISTORY h
             LEFT JOIN SEC_USER u ON u.OBJECT_ID = h.ACTOR_ID
             WHERE h.INSTANCE_ID = HEXTORAW(?)
             ORDER BY h.EVENT_AT DESC",
            [strtoupper((string) $instance['OBJECT_ID'])],
        );

        return array_map(static function ($r): array {
            $r = array_change_key_case((array) $r, CASE_UPPER);

            return [
                'action' => (string) ($r['ACTION_CODE'] ?? ''),
                'fromState' => $r['FROM_STATE_CODE'] ?? null,
                'toState' => $r['TO_STATE_CODE'] ?? null,
                'comment' => $r['COMMENTS'] ?? null,
                'actor' => $r['ACTOR_NAME'] ?? null,
                'at' => $r['EVENT_AT'] ?? null,
            ];
        }, $rows);
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    private function workflowOrNull(ExecutionContextInterface $context, string $datasetId): ?array
    {
        return $this->kernel->workflowForDataset($context->applicationId(), $datasetId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function instanceRow(string $workflowId, string $entityKey): ?array
    {
        $row = $this->connection->selectOne(
            'SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, RAWTOHEX(CURRENT_ACTIVITY_ID) AS CURRENT_ACTIVITY_ID,
                    CURRENT_STATE_CODE, INSTANCE_STATUS, RAWTOHEX(STARTED_BY) AS STARTED_BY
             FROM RT_WORKFLOW_INSTANCE
             WHERE WORKFLOW_ID = HEXTORAW(?) AND ENTITY_KEY = ?',
            [strtoupper($workflowId), $entityKey],
        );

        return $row === null ? null : array_change_key_case((array) $row, CASE_UPPER);
    }

    private function roleAllowed(ExecutionContextInterface $context, ?string $requiredRoleId): bool
    {
        if ($requiredRoleId === null || $requiredRoleId === '') {
            return true;
        }

        return in_array(strtoupper($requiredRoleId), array_map('strtoupper', $context->roleIds()), true);
    }

    /**
     * @param  array<string, mixed>  $wf
     * @param  array<string, mixed>  $instanceRow
     */
    private function stateFromInstance(array $wf, array $instanceRow): WorkflowStateInterface
    {
        $activityId = strtoupper((string) $instanceRow['CURRENT_ACTIVITY_ID']);
        $activity = $wf['activities'][$activityId] ?? null;

        return new WorkflowState(
            activityId: $activityId,
            stateCode: (string) $instanceRow['CURRENT_STATE_CODE'],
            stateName: $activity !== null ? (string) $activity['name'] : (string) $instanceRow['CURRENT_STATE_CODE'],
            instanceStatus: (string) $instanceRow['INSTANCE_STATUS'],
            final: $activity !== null ? (bool) $activity['final'] : false,
        );
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private function stateFromActivity(array $activity): WorkflowStateInterface
    {
        return new WorkflowState(
            activityId: (string) $activity['id'],
            stateCode: (string) $activity['code'],
            stateName: (string) $activity['name'],
            instanceStatus: $activity['final'] ? 'COMPLETED' : 'RUNNING',
            final: (bool) $activity['final'],
        );
    }

    private function writeHistory(
        string $instanceId,
        ?string $transitionId,
        string $actionCode,
        ?string $fromCode,
        ?string $toCode,
        ExecutionContextInterface $context,
        ?string $comment,
    ): void {
        $this->connection->insert(
            'INSERT INTO RT_WORKFLOW_HISTORY
                (OBJECT_ID, INSTANCE_ID, TRANSITION_ID, ACTION_CODE, FROM_STATE_CODE, TO_STATE_CODE, ACTOR_ID, COMMENTS)
             VALUES (HEXTORAW(?), HEXTORAW(?), HEXTORAW(?), ?, ?, ?, HEXTORAW(?), ?)',
            [
                strtoupper($this->identity->generate()),
                strtoupper($instanceId),
                $transitionId !== null ? strtoupper($transitionId) : null,
                $actionCode,
                $fromCode,
                $toCode,
                $this->rawUser($context),
                $comment,
            ],
        );
    }

    /**
     * Cermin state ke kolom bisnis bila STATE_COLUMN didefinisikan & dataset TABLE.
     *
     * @param  array<string, mixed>  $wf
     */
    private function mirrorState(ExecutionContextInterface $context, array $wf, string $entityKey, string $stateCode): void
    {
        $column = $wf['stateColumn'] ?? null;
        if ($column === null) {
            return;
        }
        $dataset = $this->kernel->dataset($context->applicationId(), (string) $wf['datasetId']);
        // mirrorState dilewati bila definisi dataset tidak tersedia di konteks ini.
        if ($dataset === null || ! in_array((string) ($dataset['sourceType'] ?? ''), ['TABLE'], true)) {
            return;
        }
        $table = strtoupper((string) $dataset['sourceObject']);
        $pk = strtoupper((string) $dataset['primaryKey']);
        if (! preg_match('/^[A-Z0-9_$#]+$/', $table) || ! preg_match('/^[A-Z0-9_$#]+$/', (string) $column)) {
            return;
        }

        $this->connection->update(
            "UPDATE {$table} SET {$column} = ? WHERE {$pk} = HEXTORAW(?)",
            [$stateCode, $entityKey],
        );
    }

    private function datasetCode(ExecutionContextInterface $context, string $datasetId): ?string
    {
        $ds = $this->kernel->dataset($context->applicationId(), $datasetId);

        return $ds !== null ? (string) $ds['code'] : null;
    }

    private function rawUser(ExecutionContextInterface $context): ?string
    {
        $userId = $context->userId();

        return $userId !== null ? strtoupper($userId) : null;
    }

    /**
     * Publikasikan event workflow ke Notification Engine (best-effort).
     * Kode event: WF.<workflow_code>.<action> (mis. WF.WF_CUSTOMER_APPROVAL.SUBMIT).
     *
     * @param  array<string, mixed>  $wf
     */
    private function publishEvent(
        ExecutionContextInterface $context,
        array $wf,
        string $entityKey,
        string $action,
        string $toState,
        string $fromState,
        string $ownerId,
    ): void {
        try {
            $eventCode = sprintf('WF.%s.%s', $wf['code'], strtoupper($action));
            $this->notifications->publish($context, $eventCode, [
                'entityKey' => $entityKey,
                'entity' => $this->entityLabel($context, (string) $wf['datasetId'], $entityKey),
                'actor' => $this->actorName($context),
                'ownerId' => $ownerId !== '' ? $ownerId : null,
                'state' => $toState,
                'fromState' => $fromState,
                'workflow' => (string) $wf['name'],
                'action' => strtoupper($action),
            ]);
        } catch (Throwable $e) {
            // Notifikasi tidak boleh menggagalkan transisi workflow.
            logger()->error('ODAF publish event workflow gagal', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Label ramah untuk baris bisnis: pakai kolom *_CODE bila ada, jika tidak
     * gunakan entityKey. Heuristik sesuai konvensi penamaan (Vol.2 Bab 06).
     */
    private function entityLabel(ExecutionContextInterface $context, string $datasetId, string $entityKey): string
    {
        $ds = $this->kernel->dataset($context->applicationId(), $datasetId);
        if ($ds === null || (string) ($ds['sourceType'] ?? '') !== 'TABLE') {
            return $entityKey;
        }
        $table = strtoupper((string) $ds['sourceObject']);
        $pk = strtoupper((string) $ds['primaryKey']);
        if (! preg_match('/^[A-Z0-9_$#]+$/', $table)) {
            return $entityKey;
        }

        $codeColumn = $this->connection->scalar(
            "SELECT COLUMN_NAME FROM USER_TAB_COLUMNS
             WHERE TABLE_NAME = ? AND COLUMN_NAME LIKE '%\_CODE' ESCAPE '\\'
             ORDER BY COLUMN_ID FETCH FIRST 1 ROWS ONLY",
            [$table],
        );
        if ($codeColumn === null) {
            return $entityKey;
        }
        $codeColumn = strtoupper((string) $codeColumn);
        if (! preg_match('/^[A-Z0-9_$#]+$/', $codeColumn)) {
            return $entityKey;
        }

        $label = $this->connection->scalar(
            "SELECT {$codeColumn} FROM {$table} WHERE {$pk} = HEXTORAW(?)",
            [$entityKey],
        );

        return $label !== null ? (string) $label : $entityKey;
    }

    private function actorName(ExecutionContextInterface $context): string
    {
        $userId = $context->userId();
        if ($userId === null) {
            return 'sistem';
        }
        $name = $this->connection->scalar(
            'SELECT OBJECT_NAME FROM SEC_USER WHERE OBJECT_ID = HEXTORAW(?)',
            [strtoupper($userId)],
        );

        return $name !== null ? (string) $name : 'sistem';
    }
}
