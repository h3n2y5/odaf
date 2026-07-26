<div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    
    {{-- ═══ Header ═══ --}}
    <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <h1 class="font-bold">ODAF Mobile Inventory</h1>
        </div>
        <a href="/" class="p-1 hover:bg-indigo-500 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
    </div>

    {{-- ═══ Flash Messages ═══ --}}
    @if (session('success'))
        <div class="m-4 p-3 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg text-sm text-center shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="m-4 p-3 bg-rose-100 border border-rose-300 text-rose-800 rounded-lg text-sm text-center shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- ═══ MODE: SCANNING ═══ --}}
    <div x-show="$wire.mode === 'SCANNING'" class="flex-1 flex flex-col" style="display: {{ $mode === 'SCANNING' ? 'flex' : 'none' }}">
        
        <div class="p-4 text-center">
            <h2 class="font-bold text-slate-800 text-lg">Scan Barcode Produk</h2>
            <p class="text-sm text-slate-500 mt-1">Arahkan kamera ke barcode untuk update stok atau tambah barang baru.</p>
        </div>

        <div class="flex-1 px-4 pb-4 flex flex-col items-center justify-center">
            <div id="reader" class="w-full max-w-sm rounded-xl overflow-hidden shadow-lg border-4 border-slate-200 bg-black aspect-square"></div>
            
            <div class="mt-6 w-full max-w-sm">
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex-1 h-px bg-slate-300"></div>
                    <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">ATAU INPUT MANUAL</span>
                    <div class="flex-1 h-px bg-slate-300"></div>
                </div>
                <div class="flex gap-2">
                    <input type="text" wire:model="barcode" wire:keydown.enter="checkBarcode" placeholder="Ketik Barcode/Kode..." class="flex-1 rounded-xl border-slate-300 py-3 px-4 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <button wire:click="checkBarcode" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 rounded-xl font-bold transition-colors">CEK</button>
                </div>
            </div>
        </div>

        <script src="https://unpkg.com/html5-qrcode"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                let html5QrcodeScanner = null;

                function initScanner() {
                    if (html5QrcodeScanner) return;
                    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 150}, aspectRatio: 1.0 });
                    
                    html5QrcodeScanner.render(function(decodedText) {
                        // Hentikan scanner sementara, kirim ke Livewire
                        html5QrcodeScanner.clear();
                        html5QrcodeScanner = null;
                        @this.onBarcodeDetected(decodedText);
                    }, function(error) {
                        // ignore scan errors
                    });
                }

                // Initialize when shown
                Livewire.hook('morph.updated', ({ component }) => {
                    if (component.el.querySelector('[x-show="$wire.mode === \'SCANNING\'"]').style.display !== 'none') {
                        setTimeout(initScanner, 300);
                    }
                });

                // Init on first load
                if (@this.mode === 'SCANNING') {
                    initScanner();
                }
            });
        </script>
        <style>
            /* Make html5-qrcode look better on mobile */
            #reader button { background: #4f46e5; color: white; border: none; padding: 8px 16px; border-radius: 8px; margin: 5px; font-weight: bold; }
            #reader select { padding: 8px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 10px; width: 100%; }
            #reader__dashboard_section_csr span { display: none; } /* hide 'or drop an image' */
        </style>
    </div>

    {{-- ═══ MODE: FORM (Insert/Update) ═══ --}}
    <div x-show="$wire.mode === 'FORM'" class="flex-1 overflow-y-auto" style="display: {{ $mode === 'FORM' ? 'block' : 'none' }}">
        
        <div class="bg-indigo-50 border-b border-indigo-100 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-indigo-500 tracking-wider uppercase mb-1">{{ $formMode === 'INSERT' ? 'PRODUK BARU' : 'STOCK OPNAME' }}</div>
                    <div class="text-lg font-mono font-bold text-slate-800">{{ $barcode }}</div>
                </div>
                <button wire:click="cancel" class="text-sm font-bold text-slate-500 hover:text-slate-700 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">Batal</button>
            </div>
        </div>

        <div class="p-4 space-y-4 max-w-lg mx-auto pb-24">
            
            {{-- Foto Produk --}}
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 text-center">
                <label class="block text-sm font-bold text-slate-700 mb-3 text-left">Foto Produk</label>
                
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" class="w-full max-w-[200px] h-auto mx-auto rounded-lg shadow-sm border border-slate-100 mb-3">
                @elseif ($photoPath)
                    <img src="{{ $photoPath }}" class="w-full max-w-[200px] h-auto mx-auto rounded-lg shadow-sm border border-slate-100 mb-3">
                @else
                    <div class="w-full max-w-[200px] aspect-square bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl mx-auto mb-3 flex flex-col items-center justify-center text-slate-400">
                        <svg class="w-12 h-12 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-xs">Belum ada foto</span>
                    </div>
                @endif
                
                <div class="relative overflow-hidden inline-block w-full">
                    <button class="w-full bg-indigo-50 text-indigo-700 font-bold py-2.5 px-4 rounded-lg border border-indigo-200 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Buka Kamera / Pilih Foto
                    </button>
                    <input type="file" wire:model="photo" accept="image/*" capture="environment" class="absolute left-0 top-0 opacity-0 w-full h-full cursor-pointer">
                </div>
                <div wire:loading wire:target="photo" class="text-xs text-indigo-600 mt-2 font-medium animate-pulse">Mengunggah foto...</div>
            </div>

            {{-- Informasi Dasar --}}
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nama Produk <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="productName" class="w-full rounded-lg border-slate-300 py-2.5 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kode Internal <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="productCode" class="w-full rounded-lg border-slate-300 py-2.5 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kategori</label>
                        <input type="text" wire:model="category" class="w-full rounded-lg border-slate-300 py-2.5 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Harga Jual (Rp)</label>
                    <input type="number" wire:model="unitPrice" class="w-full rounded-lg border-slate-300 py-2.5 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" inputmode="numeric">
                </div>
            </div>

            {{-- Stock Opname --}}
            <div class="bg-emerald-50 p-4 rounded-xl shadow-sm border border-emerald-200">
                <label class="block text-sm font-bold text-emerald-800 mb-2">Jumlah Fisik (Stock Opname)</label>
                <div class="flex items-center justify-between gap-4">
                    <button wire:click="$set('stockQty', stockQty - 1)" class="w-12 h-12 bg-white rounded-xl shadow-sm border border-slate-200 text-2xl font-bold text-slate-600 active:bg-slate-100 flex items-center justify-center">−</button>
                    <input type="number" wire:model="stockQty" class="flex-1 rounded-xl border-slate-300 py-3 text-center text-xl font-bold shadow-sm focus:ring-emerald-500 focus:border-emerald-500" inputmode="numeric">
                    <button wire:click="$set('stockQty', stockQty + 1)" class="w-12 h-12 bg-emerald-600 rounded-xl shadow-sm border border-emerald-600 text-2xl font-bold text-white active:bg-emerald-700 flex items-center justify-center">+</button>
                </div>
                @if($formMode === 'UPDATE')
                    <p class="text-xs text-emerald-600 text-center mt-3">Sesuaikan jumlah fisik barang dengan yang ada di aplikasi.</p>
                @endif
            </div>

        </div>

        {{-- Bottom Action Bar --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 p-4 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
            <button wire:click="save" class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] transition-transform text-white font-bold py-3.5 px-4 rounded-xl shadow-lg flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                SIMPAN & LANJUT SCAN
            </button>
        </div>
    </div>
</div>
