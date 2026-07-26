<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedCustomPages extends Command
{
    protected $signature = 'odaf:seed-custom-pages';
    protected $description = 'Seed Custom Pages for POS and Promo';

    public function handle()
    {
        $this->info("Seeding Custom Pages...");
        
        $appIdHex = '5765E337C7164C54E063030012ACA96A';

$posBlade = <<<'BLADE'
<div class="h-[calc(100vh-80px)] bg-slate-900 rounded-xl overflow-hidden flex flex-col md:flex-row text-slate-100 shadow-2xl border border-slate-700">
    <!-- Katalog Produk (Kiri) -->
    <div class="flex-1 flex flex-col border-r border-slate-700">
        <div class="p-4 border-b border-slate-800 bg-slate-900 flex items-center justify-between">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Katalog Produk
            </h2>
            <div class="relative w-64">
                <input type="text" wire:model.live="searchQuery" class="w-full bg-slate-800 border-none rounded-lg text-sm text-slate-200 pl-10 focus:ring-1 focus:ring-indigo-500" placeholder="Scan Barcode / Cari Nama...">
                <svg class="w-5 h-5 absolute left-3 top-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 bg-slate-800/50">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($products as $prod)
                    @if(empty($searchQuery) || stripos($prod['name'], $searchQuery) !== false)
                    <button wire:click="addToCart({$prod['id']})" class="bg-slate-800 border border-slate-700 hover:border-indigo-500 hover:shadow-lg rounded-xl p-4 text-left transition-all group flex flex-col h-32 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <h3 class="font-bold text-slate-200 line-clamp-2 z-10">{{$prod['name']}}</h3>
                        <div class="mt-auto z-10 flex justify-between items-end">
                            <span class="text-sm font-mono text-indigo-400">Rp {{ number_format($prod['price'], 0, ',', '.') }}</span>
                            <span class="text-xs bg-slate-700 px-2 py-1 rounded text-slate-300">Stok: 99</span>
                        </div>
                    </button>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Keranjang Belanja (Kanan) -->
    <div class="w-full md:w-[400px] flex flex-col bg-slate-900">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Keranjang</h2>
            <button wire:click="clearCart" class="text-xs text-rose-400 hover:text-rose-300">Kosongkan</button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            @if(count($cart) === 0)
                <div class="h-full flex flex-col items-center justify-center text-slate-500">
                    <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <p>Keranjang masih kosong</p>
                </div>
            @else
                @foreach($cart as $idx => $item)
                    <div class="bg-slate-800 p-3 rounded-lg border border-slate-700 flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold text-sm text-slate-200">{{$item['name']}}</h4>
                            <button wire:click="removeItem({$idx})" class="text-slate-500 hover:text-rose-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <div class="flex items-center gap-3 bg-slate-900 rounded-lg p-1 border border-slate-700">
                                <button wire:click="updateQty({$idx}, -1)" class="w-6 h-6 rounded bg-slate-800 flex items-center justify-center text-slate-300 hover:bg-slate-700">-</button>
                                <span class="font-mono text-sm w-6 text-center">{{$item['qty']}}</span>
                                <button wire:click="updateQty({$idx}, 1)" class="w-6 h-6 rounded bg-slate-800 flex items-center justify-center text-slate-300 hover:bg-slate-700">+</button>
                            </div>
                            <span class="font-mono text-sm font-bold text-emerald-400">Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        
        <!-- Panel Total & Bayar -->
        <div class="p-4 bg-slate-800 border-t border-slate-700 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.5)] z-20">
            <div class="flex justify-between text-sm text-slate-400 mb-2">
                <span>Subtotal</span>
                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-400 mb-3">
                <span>Pajak (11%)</span>
                <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-end mb-4 pt-3 border-t border-slate-700">
                <span class="text-lg font-bold text-slate-200">Total</span>
                <span class="text-2xl font-bold font-mono text-emerald-400">Rp {{ number_format($total, 0, ',', '.') }}</span>
            </div>
            <button wire:click="processPayment" 
                    @if(count($cart) === 0) disabled @endif
                    class="w-full py-3 rounded-lg font-bold text-lg text-white transition-all shadow-lg flex items-center justify-center gap-2
                           {{ count($cart) === 0 ? 'bg-slate-700 text-slate-500 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-500/20' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                BAYAR SEKARANG
            </button>
            @if($paymentSuccess)
                <div class="mt-3 p-2 bg-emerald-500/20 border border-emerald-500/50 rounded text-emerald-400 text-sm text-center animate-pulse">
                    Pembayaran Berhasil! Struk sedang dicetak...
                </div>
            @endif
        </div>
    </div>
</div>
BLADE;

$posPhp = <<<'PHP'
    public array $products = [
        ['id' => 1, 'name' => 'Kopi Espresso Premium', 'price' => 25000],
        ['id' => 2, 'name' => 'Caramel Macchiato', 'price' => 35000],
        ['id' => 3, 'name' => 'Matcha Latte', 'price' => 30000],
        ['id' => 4, 'name' => 'Croissant Butter', 'price' => 20000],
        ['id' => 5, 'name' => 'Red Velvet Cake (Slice)', 'price' => 45000],
        ['id' => 6, 'name' => 'Air Mineral Botol', 'price' => 10000],
        ['id' => 7, 'name' => 'Tumbler Eksklusif', 'price' => 150000],
        ['id' => 8, 'name' => 'Biji Kopi Arabica 250g', 'price' => 85000],
    ];

    public string $searchQuery = '';
    public array $cart = [];
    public float $subtotal = 0;
    public float $tax = 0;
    public float $total = 0;
    public bool $paymentSuccess = false;

    public function addToCart(int $productId)
    {
        $this->paymentSuccess = false;
        $product = collect($this->products)->firstWhere('id', $productId);
        if (!$product) return;

        $existingIdx = collect($this->cart)->search(fn($item) => $item['id'] === $productId);
        
        if ($existingIdx !== false) {
            $this->cart[$existingIdx]['qty']++;
        } else {
            $this->cart[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => 1
            ];
        }
        $this->calculateTotals();
    }

    public function updateQty(int $idx, int $change)
    {
        $this->paymentSuccess = false;
        if (!isset($this->cart[$idx])) return;
        
        $this->cart[$idx]['qty'] += $change;
        if ($this->cart[$idx]['qty'] <= 0) {
            unset($this->cart[$idx]);
            $this->cart = array_values($this->cart); // reindex
        }
        $this->calculateTotals();
    }

    public function removeItem(int $idx)
    {
        unset($this->cart[$idx]);
        $this->cart = array_values($this->cart);
        $this->calculateTotals();
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->calculateTotals();
        $this->paymentSuccess = false;
    }

    public function calculateTotals()
    {
        $this->subtotal = collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
        $this->tax = $this->subtotal * 0.11;
        $this->total = $this->subtotal + $this->tax;
    }

    public function processPayment()
    {
        if (count($this->cart) === 0) return;
        
        // Di sini bisa ditambahkan logika insert ke tabel T_SALES
        $this->paymentSuccess = true;
        
        // Kosongkan keranjang setelah beberapa detik (dibuat simple)
        $this->cart = [];
        $this->calculateTotals();
    }
PHP;


// -----------------------------------------------------------------------------
// 2. Promo Simulator Custom Page
// -----------------------------------------------------------------------------
$promoBlade = <<<'BLADE'
<div class="h-full bg-slate-50 p-6 rounded-xl border border-slate-200 shadow-sm overflow-y-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Simulator Promo & Diskon
            </h1>
            <p class="text-slate-500 mt-1">Uji coba parameter diskon sebelum diterapkan ke sistem kasir.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Input Parameter -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Parameter Skenario</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Total Pembelanjaan (Rp)</label>
                    <input type="number" wire:model.live="basePrice" class="w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Diskon</label>
                        <select wire:model.live="discountType" class="w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                            <option value="PERCENT">% Persentase</option>
                            <option value="NOMINAL">Rp Nominal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nilai Diskon</label>
                        <input type="number" wire:model.live="discountValue" class="w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    </div>
                </div>

                @if($discountType === 'PERCENT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Maksimal Diskon (Rp) <span class="text-xs font-normal text-slate-400">0 = Tanpa batas</span></label>
                    <input type="number" wire:model.live="maxDiscount" class="w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
                @endif
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Minimal Belanja (Rp) <span class="text-xs font-normal text-slate-400">Agar promo aktif</span></label>
                    <input type="number" wire:model.live="minPurchase" class="w-full rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
            </div>
            
            <button wire:click="calculate" class="mt-6 w-full bg-slate-800 text-white font-bold py-2 rounded-lg hover:bg-slate-700 transition">
                Hitung Simulasi
            </button>
        </div>

        <!-- Hasil Simulasi -->
        <div class="bg-gradient-to-br from-rose-500 to-orange-500 p-6 rounded-xl shadow-lg text-white relative overflow-hidden">
            <div class="absolute -right-10 -top-10 opacity-10">
                <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 4.5l6.5 13h-13L12 6.5z"/></svg>
            </div>
            
            <h3 class="text-lg font-bold text-white/90 mb-4 border-b border-white/20 pb-2 relative z-10">Hasil Perhitungan</h3>
            
            <div class="relative z-10 space-y-6">
                @if($basePrice < $minPurchase)
                    <div class="bg-rose-900/40 p-4 rounded-lg border border-rose-300/30 backdrop-blur-sm">
                        <div class="font-bold text-rose-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Promo Tidak Aktif
                        </div>
                        <p class="text-sm text-rose-200 mt-1">Pembelanjaan (Rp {{ number_format($basePrice, 0, ',', '.') }}) kurang dari minimum syarat (Rp {{ number_format($minPurchase, 0, ',', '.') }}).</p>
                    </div>
                    <div class="text-center pt-4">
                        <div class="text-white/60 text-sm">Pelanggan Tetap Membayar</div>
                        <div class="text-4xl font-mono font-bold mt-1">Rp {{ number_format($basePrice, 0, ',', '.') }}</div>
                    </div>
                @else
                    <div class="bg-white/10 p-4 rounded-lg backdrop-blur-sm">
                        <div class="flex justify-between text-sm text-white/80 mb-2">
                            <span>Harga Awal</span>
                            <span>Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-rose-200 mb-2">
                            <span>Potongan Diskon</span>
                            <span>- Rp {{ number_format($calculatedDiscount, 0, ',', '.') }}</span>
                        </div>
                        @if($isCapped)
                            <div class="text-xs text-orange-200 text-right italic mb-2">
                                * Diskon mencapai batas maksimal (Capped at Rp {{ number_format($maxDiscount, 0, ',', '.') }})
                            </div>
                        @endif
                        <div class="flex justify-between items-end border-t border-white/20 pt-3 mt-2">
                            <span class="font-bold text-white/90">Harga Akhir</span>
                            <span class="text-3xl font-mono font-bold">Rp {{ number_format($finalPrice, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <div class="text-center bg-white/20 py-2 rounded-lg backdrop-blur-sm border border-white/30">
                        <span class="text-sm font-medium">Penghematan Pelanggan:</span>
                        <span class="ml-2 font-bold text-amber-200">
                            {{ $basePrice > 0 ? round(($calculatedDiscount / $basePrice) * 100, 1) : 0 }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
BLADE;

$promoPhp = <<<'PHP'
    public float $basePrice = 1000000;
    public string $discountType = 'PERCENT';
    public float $discountValue = 20;
    public float $maxDiscount = 150000;
    public float $minPurchase = 500000;

    public float $calculatedDiscount = 0;
    public float $finalPrice = 1000000;
    public bool $isCapped = false;

    public function mount()
    {
        $this->calculate();
    }

    public function updated($property)
    {
        $this->calculate();
    }

    public function calculate()
    {
        $this->isCapped = false;
        
        if ($this->basePrice < $this->minPurchase) {
            $this->calculatedDiscount = 0;
            $this->finalPrice = $this->basePrice;
            return;
        }

        if ($this->discountType === 'PERCENT') {
            $rawDiscount = $this->basePrice * ($this->discountValue / 100);
            if ($this->maxDiscount > 0 && $rawDiscount > $this->maxDiscount) {
                $this->calculatedDiscount = $this->maxDiscount;
                $this->isCapped = true;
            } else {
                $this->calculatedDiscount = $rawDiscount;
            }
        } else {
            $this->calculatedDiscount = $this->discountValue;
        }

        // Hindari diskon lebih besar dari harga
        if ($this->calculatedDiscount > $this->basePrice) {
            $this->calculatedDiscount = $this->basePrice;
        }

        $this->finalPrice = $this->basePrice - $this->calculatedDiscount;
    }
PHP;


// -----------------------------------------------------------------------------
// INJECTION LOGIC
// -----------------------------------------------------------------------------
        try {
            DB::beginTransaction();

            // Hapus Hardcoded System Modules lama dari menu agar tidak double
            DB::delete("DELETE FROM ODAF.APP_MENU WHERE CUSTOM_ROUTE IS NOT NULL AND MODULE_ID IN (SELECT OBJECT_ID FROM ODAF.APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?))", [$appIdHex]);

            $this->insertCustomPage($appIdHex, 'CUST_POS', 'Point of Sale (Kasir)', $posBlade, $posPhp);
            $this->insertCustomPage($appIdHex, 'CUST_PROMO', 'Promo Simulator', $promoBlade, $promoPhp);
            
            // Seed blank pages for the other requested system modules so the user can design them
            $blankBlade = "<div>\n    <h1 class=\"text-2xl font-bold text-slate-800\">Page Under Construction</h1>\n    <p class=\"text-slate-500 mt-2\">Gunakan Custom Page Designer untuk mendesain halaman ini.</p>\n</div>";
            $blankPhp = "";

            $this->insertCustomPage($appIdHex, 'CUST_PRICING', 'Pricing Manager', $blankBlade, $blankPhp);
            $this->insertCustomPage($appIdHex, 'CUST_SYS_SECURITY', 'System Security', $blankBlade, $blankPhp);
            $this->insertCustomPage($appIdHex, 'CUST_SYS_ACCESS', 'System Access', $blankBlade, $blankPhp);
            $this->insertCustomPage($appIdHex, 'CUST_SYS_WORKFLOW', 'System Workflow', $blankBlade, $blankPhp);
            
            DB::commit();
            $this->info("Seed completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to seed: " . $e->getMessage());
        }
    }

    private function insertCustomPage($appId, $pageCode, $pageName, $blade, $php) {
        // Delete existing if any
        DB::delete("DELETE FROM ODAF.APP_MENU WHERE OBJECT_CODE = ? AND MODULE_ID IN (SELECT OBJECT_ID FROM ODAF.APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?))", ['MNU_' . $pageCode, $appId]);
        DB::delete("DELETE FROM ODAF.UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)", [$pageCode, $appId]);

        $pageId = strtoupper(str_replace('-', '', Str::uuid()->toString()));
        
        $config = [
            [
                'id' => 'block_' . Str::random(8),
                'type' => 'hero',
                'config' => [
                    'title' => $pageName,
                    'subtitle' => 'Telah dioptimasi untuk antarmuka ODAF',
                    'buttonText' => '',
                    'buttonRoute' => ''
                ]
            ],
            [
                'id' => 'block_' . Str::random(8),
                'type' => 'custom',
                'config' => []
            ]
        ];

        DB::insert("
            INSERT INTO ODAF.UI_PAGE (OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, PAGE_TYPE, LAYOUT_TYPE, VERSION_NO, STATUS, CUSTOM_VIEW_BLADE, CUSTOM_LOGIC_PHP, PAGE_CONFIG)
            VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, 'CUSTOM', 'BLANK', 1, 'PUBLISHED', ?, ?, ?)
        ", [$pageId, $appId, $pageCode, $pageName, $blade, $php, json_encode($config)]);

        // Ensure module MOD_CUSTOM_PAGES exists
        $mod = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS ID FROM ODAF.APP_MODULE WHERE OBJECT_CODE = 'MOD_CUSTOM_PAGES' AND APPLICATION_ID = HEXTORAW(?)", [$appId]);
        if (!$mod) {
            $modId = strtoupper(str_replace('-', '', Str::uuid()->toString()));
            DB::insert("INSERT INTO ODAF.APP_MODULE (OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, DISPLAY_ORDER, STATUS) VALUES (HEXTORAW(?), HEXTORAW(?), 'MOD_CUSTOM_PAGES', 'Sistem Kustom (Hybrid)', 99, 'PUBLISHED')", [$modId, $appId]);
        } else {
            $modId = $mod->id;
        }

        // Insert Menu
        $menuId = strtoupper(str_replace('-', '', Str::uuid()->toString()));
        DB::insert("
            INSERT INTO ODAF.APP_MENU (OBJECT_ID, MODULE_ID, OBJECT_CODE, OBJECT_NAME, PAGE_ID, ICON, DISPLAY_ORDER, VISIBLE_FLAG, STATUS)
            VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, HEXTORAW(?), 'sparkles', 10, 1, 'PUBLISHED')
        ", [$menuId, $modId, 'MNU_' . $pageCode, $pageName, $pageId]);
        
        $this->info("Inserted Custom Page: $pageName ($pageCode)");
    }
}
