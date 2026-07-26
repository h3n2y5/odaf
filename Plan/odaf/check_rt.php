<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::select('SELECT RAWTOHEX(APPLICATION_ID) AS APP_ID, ACTIVE_FLAG FROM ODAF.RT_PACKAGE');
foreach($rows as $r) {
    echo $r->app_id . ' - ' . $r->active_flag . "\n";
}
