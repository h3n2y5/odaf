<div class="min-h-screen bg-slate-50 font-sans pb-20">
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Harga & Profit</h1>
                <p class="text-xs text-slate-500">Atur Harga Modal (HPP), Margin, dan Harga Jual akhir.</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="relative w-64">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari produk..." class="w-full pl-9 pr-4 py-2 text-sm border-slate-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <a href="{{ route('odaf.home', ['appCode' => 'POS_APP']) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Kembali</a>
        </div>
    </div>

    <div class="p-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-bold text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 w-48">Harga Modal (HPP)</th>
                        <th class="px-4 py-3 w-32">Margin (%)</th>
                        <th class="px-4 py-3 w-48">Harga Jual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($products as $idx => $p)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $p['name'] }}</div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $p['code'] }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">Rp</span>
                                    <input type="number" wire:model.blur="products.{{ $idx }}.cost" wire:change="recalculate({{ $idx }})" class="w-full pl-9 pr-3 py-1.5 text-sm border-slate-300 rounded focus:ring-emerald-500 focus:border-emerald-500 text-right font-mono">
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative">
                                    <input type="number" wire:model.blur="products.{{ $idx }}.margin" wire:change="recalculate({{ $idx }})" class="w-full pl-3 pr-8 py-1.5 text-sm border-slate-300 rounded focus:ring-emerald-500 focus:border-emerald-500 text-right font-mono text-emerald-600 font-bold">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">Rp</span>
                                    <input type="number" wire:model.blur="products.{{ $idx }}.price" wire:change="recalculateMargin({{ $idx }})" class="w-full pl-9 pr-3 py-1.5 text-sm border-slate-300 rounded focus:ring-emerald-500 focus:border-emerald-500 text-right font-mono font-bold text-slate-800">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if(empty($products))
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Produk tidak ditemukan.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
