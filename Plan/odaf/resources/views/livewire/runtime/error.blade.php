<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full rounded-lg border border-rose-200 bg-white p-6">
        <h1 class="text-lg font-semibold text-rose-700">Runtime belum siap</h1>
        <p class="mt-2 text-slate-600">{{ $message }}</p>
        <div class="mt-4 rounded bg-slate-900 text-slate-100 text-sm font-mono px-4 py-2">
            php artisan odaf:compile {{ $appCode }} --activate
        </div>
    </div>
</div>
