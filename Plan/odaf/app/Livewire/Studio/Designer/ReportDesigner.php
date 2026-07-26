<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class ReportDesigner extends Component
{
    public string $appId;
    public string $reportId;
    
    public string $reportName = '';
    public string $reportDesc = '';
    public string $sqlQuery = '';

    public array $application = [];
    public array $detectedParams = [];

    public function mount(StudioAccess $access, string $appId, string $reportId): void
    {
        $access->ensureAdmin();

        $this->appId = $appId;
        $this->reportId = $reportId;

        $appRow = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME FROM ODAF.APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
        if (!$appRow) abort(404, 'App not found');
        $this->application = (array) $appRow;

        if ($this->reportId !== 'new') {
            $rep = DB::selectOne("SELECT RAWTOHEX(REPORT_ID) AS ID, REPORT_NAME, REPORT_DESC, SQL_QUERY FROM ODAF.APP_REPORT WHERE REPORT_ID = HEXTORAW(?)", [$this->reportId]);
            if ($rep) {
                $arr = (array)$rep;
                $this->reportName = $arr['report_name'] ?? $arr['REPORT_NAME'] ?? '';
                $this->reportDesc = $arr['report_desc'] ?? $arr['REPORT_DESC'] ?? '';
                $sql = $arr['sql_query'] ?? $arr['SQL_QUERY'] ?? '';
                if (is_resource($sql)) $sql = stream_get_contents($sql);
                $this->sqlQuery = $sql;
                
                $this->detectParams();
            }
        }
    }

    public function updatedSqlQuery(): void
    {
        $this->detectParams();
    }

    public function detectParams(): void
    {
        preg_match_all('/\:([a-zA-Z0-9_]+)/', $this->sqlQuery, $matches);
        if (isset($matches[1])) {
            $this->detectedParams = array_values(array_unique($matches[1]));
        } else {
            $this->detectedParams = [];
        }
    }

    public function save(): void
    {
        $this->validate([
            'reportName' => 'required|string|max:200',
            'sqlQuery' => 'required|string',
        ]);

        if ($this->reportId === 'new') {
            $newId = DB::selectOne("SELECT RAWTOHEX(SYS_GUID()) AS ID FROM DUAL")->id ?? DB::selectOne("SELECT RAWTOHEX(SYS_GUID()) AS ID FROM DUAL")->ID;
            
            DB::insert("
                INSERT INTO ODAF.APP_REPORT (REPORT_ID, APP_CODE, REPORT_NAME, REPORT_DESC, SQL_QUERY)
                VALUES (HEXTORAW(?), ?, ?, ?, ?)
            ", [
                $newId,
                $this->application['APP_CODE'] ?? $this->application['app_code'],
                $this->reportName,
                $this->reportDesc,
                $this->sqlQuery
            ]);
            
            DB::statement('COMMIT');
            session()->flash('success', 'Report berhasil dibuat.');
            $this->redirect(route('studio.report.designer', ['appId' => $this->appId, 'reportId' => $newId]), navigate: true);
        } else {
            DB::update("
                UPDATE ODAF.APP_REPORT 
                SET REPORT_NAME = ?, REPORT_DESC = ?, SQL_QUERY = ?, UPDATED_AT = CURRENT_TIMESTAMP
                WHERE REPORT_ID = HEXTORAW(?)
            ", [
                $this->reportName,
                $this->reportDesc,
                $this->sqlQuery,
                $this->reportId
            ]);
            DB::statement('COMMIT');
            session()->flash('success', 'Report berhasil diperbarui.');
        }
    }

    public function render()
    {
        return view('livewire.studio.designer.report-designer');
    }
}
