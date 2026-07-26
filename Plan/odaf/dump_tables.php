<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = \Illuminate\Support\Facades\DB::table('user_tables')->where('table_name', 'LIKE', 'NEXUS_%')->pluck('table_name');
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $ddl = \Illuminate\Support\Facades\DB::select("SELECT dbms_metadata.get_ddl('TABLE', ?, 'ODAF') as ddl FROM dual", [$t])[0]->ddl;
    echo $ddl . ";\n";
}
