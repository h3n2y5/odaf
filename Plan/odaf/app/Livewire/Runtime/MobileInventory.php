<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

/**
 * Mobile Inventory (Stock Opname)
 *
 * Halaman mobile-friendly untuk mendaftarkan barang baru (Master Data)
 * dan melakukan update stok fisik (Stock Opname) melalui scan barcode kamera.
 */
#[Layout('layouts.odaf')]
final class MobileInventory extends Component
{
    use WithFileUploads;

    // ─── Mode & State ────────────────────────────────────────────────
    /** @var 'SCANNING' | 'FORM' */
    public string $mode = 'SCANNING';
    
    /** @var 'INSERT' | 'UPDATE' */
    public string $formMode = 'INSERT';

    // ─── Form Fields ─────────────────────────────────────────────────
    public ?string $productId = null;
    public string $barcode = '';
    public string $productCode = '';
    public string $productName = '';
    public float $unitPrice = 0;
    public int $stockQty = 0;
    public string $category = 'Umum';
    public ?string $photoPath = null;
    
    /** File upload instance */
    public $photo;

    // ─── Scanner Handler ─────────────────────────────────────────────

    /**
     * Dipanggil dari JavaScript saat barcode terdeteksi oleh kamera.
     */
    public function onBarcodeDetected(string $barcode): void
    {
        $this->barcode = trim($barcode);
        $this->checkBarcode();
    }

    public function checkBarcode(): void
    {
        if ($this->barcode === '') return;

        // Cari produk berdasarkan BARCODE (atau PRODUCT_CODE sebagai fallback)
        $p = collect(DB::select("
            SELECT RAWTOHEX(PRODUCT_ID) AS ID, PRODUCT_CODE, PRODUCT_NAME, 
                   UNIT_PRICE, STOCK_QTY, CATEGORY, BARCODE, PHOTO_PATH
            FROM ODAF.PRODUCT 
            WHERE (BARCODE = ? OR PRODUCT_CODE = ?) AND DELETED_AT IS NULL
        ", [$this->barcode, $this->barcode]))->first();

        $pArr = (array) ($p ?? []);

        if (!empty($pArr)) {
            // Produk ditemukan -> Update mode (Stock Opname)
            $this->formMode = 'UPDATE';
            $this->productId = $pArr['id'] ?? $pArr['ID'];
            $this->productCode = $pArr['product_code'] ?? $pArr['PRODUCT_CODE'];
            $this->productName = $pArr['product_name'] ?? $pArr['PRODUCT_NAME'];
            $this->unitPrice = (float) ($pArr['unit_price'] ?? $pArr['UNIT_PRICE']);
            $this->stockQty = (int) ($pArr['stock_qty'] ?? $pArr['STOCK_QTY']);
            $this->category = $pArr['category'] ?? $pArr['CATEGORY'] ?? 'Umum';
            $this->photoPath = $pArr['photo_path'] ?? $pArr['PHOTO_PATH'];
        } else {
            // Produk belum ada -> Insert mode
            $this->formMode = 'INSERT';
            $this->productId = null;
            // Gunakan barcode sebagai awalan kode produk bila belum ada
            $this->productCode = 'PRD-' . substr($this->barcode, -6); 
            $this->productName = '';
            $this->unitPrice = 0;
            $this->stockQty = 0;
            $this->category = 'Umum';
            $this->photoPath = null;
        }

        $this->photo = null;
        $this->mode = 'FORM';
    }

    // ─── Actions ─────────────────────────────────────────────────────

    public function cancel(): void
    {
        $this->mode = 'SCANNING';
        $this->barcode = '';
    }

    public function save(): void
    {
        // Validasi dasar
        if (trim($this->productName) === '' || trim($this->productCode) === '') {
            session()->flash('error', 'Nama dan Kode Produk harus diisi!');
            return;
        }

        // Upload foto jika ada
        $finalPhotoPath = $this->photoPath;
        if ($this->photo) {
            $fileName = Str::random(20) . '.' . $this->photo->getClientOriginalExtension();
            $path = $this->photo->storeAs('products', $fileName, 'public');
            $finalPhotoPath = '/storage/' . $path;
        }

        if ($this->formMode === 'INSERT') {
            DB::insert("
                INSERT INTO ODAF.PRODUCT (
                    PRODUCT_ID, PRODUCT_CODE, PRODUCT_NAME, BARCODE, 
                    UNIT_PRICE, STOCK_QTY, CATEGORY, PHOTO_PATH, VERSION_NO
                ) VALUES (
                    SYS_GUID(), ?, ?, ?, ?, ?, ?, ?, 1
                )
            ", [
                $this->productCode, $this->productName, $this->barcode,
                $this->unitPrice, $this->stockQty, $this->category, $finalPhotoPath
            ]);
            
            session()->flash('success', 'Produk berhasil didaftarkan!');
        } else {
            DB::update("
                UPDATE ODAF.PRODUCT SET 
                    PRODUCT_NAME = ?, BARCODE = ?, UNIT_PRICE = ?, 
                    STOCK_QTY = ?, CATEGORY = ?, PHOTO_PATH = ?, 
                    UPDATED_AT = SYSTIMESTAMP, VERSION_NO = VERSION_NO + 1
                WHERE PRODUCT_ID = HEXTORAW(?)
            ", [
                $this->productName, $this->barcode, $this->unitPrice,
                $this->stockQty, $this->category, $finalPhotoPath,
                $this->productId
            ]);
            
            session()->flash('success', 'Data produk & stok berhasil diperbarui!');
        }

        DB::statement('COMMIT');
        
        $this->cancel();
    }

    public function render()
    {
        return view('livewire.runtime.mobile-inventory');
    }
}
