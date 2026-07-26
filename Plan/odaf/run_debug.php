<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$res = DB::select("SELECT OBJECT_CODE, DBMS_LOB.SUBSTR(PAGE_CONFIG, 4000, 1) as PAGE_CONFIG, DBMS_LOB.SUBSTR(CUSTOM_VIEW_BLADE, 4000, 1) as CUSTOM_VIEW_BLADE FROM ODAF.UI_PAGE WHERE OBJECT_CODE IN ('CUST_PRICING', 'CUST_PROMO')");
file_put_contents('debug.json', json_encode($res, JSON_PRETTY_PRINT));
echo "DONE";
