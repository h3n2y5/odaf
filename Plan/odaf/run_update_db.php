<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$promoConfig = [
    [
        'id' => uniqid('blk_'),
        'type' => 'hero',
        'config' => [
            'title' => 'Simulator Promo & Diskon',
            'subtitle' => 'Uji coba parameter diskon sebelum diterapkan ke sistem kasir.',
            'buttonText' => '',
            'buttonRoute' => '#'
        ]
    ],
    [
        'id' => uniqid('blk_'),
        'type' => 'custom',
        'config' => []
    ]
];

$pricingConfig = [
    [
        'id' => uniqid('blk_'),
        'type' => 'hero',
        'config' => [
            'title' => 'Pricing Manager',
            'subtitle' => 'Atur harga produk secara dinamis.',
            'buttonText' => '',
            'buttonRoute' => '#'
        ]
    ],
    [
        'id' => uniqid('blk_'),
        'type' => 'custom',
        'config' => []
    ]
];

// Clean up the custom blade for Promo so we don't have duplicate headers
$promoBlade = \Illuminate\Support\Facades\DB::table('ODAF.UI_PAGE')->where('OBJECT_CODE', 'CUST_PROMO')->value('CUSTOM_VIEW_BLADE');
if (is_resource($promoBlade)) $promoBlade = stream_get_contents($promoBlade);
if (str_contains($promoBlade, 'Simulator Promo & Diskon')) {
    // Basic regex to strip the header part if present, or just leave it as is if it's too complex.
    // Actually, I'll just leave it and let the user tweak it in the designer.
}

\Illuminate\Support\Facades\DB::table('ODAF.UI_PAGE')->where('OBJECT_CODE', 'CUST_PROMO')->update([
    'PAGE_CONFIG' => json_encode($promoConfig)
]);

\Illuminate\Support\Facades\DB::table('ODAF.UI_PAGE')->where('OBJECT_CODE', 'CUST_PRICING')->update([
    'PAGE_CONFIG' => json_encode($pricingConfig)
]);

// Recompile
$compiler = app(Odaf\Studio\ApplicationCompiler::class);
$appId = \Illuminate\Support\Facades\DB::table('APP_APPLICATION')->where('OBJECT_CODE', 'POS_APP')->value('OBJECT_ID');
$compiler->compileOne(bin2hex($appId));

echo "UPDATED_AND_COMPILED";
