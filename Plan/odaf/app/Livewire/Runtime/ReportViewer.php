<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf_runtime')]
final class ReportViewer extends Component
{
    public string $appCode;
    public string $reportId;
    
    public array $report = [];
    public array $parameters = [];
    public array $paramValues = []; // Binds
    
    public array $results = [];
    public array $columns = [];
    public bool $hasRun = false;
    public ?string $errorMessage = null;

    public function mount(string $appCode, string $reportId): void
    {
        $this->appCode = $appCode;
        $this->reportId = $reportId;

        $rep = DB::selectOne("
            SELECT RAWTOHEX(REPORT_ID) AS ID, REPORT_NAME, REPORT_DESC, SQL_QUERY 
            FROM ODAF.APP_REPORT 
            WHERE APP_CODE = ? AND REPORT_ID = HEXTORAW(?)
        ", [$this->appCode, $this->reportId]);
        
        if (!$rep) abort(404, 'Report not found');
        $arr = (array)$rep;
        $this->report = [
            'id' => $arr['id'] ?? $arr['ID'],
            'name' => $arr['report_name'] ?? $arr['REPORT_NAME'],
            'desc' => $arr['report_desc'] ?? $arr['REPORT_DESC'],
            'sql' => $arr['sql_query'] ?? $arr['SQL_QUERY'],
        ];
        
        if (is_resource($this->report['sql'])) {
            $this->report['sql'] = stream_get_contents($this->report['sql']);
        }

        // Parse Parameters
        preg_match_all('/\:([a-zA-Z0-9_]+)/', $this->report['sql'], $matches);
        if (isset($matches[1])) {
            $this->parameters = array_values(array_unique($matches[1]));
            foreach ($this->parameters as $p) {
                $this->paramValues[$p] = ''; // Init empty
            }
        }
    }

    public function runReport(): void
    {
        $this->validate(array_fill_keys(array_map(fn($p) => "paramValues.{$p}", $this->parameters), 'required'));

        $this->errorMessage = null;
        $this->hasRun = false;
        $this->results = [];
        $this->columns = [];

        try {
            $this->results = DB::select($this->report['sql'], $this->paramValues);
            
            if (count($this->results) > 0) {
                $this->columns = array_keys((array) $this->results[0]);
            }
            $this->hasRun = true;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.runtime.report-viewer');
    }
}
