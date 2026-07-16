<div>
    <x-studio-shell :title="$table">
      <div class="flex flex-col flex-1 min-h-0">
        <div class="flex items-center justify-between mb-3 shrink-0">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">{{ $table }}</h1>
                <p class="text-sm text-slate-500">{{ $total }} baris</p>
            </div>
            @if ($canEdit)
                @php
                    $newUrl = $table === 'DS_LOV' 
                        ? route('studio.designer.lov.new') 
                        : route('studio.form', ['table' => $table]);
                @endphp
                <a href="{{ $newUrl }}" wire:navigate
                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                    + Baru
                </a>
            @endif
        </div>

        @error('grid')
            <div class="mb-3 rounded-md bg-rose-50 border border-rose-200 px-4 py-2 text-rose-700 text-sm shrink-0">{{ $message }}</div>
        @enderror

        @if (session('studio.status'))
            <div class="mb-3 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-2 text-emerald-700 text-sm shrink-0">{{ session('studio.status') }}</div>
        @endif

        <div x-data="{ wrap: (localStorage.getItem('gridwrap:{{ $table }}') === '1') }" class="flex-1 flex flex-col min-h-0">
        {{-- Toolbar --}}
        <div class="mb-3 flex flex-wrap items-center gap-2 shrink-0">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari..."
                   class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            @if (count($filters) || $search !== '')
                <button wire:click="clearFilters"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                    Reset filter
                </button>
            @endif

            @if ($canEdit && count($selected))
                <button wire:click="cloneSelected"
                        wire:confirm="Clone {{ count($selected) }} baris terpilih sebagai DRAFT?"
                        class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Clone ({{ count($selected) }})
                </button>
            @endif

            <div class="ml-auto flex items-center gap-2">
                {{-- Wrap teks (tinggi baris menyesuaikan isi) --}}
                <button type="button"
                        @click="wrap = !wrap; localStorage.setItem('gridwrap:{{ $table }}', wrap ? '1' : '0')"
                        :class="wrap ? 'bg-indigo-600 text-white border-indigo-600' : 'text-slate-600 border-slate-300 hover:bg-slate-50'"
                        class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm"
                        title="Bungkus teks / tinggi baris otomatis">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h12a3 3 0 010 6h-3m0 0l2-2m-2 2l2 2M4 18h4"></path>
                    </svg>
                    Wrap
                </button>

                {{-- Column chooser --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="inline-flex items-center gap-1 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        Kolom
                    </button>
                    <div x-show="open" x-transition x-cloak
                         class="absolute right-0 mt-1 w-56 max-h-72 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg z-20 p-2">
                        <div class="text-xs font-medium text-slate-400 px-2 py-1">Tampilkan kolom</div>
                        @foreach ($allColumns as $col)
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox"
                                       @checked(! in_array(strtoupper($col), $hiddenColumns, true))
                                       wire:click="toggleColumn('{{ $col }}')"
                                       class="rounded border-slate-300 text-indigo-600">
                                <span class="text-sm text-slate-700">{{ $col }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Page size --}}
                <select wire:model.live="pageSize"
                        class="rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ([10, 15, 25, 50, 100] as $ps)
                        <option value="{{ $ps }}">{{ $ps }} / halaman</option>
                    @endforeach
                </select>
            </div>
        </div>

        @php
            $pageKeys = [];
            foreach ($rows as $r) { if (($r['key'] ?? null) !== null) $pageKeys[] = (string) $r['key']; }
            $allChecked = $pageKeys !== [] && count(array_intersect($pageKeys, $selected)) === count($pageKeys);
        @endphp
        <div class="rounded-lg border border-slate-200 bg-white overflow-auto flex-1 min-h-0">
            <table data-resize-key="studio:{{ $table }}" :class="{ 'grid-wrap': wrap }"
                   class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 sticky top-0 z-10">
                    <tr>
                        @if ($canEdit)
                            <th class="px-3 py-2 w-8">
                                <input type="checkbox" @checked($allChecked)
                                       wire:click="toggleSelectAll(@js($pageKeys))"
                                       class="rounded border-slate-300 text-indigo-600">
                            </th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600 w-32">Aksi</th>
                        @endif
                        @foreach ($displayColumns as $col)
                            <th class="resizable px-4 py-2 text-left font-medium text-slate-600 cursor-pointer select-none whitespace-nowrap"
                                wire:click="sortBy('{{ $col }}')">
                                {{ $col }}
                                @if ($sortColumn === $col)
                                    <span class="text-slate-400">{{ $sortDir === 'ASC' ? '▲' : '▼' }}</span>
                                @endif
                                <span class="col-resizer" wire:ignore onclick="event.stopPropagation()"></span>
                            </th>
                        @endforeach
                    </tr>
                    {{-- Baris filter per kolom --}}
                    <tr class="bg-white">
                        @if ($canEdit)
                            <th></th>
                            <th></th>
                        @endif
                        @foreach ($displayColumns as $col)
                            <th class="px-2 py-1.5">
                                <input type="text"
                                       wire:model.live.debounce.400ms="filters.{{ strtoupper($col) }}"
                                       placeholder="filter…"
                                       class="w-full min-w-[8rem] rounded border border-slate-200 px-2 py-1 text-xs font-normal focus:border-indigo-500 focus:ring-indigo-500">
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-slate-50" wire:key="srow-{{ $row['key'] }}">
                            @if ($canEdit)
                                <td class="px-3 py-2 w-8">
                                    <input type="checkbox" value="{{ $row['key'] }}" wire:model.live="selected"
                                           class="rounded border-slate-300 text-indigo-600">
                                </td>
                                <td class="px-4 py-2 text-left whitespace-nowrap">
                                    @php
                                        $editUrl = $table === 'DS_LOV' 
                                            ? route('studio.designer.lov.edit', ['lovId' => $row['key']]) 
                                            : route('studio.form', ['table' => $table, 'key' => $row['key']]);
                                    @endphp
                                    <a href="{{ $editUrl }}"
                                       wire:navigate class="text-indigo-600 hover:underline font-medium">Ubah</a>
                                    <button type="button" wire:click="delete('{{ $row['key'] }}')"
                                            wire:confirm="Hapus baris ini?"
                                            class="ml-3 text-rose-600 hover:underline">Hapus</button>
                                </td>
                            @endif
                            @foreach ($displayColumns as $col)
                                @php
                                    $val = $row['data'][$col] ?? null;
                                    if (isset($fkLabels[$col][$val])) {
                                        $display = $fkLabels[$col][$val];
                                    } elseif (($schema['columns'][$col]['isBinary'] ?? false) && $val) {
                                        $display = strtolower(substr((string) $val, 0, 8)) . '…';
                                    } elseif (strtoupper($col) === 'PASSWORD_HASH') {
                                        $display = $val ? '••••••••' : '';
                                    } else {
                                        // Tampilkan penuh; gunakan tombol Wrap agar isi panjang tidak terpotong.
                                        $display = (string) $val;
                                    }
                                @endphp
                                <td class="px-4 py-2 text-slate-700 whitespace-nowrap">{{ $display }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($displayColumns) + ($canEdit ? 2 : 0) }}" class="px-4 py-8 text-center text-slate-400">
                                Tidak ada data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>{{-- /wrap scope --}}

        <div class="flex items-center justify-between mt-3 text-sm shrink-0">
            <div class="text-slate-500">Halaman {{ $pageNo }} dari {{ $lastPage }} &middot; {{ $total }} baris</div>
            <div class="space-x-2">
                <button wire:click="prevPage" @disabled($pageNo <= 1)
                        class="rounded border border-slate-300 px-3 py-1 disabled:opacity-40">Sebelumnya</button>
                <button wire:click="nextPage({{ $lastPage }})" @disabled($pageNo >= $lastPage)
                        class="rounded border border-slate-300 px-3 py-1 disabled:opacity-40">Berikutnya</button>
            </div>
        </div>
      </div>{{-- /flex-fill --}}
    </x-studio-shell>
</div>
