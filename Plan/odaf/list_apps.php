<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$apps = \Illuminate\Support\Facades\DB::table('APP_APPLICATION')->get();
foreach ($apps as $a) {
    echo $a->object_code . " - " . $a->object_name . "\n";
}
