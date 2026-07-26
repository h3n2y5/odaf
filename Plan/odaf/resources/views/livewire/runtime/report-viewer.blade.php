<div class="min-h-screen bg-slate-50">
    {{-- Header --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="{{ route('odaf.home', $appCode) }}" wire:navigate class="p-2 -ml-2 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $report['name'] }}</h1>
                            <p class="text-xs text-slate-500 line-clamp-1">{{ $report['desc'] }}</p>
                        </div>
                    </div>
                </div>
                
                @if($hasRun && count($results) > 0)
                    <a href="{{ route('odaf.report.export', ['appCode' => $appCode, 'reportId' => $reportId]) }}?{{ http_build_query($paramValues) }}" 
                       target="_blank"
                       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white text-sm font-bold shadow-sm hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export CSV
                    </a>
                @endif
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        {{-- Parameters Form --}}
        @if(count($parameters) > 0)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        Filter Parameters
                    </h3>
                </div>
                <div class="p-6">
                    <form wire:submit="runReport" class="flex flex-wrap items-end gap-4">
                        @foreach($parameters as $param)
                            <div class="w-full sm:w-auto flex-1 min-w-[200px]">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $param) }} <span class="text-rose-500">*</span></label>
                                @if(str_contains(strtolower($param), 'date') || str_contains(strtolower($param), 'period'))
                                    <input type="date" wire:model="paramValues.{{ $param }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @else
                                    <input type="text" wire:model="paramValues.{{ $param }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @endif
                                @error('paramValues.'.$param) <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                        
                        <div class="w-full sm:w-auto shrink-0">
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Run Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif(!$hasRun)
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-center">
                <p class="text-indigo-800 font-medium mb-4">Laporan ini tidak memerlukan parameter.</p>
                <button wire:click="runReport" class="inline-flex justify-center items-center gap-2 rounded-lg bg-indigo-600 px-8 py-3 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors">
                    Jalankan Laporan Sekarang
                </button>
            </div>
        @endif

        {{-- Error State --}}
        @if($errorMessage)
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 flex items-start gap-4 text-rose-800">
                <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h4 class="font-bold text-lg mb-1">Query Error</h4>
                    <p class="text-sm font-mono whitespace-pre-wrap">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        {{-- Results Table --}}
        @if($hasRun && !$errorMessage)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800">Data Results ({{ number_format(count($results)) }} rows)</h3>
                </div>
                
                @if(count($results) === 0)
                    <div class="p-12 text-center text-slate-500">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-lg font-medium text-slate-900">Tidak ada data ditemukan</p>
                        <p class="text-sm mt-1">Coba ubah filter parameter Anda.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-16">No</th>
                                    @foreach($columns as $col)
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach($results as $idx => $row)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $idx + 1 }}</td>
                                        @foreach($columns as $col)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                                {{ is_scalar($row->$col) ? $row->$col : json_encode($row->$col) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>
