<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class PromoManager extends Component
{
    public array $promos = [];
    public array $products = [];

    // Form state
    public bool $showForm = false;
    public string $formMode = 'INSERT';
    public ?string $promoId = null;
    
    public string $promoCode = '';
    public string $promoName = '';
    public string $promoType = 'BOGO'; // BOGO, DISC_PCT, TIERED
    public ?string $productId = null;
    public string $startDate = '';
    public string $endDate = '';
    
    // BOGO config
    public int $buyQty = 1;
    public int $getQty = 1;
    
    // Discount config
    public float $discountPct = 0;
    
    // Tiered config
    public array $tieredConfig = []; // [['qty' => 3, 'disc' => 10]]
    
    public bool $activeFlag = true;

    public function mount(): void
    {
        $this->loadPromos();
        $this->loadProducts();
    }

    public function loadPromos(): void
    {
        $rows = DB::select("
            SELECT RAWTOHEX(OBJECT_ID) AS ID, PROMO_CODE, PROMO_NAME, PROMO_TYPE, 
                   TO_CHAR(START_DATE, 'YYYY-MM-DD') AS SDATE, 
                   TO_CHAR(END_DATE, 'YYYY-MM-DD') AS EDATE,
                   ACTIVE_FLAG, RAWTOHEX(PRODUCT_ID) AS PROD_ID
            FROM ODAF.POS_PROMO
            ORDER BY CREATED_AT DESC
        ");
        
        $this->promos = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $this->promos[] = [
                'id' => $arr['id'] ?? $arr['ID'],
                'code' => $arr['promo_code'] ?? $arr['PROMO_CODE'],
                'name' => $arr['promo_name'] ?? $arr['PROMO_NAME'],
                'type' => $arr['promo_type'] ?? $arr['PROMO_TYPE'],
                'start_date' => $arr['sdate'] ?? $arr['SDATE'],
                'end_date' => $arr['edate'] ?? $arr['EDATE'],
                'active' => (bool) ($arr['active_flag'] ?? $arr['ACTIVE_FLAG']),
            ];
        }
    }

    public function loadProducts(): void
    {
        $rows = DB::select("SELECT RAWTOHEX(PRODUCT_ID) AS ID, PRODUCT_NAME FROM ODAF.PRODUCT WHERE ACTIVE_FLAG = 1 ORDER BY PRODUCT_NAME");
        $this->products = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $this->products[] = [
                'id' => $arr['id'] ?? $arr['ID'],
                'name' => $arr['product_name'] ?? $arr['PRODUCT_NAME'],
            ];
        }
    }

    public function createPromo(): void
    {
        $this->formMode = 'INSERT';
        $this->promoId = null;
        $this->promoCode = 'PRM-' . strtoupper(substr(uniqid(), -5));
        $this->promoName = '';
        $this->promoType = 'BOGO';
        $this->productId = null;
        $this->startDate = date('Y-m-d');
        $this->endDate = date('Y-m-d', strtotime('+1 month'));
        $this->buyQty = 2;
        $this->getQty = 1;
        $this->discountPct = 0;
        $this->tieredConfig = [['qty' => 2, 'disc' => 5], ['qty' => 5, 'disc' => 10]];
        $this->activeFlag = true;
        $this->showForm = true;
    }

    public function editPromo(string $id): void
    {
        $r = DB::selectOne("
            SELECT PROMO_CODE, PROMO_NAME, PROMO_TYPE, RAWTOHEX(PRODUCT_ID) AS PROD_ID,
                   TO_CHAR(START_DATE, 'YYYY-MM-DD') AS SDATE, 
                   TO_CHAR(END_DATE, 'YYYY-MM-DD') AS EDATE,
                   BUY_QTY, GET_QTY, DISCOUNT_PCT, TIERED_CONFIG, ACTIVE_FLAG
            FROM ODAF.POS_PROMO WHERE OBJECT_ID = HEXTORAW(?)
        ", [$id]);
        
        $arr = (array) ($r ?? []);
        if (empty($arr)) return;

        $this->formMode = 'UPDATE';
        $this->promoId = $id;
        $this->promoCode = $arr['promo_code'] ?? $arr['PROMO_CODE'];
        $this->promoName = $arr['promo_name'] ?? $arr['PROMO_NAME'];
        $this->promoType = $arr['promo_type'] ?? $arr['PROMO_TYPE'];
        $this->productId = $arr['prod_id'] ?? $arr['PROD_ID'];
        $this->startDate = $arr['sdate'] ?? $arr['SDATE'];
        $this->endDate = $arr['edate'] ?? $arr['EDATE'];
        $this->buyQty = (int) ($arr['buy_qty'] ?? $arr['BUY_QTY']);
        $this->getQty = (int) ($arr['get_qty'] ?? $arr['GET_QTY']);
        $this->discountPct = (float) ($arr['discount_pct'] ?? $arr['DISCOUNT_PCT']);
        
        $tiered = $arr['tiered_config'] ?? $arr['TIERED_CONFIG'];
        if ($tiered && is_resource($tiered)) $tiered = stream_get_contents($tiered);
        $this->tieredConfig = $tiered ? json_decode($tiered, true) : [['qty' => 2, 'disc' => 5]];
        if (!is_array($this->tieredConfig)) $this->tieredConfig = [['qty' => 2, 'disc' => 5]];
        
        $this->activeFlag = (bool) ($arr['active_flag'] ?? $arr['ACTIVE_FLAG']);
        
        $this->showForm = true;
    }

    public function addTier(): void
    {
        $this->tieredConfig[] = ['qty' => 0, 'disc' => 0];
    }

    public function removeTier(int $idx): void
    {
        unset($this->tieredConfig[$idx]);
        $this->tieredConfig = array_values($this->tieredConfig);
    }

    public function savePromo(): void
    {
        // Validation minimal
        if (trim($this->promoName) === '' || trim($this->promoCode) === '') return;

        $pid = $this->productId ?: null;
        $tieredJson = json_encode($this->tieredConfig);
        $act = $this->activeFlag ? 1 : 0;

        if ($this->formMode === 'INSERT') {
            DB::insert("
                INSERT INTO ODAF.POS_PROMO (
                    OBJECT_ID, PROMO_CODE, PROMO_NAME, PROMO_TYPE, 
                    START_DATE, END_DATE, PRODUCT_ID,
                    BUY_QTY, GET_QTY, DISCOUNT_PCT, TIERED_CONFIG, ACTIVE_FLAG
                ) VALUES (
                    SYS_GUID(), ?, ?, ?, 
                    TO_TIMESTAMP(?,'YYYY-MM-DD'), TO_TIMESTAMP(?,'YYYY-MM-DD'), 
                    HEXTORAW(?), ?, ?, ?, ?, ?
                )
            ", [
                $this->promoCode, $this->promoName, $this->promoType,
                $this->startDate, $this->endDate, 
                $pid, $this->buyQty, $this->getQty, $this->discountPct, $tieredJson, $act
            ]);
        } else {
            DB::update("
                UPDATE ODAF.POS_PROMO SET
                    PROMO_NAME = ?, PROMO_TYPE = ?,
                    START_DATE = TO_TIMESTAMP(?,'YYYY-MM-DD'), END_DATE = TO_TIMESTAMP(?,'YYYY-MM-DD'),
                    PRODUCT_ID = HEXTORAW(?), BUY_QTY = ?, GET_QTY = ?,
                    DISCOUNT_PCT = ?, TIERED_CONFIG = ?, ACTIVE_FLAG = ?, UPDATED_AT = SYSTIMESTAMP
                WHERE OBJECT_ID = HEXTORAW(?)
            ", [
                $this->promoName, $this->promoType,
                $this->startDate, $this->endDate,
                $pid, $this->buyQty, $this->getQty,
                $this->discountPct, $tieredJson, $act, $this->promoId
            ]);
        }

        DB::statement('COMMIT');
        $this->showForm = false;
        $this->loadPromos();
    }

    public function render()
    {
        return view('livewire.runtime.promo-manager');
    }
}
