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
                // If the column ends with _ID or _BY, it might be RAW and we should wrap it in HEXTORAW
                if (str_ends_with(strtoupper((string)$k), '_ID') || str_ends_with(strtoupper((string)$k), '_BY')) {
                    $vals[] = "HEXTORAW('" . strtoupper(bin2hex($v)) . "')";
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
