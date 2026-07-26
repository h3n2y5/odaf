<div x-data="{
    formatRp(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n); }
}" @keydown.f12.window.prevent="$wire.openPayment()" @keydown.escape.window="$wire.closePayment(); $wire.closeReceipt();">

    {{-- ═══ TOP BAR ═══ --}}
    <div class="bg-slate-900 text-white px-4 py-2.5 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight">ODAF Point of Sale</h1>
                <p class="text-xs text-slate-400">{{ now()->format('d M Y') }} — {{ Auth::user()->object_name ?? Auth::user()->OBJECT_NAME ?? 'Kasir' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$toggle('showHistory')" class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Riwayat
            </button>
            <div class="text-right">
                <div class="text-xs text-slate-400">Total Hari Ini</div>
                <div class="text-sm font-bold text-emerald-400" x-text="formatRp({{ $this->todayTotal }})"></div>
            </div>
            <a href="/" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors" title="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </a>
        </div>
    </div>

    <div class="flex h-[calc(100vh-56px)]">

        {{-- ═══ LEFT: Product Grid ═══ --}}
        <div class="flex-1 bg-slate-100 flex flex-col">
            {{-- Search & Filter Bar --}}
            <div class="p-3 bg-white border-b border-slate-200 flex items-center gap-3">
                <div class="flex-1 relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           wire:keydown.enter="handleBarcodeSearch"
                           autofocus
                           placeholder="Scan barcode, atau cari nama/kode... (F2)"
                           class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           @keydown.f2.window.prevent="$el.focus()">
                </div>
                <div class="flex gap-1">
                    @foreach($this->categories as $cat)
                        <button wire:click="$set('categoryFilter', '{{ $cat }}')"
                                class="px-3 py-2 text-xs font-medium rounded-lg transition-colors {{ $categoryFilter === $cat ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-300' }}">
                            {{ $cat === 'ALL' ? 'Semua' : $cat }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1 overflow-y-auto p-3">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                    @foreach($this->products as $product)
                        <button wire:click="addToCart('{{ $product['id'] }}')"
                                class="bg-white rounded-xl border border-slate-200 p-3 hover:border-indigo-400 hover:shadow-lg transition-all duration-200 text-left group active:scale-95 {{ $product['stock'] <= 0 ? 'opacity-40 pointer-events-none' : '' }}">
                            {{-- Product Icon --}}
                            <div class="w-full aspect-square rounded-lg bg-gradient-to-br from-indigo-50 to-blue-50 flex items-center justify-center mb-2 group-hover:from-indigo-100 group-hover:to-blue-100 transition-colors">
                                @php
                                    $cat = strtolower($product['category'] ?? '');
                                    $icon = match(true) {
                                        str_contains($cat, 'minum') => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                                        str_contains($cat, 'makan') => 'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.75 1.75 0 003 15.546M12 2v4m-3 0V2m6 4V2M5 8h14c.552 0 1 .672 1 1.5S19.552 11 19 11H5c-.552 0-1-.672-1-1.5S4.448 8 5 8zm1 3l-.667 5h13.334L18 11',
                                        default => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                                    };
                                @endphp
                                <svg class="w-10 h-10 text-indigo-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"></path>
                                </svg>
                            </div>
                            <div class="text-xs font-medium text-slate-800 truncate">{{ $product['name'] }}</div>
                            <div class="text-xs text-slate-400 font-mono">{{ $product['code'] }}</div>
                            <div class="text-sm font-bold text-indigo-600 mt-1" x-text="formatRp({{ $product['price'] }})"></div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Stok: {{ $product['stock'] }}</div>
                        </button>
                    @endforeach

                    @if(empty($this->products))
                        <div class="col-span-full text-center py-16 text-slate-400">
                            <svg class="mx-auto w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <p class="text-sm">Produk tidak ditemukan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ RIGHT: Cart ═══ --}}
        <div class="w-[380px] bg-white border-l border-slate-200 flex flex-col shadow-xl">
            {{-- Cart Header --}}
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                        Keranjang
                    </h2>
                    @if(!empty($cart))
                        <button wire:click="clearCart" wire:confirm="Hapus semua item dari keranjang?" class="text-xs text-rose-500 hover:text-rose-700">Hapus Semua</button>
                    @endif
                </div>
                <div class="text-xs text-slate-400 mt-0.5">{{ count($cart) }} item</div>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto">
                @if(empty($cart))
                    <div class="flex flex-col items-center justify-center h-full text-slate-300">
                        <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                        <p class="text-sm">Keranjang kosong</p>
                        <p class="text-xs mt-1">Klik produk untuk menambahkan</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($cart as $idx => $item)
                            <div class="px-4 py-3 hover:bg-slate-50 transition-colors group">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-slate-800 truncate">{{ $item['name'] }}</div>
                                        <div class="text-xs text-slate-400 font-mono">{{ $item['code'] }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5" x-text="formatRp({{ $item['price'] }}) + ' /pcs'"></div>
                                        @if(($item['promo_discount'] ?? 0) > 0)
                                            <div class="inline-block mt-1 px-1.5 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded">
                                                🎁 {{ $item['promo_name'] }}
                                            </div>
                                        @endif
                                    </div>
                                    <button wire:click="removeFromCart({{ $idx }})" class="p-1 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="decrementQty({{ $idx }})" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-lg font-bold transition-colors">−</button>
                                        <span class="w-8 text-center text-sm font-bold text-slate-800">{{ $item['qty'] }}</span>
                                        <button wire:click="incrementQty({{ $idx }})" class="w-7 h-7 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-700 flex items-center justify-center text-lg font-bold transition-colors">+</button>
                                    </div>
                                    <div class="text-right">
                                        @if(($item['promo_discount'] ?? 0) > 0)
                                            <div class="text-[10px] text-slate-400 line-through" x-text="formatRp({{ $item['price'] * $item['qty'] }})"></div>
                                        @endif
                                        <div class="text-sm font-bold text-slate-800" x-text="formatRp({{ $item['total'] }})"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cart Summary & Pay Button --}}
            <div class="border-t border-slate-200 bg-slate-50">
                <div class="px-4 py-3 space-y-1">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Subtotal</span>
                        <span x-text="formatRp({{ $this->subtotal }})"></span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Diskon</span>
                        <div class="flex items-center gap-1">
                            <span>Rp</span>
                            <input type="text" wire:model.live="discount" class="w-20 text-right text-sm border-slate-300 rounded px-1 py-0.5" placeholder="0">
                        </div>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-slate-900 pt-2 border-t border-slate-300">
                        <span>TOTAL</span>
                        <span class="text-indigo-600" x-text="formatRp({{ $this->grandTotal }})"></span>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <button wire:click="openPayment"
                            @if(empty($cart)) disabled @endif
                            class="w-full py-3.5 rounded-xl text-white font-bold text-base transition-all duration-200 flex items-center justify-center gap-2 {{ empty($cart) ? 'bg-slate-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] shadow-lg shadow-indigo-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        BAYAR (F12)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ PAYMENT MODAL ═══ --}}
    @if($showPayment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" wire:click.self="closePayment">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" @click.stop>
                <div class="bg-indigo-600 px-6 py-4 text-white">
                    <h3 class="text-lg font-bold">Pembayaran</h3>
                    <div class="text-3xl font-black mt-1" x-text="formatRp({{ $this->grandTotal }})"></div>
                </div>

                @if(session('pos_error'))
                    <div class="mx-6 mt-4 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">{{ session('pos_error') }}</div>
                @endif

                <div class="p-6 space-y-4">
                    {{-- Payment Method --}}
                    <div>
                        <label class="text-sm font-medium text-slate-700">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <button wire:click="$set('paymentMethod', 'CASH')" class="py-2.5 rounded-lg text-sm font-medium border-2 transition-colors {{ $paymentMethod === 'CASH' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                                💵 Tunai
                            </button>
                            <button wire:click="$set('paymentMethod', 'CARD')" class="py-2.5 rounded-lg text-sm font-medium border-2 transition-colors {{ $paymentMethod === 'CARD' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                                💳 Kartu
                            </button>
                        </div>
                    </div>

                    @if($paymentMethod === 'CASH')
                        {{-- Amount Input --}}
                        <div>
                            <label class="text-sm font-medium text-slate-700">Jumlah Dibayar</label>
                            <input type="text" wire:model.live="amountPaidInput" autofocus
                                   class="w-full mt-1 text-2xl font-bold text-right py-3 px-4 border-2 border-slate-300 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                   placeholder="0" inputmode="numeric">
                        </div>

                        {{-- Quick Buttons --}}
                        <div class="grid grid-cols-4 gap-2">
                            <button wire:click="setQuickPay(50000)" class="py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">50rb</button>
                            <button wire:click="setQuickPay(100000)" class="py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">100rb</button>
                            <button wire:click="setQuickPay(200000)" class="py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">200rb</button>
                            <button wire:click="setExactPay" class="py-2 rounded-lg text-sm font-medium bg-emerald-100 hover:bg-emerald-200 text-emerald-700 transition-colors">Pas</button>
                        </div>

                        {{-- Change Display --}}
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                            <div class="text-sm text-emerald-600 font-medium">Kembalian</div>
                            <div class="text-2xl font-black text-emerald-700" x-text="formatRp(Math.max(0, {{ $this->amountPaid }} - {{ $this->grandTotal }}))"></div>
                        </div>
                    @endif
                </div>

                <div class="px-6 pb-6 flex gap-3">
                    <button wire:click="closePayment" class="flex-1 py-3 rounded-xl border border-slate-300 text-slate-600 font-medium hover:bg-slate-50 transition-colors">Batal</button>
                    <button wire:click="processPayment" class="flex-1 py-3 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2 active:scale-[0.98]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ RECEIPT MODAL ═══ --}}
    @if($showReceipt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden text-center">
                <div class="bg-emerald-500 px-6 py-6 text-white">
                    <svg class="mx-auto w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-xl font-bold">Transaksi Berhasil!</h3>
                    <div class="text-emerald-100 text-sm mt-1">{{ $lastTrxNo }}</div>
                </div>
                <div class="p-6">
                    <div class="text-2xl font-black text-emerald-600 mb-1" x-text="'Kembalian: ' + formatRp({{ $lastChange }})"></div>
                    <p class="text-sm text-slate-500">Transaksi telah disimpan.</p>
                </div>
                <div class="px-6 pb-6">
                    <button wire:click="closeReceipt" class="w-full py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-colors">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ HISTORY DRAWER ═══ --}}
    @if($showHistory)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/40" wire:click.self="$set('showHistory', false)">
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col" @click.stop>
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="font-bold text-slate-800">Riwayat Hari Ini</h3>
                    <button wire:click="$set('showHistory', false)" class="p-1 text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    @forelse($this->todayTransactions as $trx)
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-mono font-medium text-slate-800">{{ $trx['trx_no'] }}</span>
                                <span class="text-xs text-slate-400">{{ $trx['time'] }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-slate-500">{{ $trx['cashier'] }} · {{ $trx['method'] }}</span>
                                <span class="text-sm font-bold text-indigo-600" x-text="formatRp({{ $trx['total'] }})"></span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center h-48 text-slate-300">
                            <p class="text-sm">Belum ada transaksi hari ini.</p>
                        </div>
                    @endforelse
                </div>
                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600">Total Hari Ini</span>
                    <span class="text-lg font-bold text-emerald-600" x-text="formatRp({{ $this->todayTotal }})"></span>
                </div>
            </div>
        </div>
    @endif

</div>
