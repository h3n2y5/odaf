<div class="min-h-screen bg-slate-50 font-sans pb-20">
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Promo Profit Simulator</h1>
                <p class="text-xs text-slate-500">Kalkulasi Cerdas (Volume & Stok) untuk menyetujui sebuah Campaign Promo.</p>
            </div>
        </div>
        <a href="{{ route('odaf.promo') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Kembali ke Master Promo</a>
    </div>

    @if (session('success'))
        <div class="max-w-7xl mx-auto mt-4 px-6">
            <div class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-xl text-sm shadow-sm flex justify-between items-center">
                {{ session('success') }}
                <a href="/app/POS_APP/g/PAGE_POS_PROMO" class="underline font-bold hover:text-emerald-900">Buka Grid Promo</a>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-7xl mx-auto mt-4 px-6">
            <div class="p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-xl text-sm shadow-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="p-6 max-w-7xl mx-auto space-y-6">
        
        {{-- Product & Scenario Selection --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-wrap lg:flex-nowrap items-center gap-8">
            
            <div class="flex-1 w-full min-w-[250px]">
                <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Produk</label>
                <select wire:model.live="selectedProductId" class="w-full rounded-lg border-slate-300 shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-medium py-3">
                    <option value="">-- Pilih Produk dari Master --</option>
                    @foreach($products as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-px h-16 bg-slate-200 hidden lg:block"></div>
            
            <div class="flex-1 w-full min-w-[250px] grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Tgl Mulai</label>
                    <input type="date" wire:model.live="startDate" class="w-full text-sm border-slate-300 rounded-lg shadow-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Tgl Berakhir</label>
                    <input type="date" wire:model.live="endDate" class="w-full text-sm border-slate-300 rounded-lg shadow-sm">
                </div>
            </div>

            <div class="w-px h-16 bg-slate-200 hidden lg:block"></div>

            <div class="text-right shrink-0">
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Stok Gudang Saat Ini</label>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stockQty) }} <span class="text-sm font-normal text-slate-500">pcs</span></div>
            </div>
        </div>

        {{-- Base Values --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-center gap-8">
            <div class="text-center">
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">Harga Beli Normal (Modal)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                    <input type="number" wire:model.live.debounce.300ms="baseCost" class="w-48 pl-9 pr-4 py-3 text-xl font-bold rounded-lg border-slate-300 shadow-sm focus:ring-amber-500 focus:border-amber-500 text-center bg-slate-50" {{ $selectedProductId ? 'disabled' : '' }}>
                </div>
            </div>
            <div class="w-px h-16 bg-slate-200"></div>
            <div class="text-center">
                <label class="block text-sm font-bold text-slate-500 mb-2 uppercase tracking-wider">Harga Jual Normal</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                    <input type="number" wire:model.live.debounce.300ms="basePrice" class="w-48 pl-9 pr-4 py-3 text-xl font-bold rounded-lg border-slate-300 shadow-sm focus:ring-amber-500 focus:border-amber-500 text-center bg-slate-50" {{ $selectedProductId ? 'disabled' : '' }}>
                </div>
            </div>
            <div class="w-px h-16 bg-slate-200"></div>
            <div class="text-center">
                <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Profit Normal</label>
                <div class="text-2xl font-bold text-slate-800" x-text="formatRp({{ $normalProfit }})"></div>
                <div class="text-sm font-bold text-slate-500">{{ number_format($normalMargin, 2) }}% Margin</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- Supplier Promo Panel --}}
            <div class="bg-white rounded-xl shadow-sm border border-emerald-200 overflow-hidden relative flex flex-col">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                <div class="p-5 border-b border-slate-100 bg-emerald-50/50">
                    <h2 class="font-bold text-emerald-800 text-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Sisi Inbound (Promo Supplier)
                    </h2>
                    <p class="text-xs text-emerald-600 mt-1">Skenario jika supplier menurunkan Harga Modal.</p>
                </div>
                <div class="p-6 space-y-5 flex-1">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Promo dari Supplier</label>
                        <select wire:model.live="suppPromoType" class="w-full rounded-lg border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium">
                            <option value="NONE">Tidak Ada Promo Supplier</option>
                            <option value="BOGO">Beli X Gratis Y (Ekstra Barang)</option>
                            <option value="DISC">Diskon Persentase (%)</option>
                        </select>
                    </div>

                    @if($suppPromoType === 'BOGO')
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Jika Beli</label>
                                <input type="number" wire:model.live.debounce.300ms="suppBuyQty" class="w-full rounded-md border-slate-300 shadow-sm text-center font-bold">
                            </div>
                            <div class="text-lg font-bold text-slate-300 mt-5">+</div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Dapat Gratis</label>
                                <input type="number" wire:model.live.debounce.300ms="suppGetQty" class="w-full rounded-md border-slate-300 shadow-sm text-center font-bold text-emerald-600">
                            </div>
                        </div>
                    @elseif($suppPromoType === 'DISC')
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Diskon Sebesar</label>
                            <div class="relative w-32">
                                <input type="number" wire:model.live.debounce.300ms="suppDiscPct" class="w-full rounded-md border-slate-300 shadow-sm pr-8 text-right font-bold text-emerald-600">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">%</span>
                            </div>
                        </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Catatan Khusus Sisi Supplier</label>
                        <input type="text" wire:model="suppPromoDesc" placeholder="Maks. pembelian, syarat pembayaran, dll" class="w-full text-sm border-slate-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="mt-auto pt-6 border-t border-slate-100">
                        <div class="text-sm font-medium text-slate-500">Effective Cost (Modal Riil / pcs):</div>
                        <div class="text-3xl font-bold text-emerald-700 mt-1" x-text="formatRp({{ $effCost }})"></div>
                    </div>
                </div>
            </div>

            {{-- Customer Promo Panel --}}
            <div class="bg-white rounded-xl shadow-sm border border-rose-200 overflow-hidden relative flex flex-col">
                <div class="absolute top-0 left-0 w-1 h-full bg-rose-500"></div>
                <div class="p-5 border-b border-slate-100 bg-rose-50/50">
                    <h2 class="font-bold text-rose-800 text-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                        Sisi Outbound (Promo Customer)
                    </h2>
                    <p class="text-xs text-rose-600 mt-1">Skenario promo yang Anda setujui ke pembeli (mengurangi harga jual).</p>
                </div>
                <div class="p-6 space-y-5 flex-1">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Promo ke Customer</label>
                        <select wire:model.live="custPromoType" class="w-full rounded-lg border-slate-300 shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm font-medium">
                            <option value="NONE">Tidak Ada Promo Customer</option>
                            <option value="BOGO">Beli X Gratis Y</option>
                            <option value="DISC">Diskon Persentase (%)</option>
                        </select>
                    </div>

                    @if($custPromoType === 'BOGO')
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Syarat Beli</label>
                                <input type="number" wire:model.live.debounce.300ms="custBuyQty" class="w-full rounded-md border-slate-300 shadow-sm text-center font-bold">
                            </div>
                            <div class="text-lg font-bold text-slate-300 mt-5">+</div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Berikan Gratis</label>
                                <input type="number" wire:model.live.debounce.300ms="custGetQty" class="w-full rounded-md border-slate-300 shadow-sm text-center font-bold text-rose-600">
                            </div>
                        </div>
                    @elseif($custPromoType === 'DISC')
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Diskon Sebesar</label>
                            <div class="relative w-32">
                                <input type="number" wire:model.live.debounce.300ms="custDiscPct" class="w-full rounded-md border-slate-300 shadow-sm pr-8 text-right font-bold text-rose-600">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">%</span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Estimasi Volume Barang Terjual <span class="text-xs font-normal text-slate-500">(Max Promo)</span></label>
                        <div class="flex gap-2">
                            <input type="number" wire:model.live.debounce.300ms="estimatedVolume" class="w-full text-sm font-bold text-center border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500">
                            <button wire:click="$set('estimatedVolume', stockQty)" class="px-3 bg-slate-100 border border-slate-300 text-xs font-bold text-slate-600 rounded-lg hover:bg-slate-200">MAX STOK</button>
                        </div>
                    </div>

                    <div class="mt-auto pt-6 border-t border-slate-100">
                        <div class="text-sm font-medium text-slate-500">Effective Selling Price (Harga Jual Riil / pcs):</div>
                        <div class="text-3xl font-bold text-rose-700 mt-1" x-text="formatRp({{ $effPrice }})"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Final Result Banner --}}
        <div class="rounded-2xl shadow-lg overflow-hidden border-2 {{ $profit > 0 ? 'bg-indigo-600 border-indigo-700' : 'bg-rose-600 border-rose-700' }} text-white p-8 relative flex flex-wrap lg:flex-nowrap items-center justify-between gap-8">
            
            <div class="absolute right-0 top-0 opacity-10 pointer-events-none transform translate-x-1/4 -translate-y-1/4">
                <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"></path></svg>
            </div>

            <div class="relative z-10">
                <div class="text-sm font-bold uppercase tracking-widest text-white/70 mb-2">PROYEKSI TOTAL LABA BERSIH (PROMO)</div>
                <div class="text-5xl lg:text-6xl font-black mb-2 tracking-tight drop-shadow-md" x-text="formatRp({{ $totalProjectedProfit }})"></div>
                <div class="text-sm text-white/80 font-medium">Berdasarkan estimasi {{ number_format($estimatedVolume) }} pcs terjual. (Normalnya: <span x-text="formatRp({{ $totalNormalProfit }})"></span>)</div>
                
                <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm px-6 py-3 rounded-full border border-white/20 mt-6">
                    <span class="text-lg font-bold">{{ number_format($margin, 2) }}% Margin / Pcs</span>
                    
                    @if($profit > $normalProfit)
                        <span class="flex items-center gap-1 text-emerald-300 text-sm font-bold bg-emerald-900/30 px-3 py-1 rounded-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            Lebih untung dari normal!
                        </span>
                    @elseif($profit > 0 && $profit < $normalProfit)
                        <span class="flex items-center gap-1 text-amber-300 text-sm font-bold bg-amber-900/30 px-3 py-1 rounded-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            Profit menurun (wajar krn diskon)
                        </span>
                    @else
                        <span class="flex items-center gap-1 text-rose-300 text-sm font-bold bg-rose-900/30 px-3 py-1 rounded-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            RUGI / BONCOS!
                        </span>
                    @endif
                </div>
            </div>

            <div class="relative z-10 shrink-0">
                <button wire:click="submitDraft" class="bg-white text-indigo-700 hover:bg-slate-50 font-black py-4 px-8 rounded-xl shadow-xl border border-indigo-200 active:scale-[0.98] transition-transform text-lg flex flex-col items-center gap-1 group">
                    <span>AJUKAN PROMO DRAFT</span>
                    <span class="text-xs font-medium text-slate-500 group-hover:text-slate-600">(Lempar ke Workflow Approval)</span>
                </button>
            </div>
        </div>

    </div>
</div>
