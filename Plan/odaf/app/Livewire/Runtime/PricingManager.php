<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class PricingManager extends Component
{
    public string $search = '';

    /**
     * Data produk: id, code, name, cost_price, margin, selling_price (unit_price)
     */
    public array $products = [];

    public function mount(): void
    {
        $this->loadProducts();
    }

    public function loadProducts(): void
    {
        $sql = "SELECT RAWTOHEX(PRODUCT_ID) AS ID, PRODUCT_CODE, PRODUCT_NAME, 
                       COST_PRICE, PROFIT_MARGIN, UNIT_PRICE
                FROM ODAF.PRODUCT
                WHERE ACTIVE_FLAG = 1 AND DELETED_AT IS NULL";
        
        $binds = [];
        if (trim($this->search) !== '') {
            $sql .= " AND (UPPER(PRODUCT_NAME) LIKE UPPER(?) OR UPPER(PRODUCT_CODE) LIKE UPPER(?))";
            $binds[] = "%" . trim($this->search) . "%";
            $binds[] = "%" . trim($this->search) . "%";
        }
        
        $sql .= " ORDER BY PRODUCT_NAME";

        $rows = DB::select($sql, $binds);
        $this->products = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $this->products[] = [
                'id' => $arr['id'] ?? $arr['ID'],
                'code' => $arr['product_code'] ?? $arr['PRODUCT_CODE'],
                'name' => $arr['product_name'] ?? $arr['PRODUCT_NAME'],
                'cost' => (float) ($arr['cost_price'] ?? $arr['COST_PRICE'] ?? 0),
                'margin' => (float) ($arr['profit_margin'] ?? $arr['PROFIT_MARGIN'] ?? 0),
                'price' => (float) ($arr['unit_price'] ?? $arr['UNIT_PRICE'] ?? 0),
            ];
        }
    }

    public function updatedSearch(): void
    {
        $this->loadProducts();
    }

    /**
     * Dipanggil ketika HPP atau Margin berubah, hitung ulang Harga Jual
     */
    public function recalculate(int $index): void
    {
        $cost = (float) $this->products[$index]['cost'];
        $margin = (float) $this->products[$index]['margin'];
        
        // Harga Jual = HPP + (HPP * Margin / 100)
        $price = $cost + ($cost * $margin / 100);
        $this->products[$index]['price'] = $price;
        
        $this->saveRow($index);
    }

    /**
     * Dipanggil ketika Harga Jual diubah manual, hitung balik Margin-nya
     */
    public function recalculateMargin(int $index): void
    {
        $cost = (float) $this->products[$index]['cost'];
        $price = (float) $this->products[$index]['price'];
        
        if ($cost > 0) {
            $margin = (($price - $cost) / $cost) * 100;
            $this->products[$index]['margin'] = round($margin, 2);
        } else {
            $this->products[$index]['margin'] = 100; // default jika cost 0
        }
        
        $this->saveRow($index);
    }

    private function saveRow(int $index): void
    {
        $p = $this->products[$index];
        DB::update("
            UPDATE ODAF.PRODUCT 
            SET COST_PRICE = ?, PROFIT_MARGIN = ?, UNIT_PRICE = ?, UPDATED_AT = SYSTIMESTAMP
            WHERE PRODUCT_ID = HEXTORAW(?)
        ", [
            $p['cost'], $p['margin'], $p['price'], $p['id']
        ]);
        
        // Notifikasi Toast bisa dipanggil di sini jika perlu
    }

    public function render()
    {
        return view('livewire.runtime.pricing-manager');
    }
}
