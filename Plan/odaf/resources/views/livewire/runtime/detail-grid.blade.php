<div class="rounded-lg border border-slate-200 bg-white p-4 mt-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 flex items-center gap-2">
            {{ $title }}
            @if ($isAdmin ?? false)
                <a href="{{ route('studio.grid', ['table' => $tableName]) }}" target="_blank"
                   class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 hover:bg-indigo-100 hover:text-indigo-700 normal-case" title="Buka Data Manager (Admin)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    {{ $tableName }}
                </a>
            @endif
        </h2>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="addLine"
                    class="inline-flex items-center gap-1 rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Baris
            </button>
            <button type="button" wire:click="saveLines"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-white text-sm font-medium hover:bg-indigo-700">
                <span wire:loading.remove wire:target="saveLines">Simpan Baris</span>
                <span wire:loading wire:target="saveLines">Menyimpan...</span>
            </button>
        </div>
    </div>

    @if (session('detail.status'))
        <div class="mb-3 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-emerald-700 text-sm">{{ session('detail.status') }}</div>
    @endif
    @error('detail')
        <div class="mb-3 rounded-md bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-sm">{{ $message }}</div>
    @enderror

    <div class="overflow-x-auto rounded border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-slate-500 w-10">#</th>
                    @foreach ($fields as $f)
                        <th class="px-3 py-2 text-left font-medium text-slate-600 whitespace-nowrap">
                            {{ $f['label'] }}
                            @if ($f['required'] ?? false)<span class="text-rose-500">*</span>@endif
                        </th>
                    @endforeach
                    <th class="px-3 py-2 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($lines as $i => $line)
                    <tr wire:key="line-{{ $i }}">
                        <td class="px-3 py-2 text-slate-400">{{ $i + 1 }}</td>
                        @foreach ($fields as $f)
                            @php
                                $col = strtoupper((string) $f['column']);
                                $model = 'lines.' . $i . '.' . $col;
                                $ft = strtoupper((string) ($f['fieldType'] ?? 'TEXT'));
                            @endphp
                            <td class="px-2 py-1.5">
                                @if (($f['lovId'] ?? '') !== '')
                                    <select wire:model="{{ $model }}" @disabled($f['readonly'] ?? false) class="w-full min-w-[8rem] rounded border border-slate-300 px-2 py-1 text-sm text-slate-700 disabled:bg-slate-50 disabled:text-slate-500">
                                        <option value="">-- pilih --</option>
                                        @foreach (($f['options'] ?? []) as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($ft === 'CHECKBOX')
                                    <input type="checkbox" wire:model="{{ $model }}" @disabled($f['readonly'] ?? false)
                                           class="rounded border-slate-300 text-indigo-600 disabled:opacity-50">
                                @elseif ($ft === 'TEXTAREA')
                                    <textarea wire:model="{{ $model }}" rows="1" @readonly($f['readonly'] ?? false)
                                              class="w-full min-w-[10rem] rounded border border-slate-300 px-2 py-1 text-sm read-only:bg-slate-50 read-only:text-slate-500"></textarea>
                                @elseif (in_array($ft, ['NUMBER','INTEGER','DECIMAL']))
                                    <input type="number" step="any" wire:model="{{ $model }}" @readonly($f['readonly'] ?? false)
                                           class="w-full min-w-[6rem] rounded border border-slate-300 px-2 py-1 text-sm text-right read-only:bg-slate-50 read-only:text-slate-500">
                                @elseif ($ft === 'DATE')
                                    <input type="date" wire:model="{{ $model }}" @readonly($f['readonly'] ?? false)
                                           class="w-full min-w-[9rem] rounded border border-slate-300 px-2 py-1 text-sm read-only:bg-slate-50 read-only:text-slate-500">
                                @elseif ($ft === 'DATETIME')
                                    <input type="datetime-local" wire:model="{{ $model }}" @readonly($f['readonly'] ?? false)
                                           class="w-full min-w-[11rem] rounded border border-slate-300 px-2 py-1 text-sm read-only:bg-slate-50 read-only:text-slate-500">
                                @else
                                    <input type="text" wire:model="{{ $model }}" @readonly($f['readonly'] ?? false)
                                           class="w-full min-w-[8rem] rounded border border-slate-300 px-2 py-1 text-sm read-only:bg-slate-50 read-only:text-slate-500">
                                @endif
                            </td>
                        @endforeach
                        <td class="px-2 py-1.5 text-center">
                            <button type="button" wire:click="removeLine({{ $i }})"
                                    wire:confirm="Hapus baris ini?"
                                    class="text-rose-600 hover:text-rose-800" title="Hapus baris">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($fields) + 2 }}" class="px-3 py-6 text-center text-slate-400">
                            Belum ada baris. Klik "Tambah Baris" untuk menambah.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
