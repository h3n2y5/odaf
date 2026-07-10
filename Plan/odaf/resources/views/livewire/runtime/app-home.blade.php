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
    </x-odaf-shell>
</div>
