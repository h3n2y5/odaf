<div>
    <x-studio-shell :groups="$groups" title="Data Manager">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">ODAF Studio &mdash; Data Manager</h1>
            <p class="mt-1 text-slate-600">
                Kelola isi seluruh tabel (metadata &amp; data) langsung dari web. Pilih tabel di samping
                atau dari daftar di bawah. Form dan grid dibangkitkan otomatis dari struktur tabel.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $group => $tables)
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        {{ $group }} <span class="text-slate-400">({{ count($tables) }})</span>
                    </h2>
                    <ul class="space-y-1">
                        @foreach ($tables as $table)
                            <li>
                                <a href="{{ route('studio.grid', ['table' => $table]) }}"
                                   class="text-sm text-indigo-600 hover:underline">{{ $table }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-studio-shell>
</div>
