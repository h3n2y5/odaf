<div>
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl p-10 mb-6 text-center shadow-lg border border-indigo-500">
    <h1 class="text-4xl font-extrabold mb-4 tracking-tight">Simulator Promo &amp; Diskon</h1>
    <p class="text-indigo-100 text-lg mb-8 max-w-2xl mx-auto font-medium">Uji coba parameter diskon sebelum diterapkan ke sistem kasir.</p>
    
</div>
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
</div>