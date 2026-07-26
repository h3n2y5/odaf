<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportExportController
{
    public function exportCsv(Request $request, string $appCode, string $reportId)
    {
        $rep = DB::selectOne("
            SELECT REPORT_NAME, SQL_QUERY 
            FROM ODAF.APP_REPORT 
            WHERE APP_CODE = ? AND REPORT_ID = HEXTORAW(?)
        ", [$appCode, $reportId]);

        if (!$rep) abort(404, 'Report not found');
        
        $sql = $rep->sql_query ?? $rep->SQL_QUERY;
        if (is_resource($sql)) $sql = stream_get_contents($sql);

        // Bindings from request
        $binds = $request->except(['appCode', 'reportId']);
        
        try {
            $results = DB::select($sql, $binds);
        } catch (\Exception $e) {
            abort(500, 'Error executing report query: ' . $e->getMessage());
        }

        $filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $rep->report_name ?? $rep->REPORT_NAME) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            
            if (count($results) > 0) {
                // Header row
                fputcsv($file, array_keys((array)$results[0]));
                
                // Data rows
                foreach ($results as $row) {
                    fputcsv($file, array_values((array)$row));
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
