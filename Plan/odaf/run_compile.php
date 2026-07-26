<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = app(Odaf\Studio\ApplicationCompiler::class);
$appId = Illuminate\Support\Facades\DB::table('APP_APPLICATION')->where('OBJECT_CODE', 'POS_APP')->value('OBJECT_ID');
$compiler->compileOne(bin2hex($appId));

echo "COMPILED";
