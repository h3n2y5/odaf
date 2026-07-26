<div>
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" title="Beranda">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-semibold text-slate-800">{{ $appName }}</h1>
            <p class="mt-2 text-slate-600">
                Aplikasi ini dijalankan sepenuhnya dari metadata terkompilasi (Runtime Package).
                Pilih menu di samping untuk membuka layar yang digenerate otomatis.
            </p>

            <dl class="mt-6 grid grid-cols-2 gap-4 max-w-md">
                <div class="rounded-lg bg-white border border-slate-200 p-4">
                    <dt class="text-xs uppercase text-slate-400">Application</dt>
                    <dd class="text-slate-800 font-medium">{{ $appCode }}</dd>
                </div>
                <div class="rounded-lg bg-white border border-slate-200 p-4">
                    <dt class="text-xs uppercase text-slate-400">Package</dt>
                    <dd class="text-slate-800 font-medium font-mono text-sm">{{ $packageVersion }}</dd>
                </div>
            </dl>

            @if (empty($nav))
                <div class="mt-6 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-amber-700 text-sm">
                    Belum ada menu terkompilasi. Pastikan metadata sudah dikompilasi dan diaktifkan.
                </div>
            @endif
        </div>

        @if(!empty($reports))
            <div class="mt-12">
                <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Available Custom Reports
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($reports as $report)
                        <a href="{{ route('odaf.report.viewer', ['appCode' => $appCode, 'reportId' => $report['id']]) }}" wire:navigate class="group bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all p-6 block">
                            <div class="flex items-start justify-between">
                                <div class="p-3 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 transition-colors">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 transition-colors transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">{{ $report['name'] }}</h3>
                            <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ $report['desc'] ?: 'Buka laporan ini...' }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-odaf-shell>
</div>
