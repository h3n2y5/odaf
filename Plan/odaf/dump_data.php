<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = \Illuminate\Support\Facades\DB::table('user_tables')->where('table_name', 'LIKE', 'NEXUS_%')->pluck('table_name');
foreach ($tables as $t) {
    $rows = \Illuminate\Support\Facades\DB::table($t)->get();
    foreach ($rows as $row) {
        $cols = [];
        $vals = [];
        foreach ((array)$row as $k => $v) {
            $cols[] = "\"$k\"";
            if ($v === null) {
                $vals[] = "NULL";
            } else {
                if (str_ends_with(strtoupper((string)$k), '_ID') || str_ends_with(strtoupper((string)$k), '_BY')) {
                    $vals[] = "HEXTORAW('" . strtoupper(bin2hex($v)) . "')";
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)?$/', $v)) {
                    if (str_contains($v, '.')) {
                        $vals[] = "TO_TIMESTAMP('" . $v . "', 'YYYY-MM-DD HH24:MI:SS.FF')";
                    } else {
                        $vals[] = "TO_TIMESTAMP('" . $v . "', 'YYYY-MM-DD HH24:MI:SS')";
                    }
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                    $vals[] = "TO_DATE('" . $v . "', 'YYYY-MM-DD')";
                } else {
                    $vals[] = "'" . str_replace("'", "''", $v) . "'";
                }
            }
        }
        $colStr = implode(", ", $cols);
        $valStr = implode(", ", $vals);
        echo "INSERT INTO \"ODAF\".\"$t\" ($colStr) VALUES ($valStr);\n";
    }
}
echo "COMMIT;\n";
