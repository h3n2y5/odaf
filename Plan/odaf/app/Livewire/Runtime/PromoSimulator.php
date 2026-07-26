<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class PromoSimulator extends Component
{
    public array $products = [];
    public ?string $selectedProductId = null;
    
    // --- Base Values ---
    public float $baseCost = 0;
    public float $basePrice = 0;
    public int $stockQty = 0;

    // --- Simulation Settings ---
    public string $startDate = '';
    public string $endDate = '';
    public int $estimatedVolume = 0; // Asumsi barang laku

    // --- Supplier (Inbound) Promo ---
    public string $suppPromoType = 'NONE'; // NONE, BOGO, DISC
    public int $suppBuyQty = 2;
    public int $suppGetQty = 1;
    public float $suppDiscPct = 10;
    public string $suppPromoDesc = '';

    // --- Customer (Outbound) Promo ---
    public string $custPromoType = 'NONE'; // NONE, BOGO, DISC
    public int $custBuyQty = 2;
    public int $custGetQty = 1;
    public float $custDiscPct = 20;

    // --- Calculation Results ---
    public float $effCost = 0;
    public float $effPrice = 0;
    public float $profit = 0;
    public float $margin = 0;

    public float $normalProfit = 0;
    public float $normalMargin = 0;
    
    public float $totalProjectedProfit = 0;
    public float $totalNormalProfit = 0;

    public function mount(): void
    {
        $this->startDate = date('Y-m-d');
        $this->endDate = date('Y-m-d', strtotime('+14 days'));
        
        $rows = DB::select("SELECT RAWTOHEX(PRODUCT_ID) AS ID, PRODUCT_NAME FROM ODAF.PRODUCT WHERE ACTIVE_FLAG = 1 ORDER BY PRODUCT_NAME");
        $this->products = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $this->products[] = [
                'id' => $arr['id'] ?? $arr['ID'],
                'name' => $arr['product_name'] ?? $arr['PRODUCT_NAME'],
            ];
        }
        
        $this->calculate();
    }

    public function updatedSelectedProductId(): void
    {
        if ($this->selectedProductId) {
            $p = DB::selectOne("
                SELECT COST_PRICE, UNIT_PRICE, STOCK_QTY 
                FROM ODAF.PRODUCT 
                WHERE PRODUCT_ID = HEXTORAW(?)
            ", [$this->selectedProductId]);
            
            $arr = (array) ($p ?? []);
            if (!empty($arr)) {
                $this->baseCost = (float) ($arr['cost_price'] ?? $arr['COST_PRICE'] ?? 0);
                $this->basePrice = (float) ($arr['unit_price'] ?? $arr['UNIT_PRICE'] ?? 0);
                $this->stockQty = (int) ($arr['stock_qty'] ?? $arr['STOCK_QTY'] ?? 0);
                $this->estimatedVolume = $this->stockQty; // Default assumsi jual semua stok
            }
        }
        $this->calculate();
    }

    public function updated(): void
    {
        $this->calculate();
    }

    public function calculate(): void
    {
        // 1. Calculate Normal Profit
        $this->normalProfit = $this->basePrice - $this->baseCost;
        if ($this->baseCost > 0) {
            $this->normalMargin = ($this->normalProfit / $this->baseCost) * 100;
        } else {
            $this->normalMargin = 100;
        }

        // 2. Calculate Effective Cost (from Supplier Promo)
        $this->effCost = $this->baseCost;
        if ($this->suppPromoType === 'BOGO' && $this->suppBuyQty > 0 && $this->suppGetQty > 0) {
            $totalPaid = $this->suppBuyQty * $this->baseCost;
            $totalRcvd = $this->suppBuyQty + $this->suppGetQty;
            $this->effCost = $totalPaid / $totalRcvd;
        } elseif ($this->suppPromoType === 'DISC' && $this->suppDiscPct > 0) {
            $this->effCost = $this->baseCost * (1 - ($this->suppDiscPct / 100));
        }

        // 3. Calculate Effective Price (from Customer Promo)
        $this->effPrice = $this->basePrice;
        if ($this->custPromoType === 'BOGO' && $this->custBuyQty > 0 && $this->custGetQty > 0) {
            $totalRcvd = $this->custBuyQty * $this->basePrice;
            $totalGiven = $this->custBuyQty + $this->custGetQty;
            $this->effPrice = $totalRcvd / $totalGiven;
        } elseif ($this->custPromoType === 'DISC' && $this->custDiscPct > 0) {
            $this->effPrice = $this->basePrice * (1 - ($this->custDiscPct / 100));
        }

        // 4. Calculate Final Profit & Margin
        $this->profit = $this->effPrice - $this->effCost;
        if ($this->effCost > 0) {
            $this->margin = ($this->profit / $this->effCost) * 100;
        } else {
            $this->margin = 100;
        }
        
        // 5. Total Projected
        $this->totalProjectedProfit = $this->profit * $this->estimatedVolume;
        $this->totalNormalProfit = $this->normalProfit * $this->estimatedVolume;
    }

    public function submitDraft(): void
    {
        if (!$this->selectedProductId) {
            session()->flash('error', 'Pilih produk terlebih dahulu.');
            return;
        }
        
        if ($this->custPromoType === 'NONE') {
            session()->flash('error', 'Pilih tipe promo customer untuk diajukan.');
            return;
        }

        // Generate Promo Code
        $code = 'PROMO-' . strtoupper(substr(uniqid(), -5));
        $name = 'Promo Draft: ' . $code;

        // Insert as DRAFT
        DB::insert("
            INSERT INTO ODAF.POS_PROMO (
                OBJECT_ID, PROMO_CODE, PROMO_NAME, PROMO_TYPE, 
                START_DATE, END_DATE, PRODUCT_ID,
                BUY_QTY, GET_QTY, DISCOUNT_PCT, ACTIVE_FLAG, 
                STATUS, SUPPLIER_PROMO_DESC, MAX_PROMO_QTY
            ) VALUES (
                SYS_GUID(), ?, ?, ?, 
                TO_TIMESTAMP(?,'YYYY-MM-DD'), TO_TIMESTAMP(?,'YYYY-MM-DD'), HEXTORAW(?), 
                ?, ?, ?, 1, 
                'DRAFT', ?, ?
            )
        ", [
            $code, $name, $this->custPromoType === 'BOGO' ? 'BOGO' : 'DISC_PCT',
            $this->startDate, $this->endDate, $this->selectedProductId,
            $this->custPromoType === 'BOGO' ? $this->custBuyQty : 0,
            $this->custPromoType === 'BOGO' ? $this->custGetQty : 0,
            $this->custPromoType === 'DISC' ? $this->custDiscPct : 0,
            $this->suppPromoDesc, $this->estimatedVolume
        ]);
        
        DB::statement('COMMIT');

        session()->flash('success', 'Promo berhasil disimpan sebagai DRAFT ('.$code.'). Silakan proses Approval di menu Master Promo.');
    }

    public function render()
    {
        return view('livewire.runtime.promo-simulator');
    }
}
