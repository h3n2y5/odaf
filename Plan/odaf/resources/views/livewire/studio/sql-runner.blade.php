<div>
    <x-studio-shell title="SQL Runner">
        <div class="h-full flex flex-col gap-4">
            {{-- Top Section: Query Editor --}}
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col shrink-0">
                <div class="border-b border-slate-200 px-4 py-2 flex items-center justify-between bg-slate-50 rounded-t-lg">
                    <h2 class="text-sm font-semibold text-slate-700">Editor SQL</h2>
                    <button wire:click="runQuery"
                            class="inline-flex items-center gap-1 rounded bg-emerald-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                        <svg wire:loading.remove wire:target="runQuery" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg wire:loading wire:target="runQuery" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Eksekusi
                    </button>
                </div>
                <div class="p-1">
                    <textarea wire:model="query" 
                              class="w-full h-32 p-3 text-sm font-mono text-slate-800 bg-slate-50 border-0 focus:ring-0 resize-y" 
                              placeholder="Ketik query SELECT di sini... Contoh: SELECT * FROM APP_APPLICATION"></textarea>
                </div>
            </div>

            {{-- Bottom Section: Results --}}
            <div class="flex-1 min-h-0 bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col">
                <div class="border-b border-slate-200 px-4 py-2 bg-slate-50 rounded-t-lg flex justify-between items-center">
                    <h2 class="text-sm font-semibold text-slate-700">Hasil Data</h2>
                    @if ($executionTime > 0)
                        <span class="text-xs text-slate-500">Waktu eksekusi: {{ $executionTime }} ms</span>
                    @endif
                </div>

                <div class="flex-1 min-h-0 overflow-auto p-0 relative">
                    @if ($errorMessage !== null)
                        <div class="p-6">
                            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                                <div class="flex">
                                    <div class="shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Error Eksekusi Query</h3>
                                        <div class="mt-2 text-sm text-red-700 whitespace-pre-wrap font-mono">{{ $errorMessage }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($columns !== [])
                        <table data-resize-key="studio:sqlrunner" class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 w-12 text-center text-slate-400 font-medium border-r border-slate-200">#</th>
                                    @foreach ($columns as $col)
                                        <th class="resizable px-4 py-2 text-left font-medium text-slate-600 whitespace-nowrap">
                                            {{ $col }}
                                            <span class="col-resizer" wire:ignore onclick="event.stopPropagation()"></span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($rows as $index => $row)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-1.5 text-center text-slate-400 text-xs border-r border-slate-200">{{ $index + 1 }}</td>
                                        @foreach ($columns as $col)
                                            <td class="px-4 py-1.5 whitespace-nowrap text-slate-700">
                                                @php
                                                    $val = $row[$col];
                                                    $isLong = is_string($val) && strlen($val) > 100;
                                                @endphp
                                                @if ($val === null)
                                                    <span class="text-slate-400 italic font-serif">(null)</span>
                                                @elseif ($isLong)
                                                    <span title="{{ $val }}">{{ substr($val, 0, 100) }}&hellip;</span>
                                                @else
                                                    {{ $val }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($query !== '')
                        <div class="flex items-center justify-center h-full text-slate-400 italic">
                            Tekan tombol Eksekusi untuk menjalankan query.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-studio-shell>
</div>
