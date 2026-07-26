<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$repo = app(\Odaf\Runtime\RuntimePackageRepository::class);
$packages = $repo->loadAllActive();

foreach ($packages as $pkg) {
    $payload = $pkg->toArray();
    echo "ID: " . $pkg->applicationId() . "\n";
    echo "Code: " . ($payload['application']['code'] ?? 'UNKNOWN') . "\n";
    echo "Name: " . ($payload['application']['name'] ?? 'Unknown App') . "\n";
    echo "-----\n";
}
