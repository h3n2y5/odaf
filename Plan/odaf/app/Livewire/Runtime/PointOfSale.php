<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Point of Sale (POS) — Frontend Kasir.
 *
 * Touch-friendly cashier interface with product grid, shopping cart,
 * payment processing, and receipt printing.
 */
#[Layout('layouts.odaf')]
final class PointOfSale extends Component
{
    // ─── Search & Filter ─────────────────────────────────────────────
    public string $search = '';
    public string $categoryFilter = 'ALL';

    // ─── Cart ────────────────────────────────────────────────────────
    /** @var array<int, array{id: string, code: string, name: string, price: float, qty: int, total: float}> */
    public array $cart = [];

    // ─── Payment Modal ───────────────────────────────────────────────
    public bool $showPayment = false;
    public string $paymentMethod = 'CASH';
    public string $amountPaidInput = '';
    public string $discount = '0';

    // ─── Receipt / Post-payment ──────────────────────────────────────
    public bool $showReceipt = false;
    public ?string $lastTrxNo = null;
    public float $lastChange = 0;

    // ─── History ─────────────────────────────────────────────────────
    public bool $showHistory = false;

    public function mount(): void
    {
        $this->loadPromos();
    }

    // ─── Product Queries ─────────────────────────────────────────────

    public function getProductsProperty(): array
    {
        $sql = "SELECT RAWTOHEX(PRODUCT_ID) AS ID, PRODUCT_CODE, PRODUCT_NAME, UNIT_PRICE, STOCK_QTY, CATEGORY, BARCODE
                FROM ODAF.PRODUCT
                WHERE ACTIVE_FLAG = 1 AND DELETED_AT IS NULL";
        $binds = [];

        if ($this->search !== '') {
            $sql .= " AND (UPPER(PRODUCT_NAME) LIKE UPPER(?) OR UPPER(PRODUCT_CODE) LIKE UPPER(?) OR UPPER(BARCODE) = UPPER(?))";
            $binds[] = "%{$this->search}%";
            $binds[] = "%{$this->search}%";
            $binds[] = $this->search;
        }

        if ($this->categoryFilter !== 'ALL') {
            $sql .= " AND CATEGORY = ?";
            $binds[] = $this->categoryFilter;
        }

        $sql .= " ORDER BY PRODUCT_NAME";

        $rows = DB::select($sql, $binds);
        $out = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $out[] = [
                'id' => $arr['id'] ?? $arr['ID'],
                'code' => $arr['product_code'] ?? $arr['PRODUCT_CODE'],
                'name' => $arr['product_name'] ?? $arr['PRODUCT_NAME'],
                'price' => (float) ($arr['unit_price'] ?? $arr['UNIT_PRICE']),
                'stock' => (int) ($arr['stock_qty'] ?? $arr['STOCK_QTY']),
                'category' => $arr['category'] ?? $arr['CATEGORY'] ?? '',
                'barcode' => $arr['barcode'] ?? $arr['BARCODE'] ?? null,
            ];
        }
        return $out;
    }

    public function getCategoriesProperty(): array
    {
        $rows = DB::select("SELECT DISTINCT CATEGORY FROM ODAF.PRODUCT WHERE ACTIVE_FLAG = 1 AND CATEGORY IS NOT NULL ORDER BY CATEGORY");
        $cats = ['ALL'];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $cats[] = $arr['category'] ?? $arr['CATEGORY'];
        }
        return $cats;
    }

    // ─── Cart Actions ────────────────────────────────────────────────

    public function addToCart(string $productId): void
    {
        // Check if already in cart
        foreach ($this->cart as $idx => $item) {
            if ($item['id'] === $productId) {
                $this->cart[$idx]['qty']++;
                $this->cart[$idx]['total'] = $this->cart[$idx]['price'] * $this->cart[$idx]['qty'];
                return;
            }
        }

        // Find product
        $p = collect($this->products)->firstWhere('id', $productId);
        if (!$p) return;

        $this->cart[] = [
            'id' => $p['id'],
            'code' => $p['code'],
            'name' => $p['name'],
            'price' => $p['price'],
            'qty' => 1,
            'total' => $p['price'],
            'promo_discount' => 0,
            'promo_name' => null,
        ];
        
        $this->evaluatePromos();
    }

    public function addByCode(string $code): void
    {
        $code = strtoupper(trim($code));
        $p = collect($this->products)->first(fn($p) => strtoupper($p['code']) === $code || strtoupper($p['barcode'] ?? '') === $code);
        if ($p) {
            $this->addToCart($p['id']);
        }
    }

    public function handleBarcodeSearch(): void
    {
        if (trim($this->search) === '') return;

        $term = strtoupper(trim($this->search));
        $p = collect($this->products)->first(fn($p) => strtoupper($p['code']) === $term || strtoupper($p['barcode'] ?? '') === $term);

        if ($p) {
            $this->addToCart($p['id']);
            $this->search = ''; // Kosongkan input setelah berhasil discan
        } else {
            // Jika ada banyak hasil dari search nama, tidak otomatis masuk
            // Tapi jika cuma 1 hasil, masukkan saja.
            if (count($this->products) === 1) {
                $this->addToCart($this->products[0]['id']);
                $this->search = '';
            }
        }
    }
    public array $activePromos = [];

    public function loadPromos(): void
    {
        $rows = DB::select("
            SELECT RAWTOHEX(OBJECT_ID) AS ID, PROMO_CODE, PROMO_NAME, PROMO_TYPE,
                   RAWTOHEX(PRODUCT_ID) AS PROD_ID, BUY_QTY, GET_QTY, DISCOUNT_PCT, TIERED_CONFIG
            FROM ODAF.POS_PROMO
            WHERE ACTIVE_FLAG = 1 
              AND STATUS = 'APPROVED'
              AND SYSTIMESTAMP BETWEEN START_DATE AND END_DATE
        ");
        
        $this->activePromos = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $tiered = $arr['tiered_config'] ?? $arr['TIERED_CONFIG'];
            if ($tiered && is_resource($tiered)) $tiered = stream_get_contents($tiered);
            
            $this->activePromos[] = [
                'name' => $arr['promo_name'] ?? $arr['PROMO_NAME'],
                'type' => $arr['promo_type'] ?? $arr['PROMO_TYPE'],
                'product_id' => $arr['prod_id'] ?? $arr['PROD_ID'],
                'buy_qty' => (int) ($arr['buy_qty'] ?? $arr['BUY_QTY']),
                'get_qty' => (int) ($arr['get_qty'] ?? $arr['GET_QTY']),
                'discount_pct' => (float) ($arr['discount_pct'] ?? $arr['DISCOUNT_PCT']),
                'tiered' => $tiered ? json_decode($tiered, true) : [],
            ];
        }
    }

    private function evaluatePromos(): void
    {
        foreach ($this->cart as $idx => $item) {
            $qty = $item['qty'];
            $price = $item['price'];
            $prodId = $item['id'];
            
            $bestDiscount = 0;
            $bestPromoName = null;

            foreach ($this->activePromos as $promo) {
                // Check if applies
                if (!empty($promo['product_id']) && $promo['product_id'] !== $prodId) continue;

                $discount = 0;
                if ($promo['type'] === 'BOGO' && $promo['buy_qty'] > 0 && $promo['get_qty'] > 0) {
                    $setQty = $promo['buy_qty'] + $promo['get_qty'];
                    $freeQty = floor($qty / $setQty) * $promo['get_qty'];
                    $discount = $freeQty * $price;
                } elseif ($promo['type'] === 'DISC_PCT' && $promo['discount_pct'] > 0) {
                    $discount = $qty * $price * ($promo['discount_pct'] / 100);
                } elseif ($promo['type'] === 'TIERED' && !empty($promo['tiered'])) {
                    // Sort tiered by qty desc
                    $tiers = $promo['tiered'];
                    usort($tiers, fn($a, $b) => $b['qty'] <=> $a['qty']);
                    foreach ($tiers as $tier) {
                        if ($qty >= $tier['qty']) {
                            $discount = $qty * $price * ($tier['disc'] / 100);
                            break;
                        }
                    }
                }

                if ($discount > $bestDiscount) {
                    $bestDiscount = $discount;
                    $bestPromoName = $promo['name'];
                }
            }

            $this->cart[$idx]['promo_discount'] = $bestDiscount;
            $this->cart[$idx]['promo_name'] = $bestPromoName;
            $this->cart[$idx]['total'] = ($price * $qty) - $bestDiscount;
        }
    }

    public function incrementQty(int $index): void
    {
        if (!isset($this->cart[$index])) return;
        $this->cart[$index]['qty']++;
        $this->evaluatePromos();
    }

    public function decrementQty(int $index): void
    {
        if (!isset($this->cart[$index])) return;
        if ($this->cart[$index]['qty'] <= 1) {
            $this->removeFromCart($index);
            return;
        }
        $this->cart[$index]['qty']--;
        $this->evaluatePromos();
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->evaluatePromos();
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    // ─── Calculations ────────────────────────────────────────────────

    public function getSubtotalProperty(): float
    {
        return array_sum(array_column($this->cart, 'total'));
    }

    public function getDiscountAmountProperty(): float
    {
        return (float) $this->discount;
    }

    public function getGrandTotalProperty(): float
    {
        return max(0, $this->subtotal - $this->discountAmount);
    }

    public function getAmountPaidProperty(): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $this->amountPaidInput ?: '0');
    }

    public function getChangeAmountProperty(): float
    {
        return max(0, $this->amountPaid - $this->grandTotal);
    }

    // ─── Payment ─────────────────────────────────────────────────────

    public function openPayment(): void
    {
        if (empty($this->cart)) return;
        $this->showPayment = true;
        $this->amountPaidInput = '';
        $this->paymentMethod = 'CASH';
    }

    public function closePayment(): void
    {
        $this->showPayment = false;
    }

    public function setQuickPay(int $amount): void
    {
        $this->amountPaidInput = (string) $amount;
    }

    public function setExactPay(): void
    {
        $this->amountPaidInput = (string) $this->grandTotal;
    }

    public function processPayment(): void
    {
        if (empty($this->cart)) return;

        $paid = $this->amountPaid;
        $total = $this->grandTotal;

        if ($this->paymentMethod === 'CASH' && $paid < $total) {
            session()->flash('pos_error', 'Jumlah bayar kurang dari total!');
            return;
        }

        // Generate TRX_NO
        $today = now()->format('Ymd');
        $lastNo = DB::selectOne("SELECT MAX(TRX_NO) AS LAST_NO FROM ODAF.POS_TRANSACTION WHERE TRX_NO LIKE ?", ["POS-{$today}-%"]);
        $lastArr = (array) ($lastNo ?? []);
        $lastVal = $lastArr['last_no'] ?? $lastArr['LAST_NO'] ?? null;
        $seq = 1;
        if ($lastVal) {
            $parts = explode('-', $lastVal);
            $seq = ((int) end($parts)) + 1;
        }
        $trxNo = sprintf('POS-%s-%04d', $today, $seq);

        $user = Auth::user();
        $userId = $user ? $user->getAuthIdentifier() : null;
        $userName = $user ? ($user->object_name ?? $user->OBJECT_NAME ?? 'Kasir') : 'Kasir';

        $change = $this->paymentMethod === 'CASH' ? ($paid - $total) : 0;

        // Insert transaction header
        DB::insert("
            INSERT INTO ODAF.POS_TRANSACTION (
                OBJECT_ID, TRX_NO, TRX_DATE, CASHIER_ID, CASHIER_NAME,
                SUBTOTAL, DISCOUNT_AMOUNT, TOTAL_AMOUNT,
                PAYMENT_METHOD, AMOUNT_PAID, CHANGE_AMOUNT, STATUS
            ) VALUES (
                SYS_GUID(), ?, SYSTIMESTAMP, HEXTORAW(?), ?,
                ?, ?, ?,
                ?, ?, ?, 'COMPLETED'
            )
        ", [
            $trxNo, $userId, $userName,
            $this->subtotal, $this->discountAmount, $total,
            $this->paymentMethod, $paid, $change,
        ]);

        // Get the transaction ID
        $trx = collect(DB::select("SELECT RAWTOHEX(OBJECT_ID) AS ID FROM ODAF.POS_TRANSACTION WHERE TRX_NO = ?", [$trxNo]))->first();
        $trxId = $trx ? ((array) $trx)['id'] ?? ((array) $trx)['ID'] : null;

        if ($trxId) {
            // Insert items
            foreach ($this->cart as $item) {
                DB::insert("
                    INSERT INTO ODAF.POS_TRANSACTION_ITEM (
                        OBJECT_ID, TRANSACTION_ID, PRODUCT_ID, PRODUCT_CODE, PRODUCT_NAME,
                        UNIT_PRICE, QTY, LINE_TOTAL
                    ) VALUES (
                        SYS_GUID(), HEXTORAW(?), HEXTORAW(?), ?, ?,
                        ?, ?, ?
                    )
                ", [
                    $trxId, $item['id'], $item['code'], $item['name'],
                    $item['price'], $item['qty'], $item['total'],
                ]);

                // Decrement stock
                DB::update("UPDATE ODAF.PRODUCT SET STOCK_QTY = STOCK_QTY - ? WHERE PRODUCT_ID = HEXTORAW(?)", [
                    $item['qty'], $item['id'],
                ]);
            }
        }

        DB::statement('COMMIT');

        $this->lastTrxNo = $trxNo;
        $this->lastChange = $change;
        $this->showPayment = false;
        $this->showReceipt = true;
        $this->cart = [];
        $this->discount = '0';
        $this->amountPaidInput = '';
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->lastTrxNo = null;
    }

    // ─── History ─────────────────────────────────────────────────────

    public function getTodayTransactionsProperty(): array
    {
        $rows = DB::select("
            SELECT TRX_NO, CASHIER_NAME, TOTAL_AMOUNT, PAYMENT_METHOD, AMOUNT_PAID, CHANGE_AMOUNT,
                   TO_CHAR(TRX_DATE, 'HH24:MI') AS TRX_TIME
            FROM ODAF.POS_TRANSACTION
            WHERE TRUNC(TRX_DATE) = TRUNC(SYSTIMESTAMP)
            ORDER BY TRX_DATE DESC
        ");
        $out = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            $out[] = [
                'trx_no' => $arr['trx_no'] ?? $arr['TRX_NO'],
                'cashier' => $arr['cashier_name'] ?? $arr['CASHIER_NAME'],
                'total' => (float) ($arr['total_amount'] ?? $arr['TOTAL_AMOUNT']),
                'method' => $arr['payment_method'] ?? $arr['PAYMENT_METHOD'],
                'time' => $arr['trx_time'] ?? $arr['TRX_TIME'],
            ];
        }
        return $out;
    }

    public function getTodayTotalProperty(): float
    {
        $r = DB::selectOne("SELECT NVL(SUM(TOTAL_AMOUNT),0) AS T FROM ODAF.POS_TRANSACTION WHERE TRUNC(TRX_DATE) = TRUNC(SYSTIMESTAMP)");
        $arr = (array) ($r ?? []);
        return (float) ($arr['t'] ?? $arr['T'] ?? 0);
    }

    public function render()
    {
        return view('livewire.runtime.point-of-sale');
    }
}
