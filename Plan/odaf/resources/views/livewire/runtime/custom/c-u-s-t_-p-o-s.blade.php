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