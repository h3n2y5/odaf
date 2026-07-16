<div>
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" :title="$page['title']">
      <div class="flex flex-col flex-1 min-h-0">
        <div class="flex items-center justify-between mb-3 shrink-0">
            <div>
                <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                    {{ $page['title'] }}
                    @if ($isAdmin ?? false)
                        <a href="{{ route('studio.grid', ['table' => $tableName]) }}" target="_blank"
                           class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 hover:bg-indigo-100 hover:text-indigo-700" title="Buka Data Manager (Admin)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                            {{ $tableName }}
                        </a>
                    @endif
                </h1>
                <p class="text-sm text-slate-500">{{ $total }} baris</p>
            </div>
            @if ($canWrite)
                <a href="{{ route('odaf.form', ['appCode' => $appCode, 'pageCode' => $page['code']]) }}"
                   wire:navigate
                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                    + Baru
                </a>
            @else
                <span class="inline-flex items-center rounded-md bg-slate-100 px-3 py-2 text-slate-500 text-xs font-medium" title="Akses hanya-baca">Hanya baca</span>
            @endif
        </div>

        <div x-data="{ wrap: (localStorage.getItem('gridwrap:{{ $page['code'] }}') === '1') }" class="flex-1 flex flex-col min-h-0">
        {{-- Toolbar --}}
        <div class="mb-3 flex flex-wrap items-center gap-2 shrink-0">
            <input type="text" wire:model.live.debounce.400ms="search"
                   placeholder="Cari..."
                   class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            @if (count($filters) || $search !== '')
                <button wire:click="clearFilters"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                    Reset filter
                </button>
            @endif

            @if (count($selected) && $canWrite)
                <button wire:click="cloneSelected"
                        wire:confirm="Clone {{ count($selected) }} data terpilih sebagai DRAFT?"
                        class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Clone ({{ count($selected) }})
                </button>
            @endif

            @if (count($selected) && isset($bulkWorkflowTransitions) && $bulkWorkflowTransitions !== [])
                @foreach ($bulkWorkflowTransitions as $tr)
                    <button wire:click="performBulkWorkflow('{{ $tr['action'] }}')"
                            wire:confirm="Jalankan {{ $tr['label'] }} untuk {{ count($selected) }} data terpilih?"
                            class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $tr['label'] }} ({{ count($selected) }})
                    </button>
                @endforeach
            @endif

            <div class="ml-auto flex items-center gap-2">
                {{-- Wrap teks (tinggi baris menyesuaikan isi) --}}
                <button type="button"
                        @click="wrap = !wrap; localStorage.setItem('gridwrap:{{ $page['code'] }}', wrap ? '1' : '0')"
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
                            @php $cn = strtoupper((string) $col['column']); @endphp
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox"
                                       @checked(! in_array($cn, $hiddenColumns, true))
                                       wire:click="toggleColumn('{{ $cn }}')"
                                       class="rounded border-slate-300 text-indigo-600">
                                <span class="text-sm text-slate-700">{{ $col['label'] }}</span>
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

        {{-- Tabel (scrollable, sticky header) --}}
        @php
            $pageKeys = [];
            foreach ($rows as $r) { $k = $r[$primaryKey] ?? null; if ($k !== null) $pageKeys[] = (string) $k; }
            $allChecked = $pageKeys !== [] && count(array_intersect($pageKeys, $selected)) === count($pageKeys);
        @endphp
        <div class="rounded-lg border border-slate-200 bg-white overflow-auto flex-1 min-h-0">
            <table data-resize-key="grid:{{ $page['code'] }}" :class="{ 'grid-wrap': wrap }"
                   class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 w-8">
                            <input type="checkbox" @checked($allChecked)
                                   wire:click="toggleSelectAll(@js($pageKeys))"
                                   class="rounded border-slate-300 text-indigo-600">
                        </th>
                        <th class="px-4 py-2 text-left font-medium text-slate-600">Aksi</th>
                        @foreach ($columns as $col)
                            <th class="resizable px-4 py-2 text-left font-medium text-slate-600 cursor-pointer select-none whitespace-nowrap"
                                wire:click="sortBy('{{ $col['column'] }}')">
                                @if ($isAdmin ?? false)
                                    <div class="text-[9px] uppercase tracking-wider text-slate-400/80 mb-0.5 leading-none" title="Database Column">{{ $col['column'] }}</div>
                                @endif
                                <div class="flex items-center gap-1">
                                    {{ $col['label'] }}
                                    @if ($sortColumn === $col['column'])
                                        <span class="text-slate-400">{{ $sortDir === 'ASC' ? '▲' : '▼' }}</span>
                                    @endif
                                </div>
                                <span class="col-resizer" wire:ignore onclick="event.stopPropagation()"></span>
                            </th>
                        @endforeach
                    </tr>
                    {{-- Baris filter per kolom --}}
                    <tr class="bg-white">
                        <th></th>
                        <th></th>
                        @foreach ($columns as $col)
                            <th class="px-2 py-1.5">
                                <input type="text"
                                       wire:model.live.debounce.400ms="filters.{{ strtoupper((string) $col['column']) }}"
                                       placeholder="filter…"
                                       class="w-full min-w-[8rem] rounded border border-slate-200 px-2 py-1 text-xs font-normal focus:border-indigo-500 focus:ring-indigo-500">
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php 
                            $key = $row[$primaryKey] ?? null; 
                            $isChecked = in_array((string)$key, $selected, true);
                            $rowTransitions = $workflowTransitions[$key] ?? [];
                            
                            $rowClass = 'hover:bg-slate-50';
                            if ($isChecked) {
                                $rowClass = 'bg-indigo-50/70 hover:bg-indigo-50';
                            } elseif ($rowTransitions !== []) {
                                $rowClass = 'bg-amber-50/30 hover:bg-amber-50/60';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}" wire:key="row-{{ $key }}">
                            <td class="px-3 py-2 w-8">
                                <input type="checkbox" value="{{ $key }}" wire:model.live="selected"
                                       class="rounded border-slate-300 text-indigo-600">
                            </td>
                            <td class="px-4 py-2 text-left whitespace-nowrap">
                                <a href="{{ route('odaf.form', ['appCode' => $appCode, 'pageCode' => $page['code'], 'key' => $key]) }}"
                                   wire:navigate
                                   class="text-indigo-600 hover:underline">{{ $canWrite ? 'Ubah' : 'Lihat' }}</a>
                                @if ($canWrite)
                                    <button type="button"
                                            wire:click="delete('{{ $key }}')"
                                            wire:confirm="Hapus data ini?"
                                            class="ml-3 text-rose-600 hover:underline">Hapus</button>
                                @endif

                                @php $rowTransitions = $workflowTransitions[$key] ?? []; @endphp
                                @if ($rowTransitions !== [])
                                    <div class="mt-1.5 flex gap-1.5">
                                        @foreach ($rowTransitions as $tr)
                                            <button type="button"
                                                    wire:click="performWorkflow('{{ $key }}', '{{ $tr['action'] }}')"
                                                    wire:confirm="Lakukan: {{ $tr['label'] }}?"
                                                    class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 hover:bg-amber-200 ring-1 ring-inset ring-amber-500/20">
                                                {{ $tr['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            @foreach ($columns as $col)
                                @php
                                    $raw = $row[$col['column']] ?? '';
                                    $colName = strtoupper((string) $col['column']);
                                    $ftype = strtoupper((string) ($col['fieldType'] ?? ''));
                                    $cfg = is_array($col['config'] ?? null) ? $col['config'] : [];
                                    $hasNumFmt = array_key_exists('thousandsSep', $cfg)
                                        || (array_key_exists('decimals', $cfg) && $cfg['decimals'] !== null && $cfg['decimals'] !== '');
                                    $isNum = in_array($ftype, ['NUMBER', 'INTEGER', 'DECIMAL'], true) && is_numeric($raw) && $hasNumFmt;
                                    $numDecimals = (array_key_exists('decimals', $cfg) && $cfg['decimals'] !== null && $cfg['decimals'] !== '') ? (int) $cfg['decimals'] : null;
                                    $numSep = (bool) ($cfg['thousandsSep'] ?? false);

                                    $display = $raw;
                                    if (! $isNum) {
                                        if ($ftype === 'CHECKBOX') {
                                            $display = in_array(strtoupper((string) $raw), ['1', 'Y', 'YES', 'TRUE', 'T'], true) ? 'Ya' : 'Tidak';
                                        } elseif (in_array($ftype, ['DATE', 'DATETIME'], true) && $raw !== '' && $raw !== null) {
                                            try {
                                                $withTime = (bool) ($cfg['withTime'] ?? ($ftype === 'DATETIME'));
                                                $display = \Illuminate\Support\Carbon::parse((string) $raw)->format($withTime ? 'd M Y H:i' : 'd M Y');
                                            } catch (\Throwable) {
                                                $display = $raw;
                                            }
                                        } else {
                                            $display = $lovLabels[$colName][$raw] ?? $raw;
                                        }
                                    }
                                @endphp
                                <td class="px-4 py-2 text-slate-700 whitespace-nowrap">
                                    @if (isset($maskedColumns[$colName]))
                                        <span class="text-slate-400 tracking-widest select-none" title="Data rahasia">&bull;&bull;&bull;&bull;</span>
                                    @elseif ($isNum)
                                        <span x-text="odafNum(@js((float) $raw), {{ $numDecimals === null ? 'null' : $numDecimals }}, {{ $numSep ? 'true' : 'false' }})">{{ $raw }}</span>
                                    @else
                                        {{ $display }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 2 }}" class="px-4 py-8 text-center text-slate-400">
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
    </x-odaf-shell>
</div>
