<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$pages = DB::select("SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE, PAGE_CONFIG, CUSTOM_VIEW_BLADE, CUSTOM_LOGIC_PHP FROM ODAF.UI_PAGE WHERE PAGE_TYPE = 'CUSTOM'");
$updated = 0;
foreach ($pages as $page) {
    if (empty($page->page_config) && !empty($page->custom_view_blade)) {
        $config = [
            [
                "id" => "block_" . Str::random(8),
                "type" => "custom",
                "data" => [
                    "phpLogic" => $page->custom_logic_php ?? "",
                    "bladeHtml" => $page->custom_view_blade ?? ""
                ]
            ]
        ];
        DB::update("UPDATE ODAF.UI_PAGE SET PAGE_CONFIG = ? WHERE OBJECT_ID = HEXTORAW(?)", [json_encode($config), $page->id]);
        $updated++;
        echo "Migrated {$page->object_code}\n";
    }
}
echo "Done. Updated $updated pages.\n";
