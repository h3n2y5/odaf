<div class="min-h-screen bg-slate-50 font-sans pb-20">
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Campaign & Promo</h1>
                <p class="text-xs text-slate-500">Atur diskon dan promo Beli X Gratis Y untuk kasir.</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button wire:click="createPromo" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm">
                + Buat Promo
            </button>
            <a href="{{ route('odaf.home', ['appCode' => 'POS_APP']) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Kembali</a>
        </div>
    </div>

    @if(!$showForm)
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($promos as $promo)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative group">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-2">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold tracking-wider {{ $promo['active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $promo['active'] ? 'AKTIF' : 'NON-AKTIF' }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono">{{ $promo['code'] }}</span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg mb-1">{{ $promo['name'] }}</h3>
                        <p class="text-xs text-slate-500 mb-4">
                            @if($promo['type'] === 'BOGO') 🎁 Beli X Gratis Y
                            @elseif($promo['type'] === 'DISC_PCT') 📉 Diskon Persen Tunggal
                            @elseif($promo['type'] === 'TIERED') 📊 Diskon Bertingkat
                            @endif
                        </p>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ $promo['start_date'] }} s/d {{ $promo['end_date'] }}
                        </div>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50 p-3 flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <button wire:click="editPromo('{{ $promo['id'] }}')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit Promo</button>
                    </div>
                </div>
            @endforeach
            @if(empty($promos))
                <div class="col-span-full py-12 text-center text-slate-400">Belum ada promo yang dibuat.</div>
            @endif
        </div>
    </div>
    @else
    <div class="p-6 max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex justify-between items-center">
                <h2 class="font-bold text-slate-800">{{ $formMode === 'INSERT' ? 'Buat Promo Baru' : 'Edit Promo' }}</h2>
                <button wire:click="$set('showForm', false)" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-6">
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kode Promo</label>
                        <input type="text" wire:model="promoCode" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Promo</label>
                        <input type="text" wire:model="promoName" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Berlaku Dari</label>
                        <input type="date" wire:model="startDate" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Sampai Dengan</label>
                        <input type="date" wire:model="endDate" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Tipe Promo</label>
                        <select wire:model.live="promoType" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="BOGO">🎁 Beli X Gratis Y</option>
                            <option value="DISC_PCT">📉 Diskon Persen (%)</option>
                            <option value="TIERED">📊 Diskon Bertingkat (Grosir)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Target Produk</label>
                        <select wire:model="productId" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Semua Produk</option>
                            @foreach($products as $p)
                                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bg-indigo-50 rounded-xl p-5 border border-indigo-100">
                    @if($promoType === 'BOGO')
                        <h4 class="font-bold text-indigo-800 text-sm mb-3">Aturan Beli X Gratis Y</h4>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-700">Jika Beli</span>
                                <input type="number" wire:model="buyQty" class="w-20 rounded-md border-slate-300 shadow-sm sm:text-sm text-center">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-700">Maka Gratis</span>
                                <input type="number" wire:model="getQty" class="w-20 rounded-md border-slate-300 shadow-sm sm:text-sm text-center">
                            </div>
                        </div>
                        <p class="text-xs text-indigo-600 mt-2">Contoh: Beli 2 Gratis 1. (Barang ke-3 otomatis dipotong 100%).</p>
                    
                    @elseif($promoType === 'DISC_PCT')
                        <h4 class="font-bold text-indigo-800 text-sm mb-3">Diskon Persen Tunggal</h4>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700">Diskon Sebesar</span>
                            <div class="relative w-32">
                                <input type="number" wire:model="discountPct" class="w-full rounded-md border-slate-300 shadow-sm sm:text-sm pr-8 text-right">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">%</span>
                            </div>
                        </div>
                    
                    @elseif($promoType === 'TIERED')
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-indigo-800 text-sm">Diskon Bertingkat</h4>
                            <button wire:click="addTier" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-white px-2 py-1 rounded border border-indigo-200">+ Tambah Tier</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($tieredConfig as $idx => $tier)
                                <div class="flex items-center gap-3">
                                    <span class="text-sm">Beli minimal</span>
                                    <input type="number" wire:model="tieredConfig.{{ $idx }}.qty" class="w-20 rounded-md border-slate-300 shadow-sm sm:text-sm text-center">
                                    <span class="text-sm">pcs, Diskon</span>
                                    <div class="relative w-24">
                                        <input type="number" wire:model="tieredConfig.{{ $idx }}.disc" class="w-full rounded-md border-slate-300 shadow-sm sm:text-sm pr-6 text-right">
                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">%</span>
                                    </div>
                                    <button wire:click="removeTier({{ $idx }})" class="text-rose-500 hover:text-rose-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="activeFlag" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-slate-700">Promo Aktif</span>
                    </label>
                </div>

            </div>
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button wire:click="$set('showForm', false)" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-300 shadow-sm">Batal</button>
                <button wire:click="savePromo" class="px-6 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">Simpan Promo</button>
            </div>
        </div>
    </div>
    @endif
</div>
