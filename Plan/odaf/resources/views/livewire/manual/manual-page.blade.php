<div>
    <x-odaf-shell :appCode="$appCode" :appName="$appName" :nav="$nav" title="Manual Pengguna">
        <style>
            .manual-prose { color: #334155; line-height: 1.7; }
            .manual-prose h1, .manual-prose h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 1.25rem 0 .75rem; }
            .manual-prose h3 { font-size: 1.05rem; font-weight: 600; color: #1e293b; margin: 1rem 0 .5rem; }
            .manual-prose p { margin: .6rem 0; }
            .manual-prose ul, .manual-prose ol { margin: .6rem 0; padding-left: 1.4rem; }
            .manual-prose ul { list-style: disc; }
            .manual-prose ol { list-style: decimal; }
            .manual-prose li { margin: .25rem 0; }
            .manual-prose a { color: #4f46e5; font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
            .manual-prose a:hover { color: #4338ca; }
            .manual-prose code { background: #f1f5f9; color: #be185d; padding: .1rem .35rem; border-radius: .25rem; font-size: .85em; font-family: ui-monospace, monospace; }
            .manual-prose pre { background: #0f172a; color: #e2e8f0; padding: .8rem 1rem; border-radius: .5rem; overflow-x: auto; margin: .75rem 0; }
            .manual-prose pre code { background: transparent; color: inherit; padding: 0; }
            .manual-prose blockquote { border-left: 4px solid #c7d2fe; background: #eef2ff; padding: .6rem 1rem; margin: .75rem 0; border-radius: .25rem; color: #3730a3; }
            .manual-prose table { border-collapse: collapse; margin: .75rem 0; width: 100%; }
            .manual-prose th, .manual-prose td { border: 1px solid #e2e8f0; padding: .4rem .6rem; text-align: left; }
            .manual-prose th { background: #f8fafc; }
            html { scroll-behavior: smooth; }
        </style>

        {{-- Flash --}}
        @if (session('manual_status'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 shrink-0">{{ session('manual_status') }}</div>
        @endif
        @if (session('manual_error'))
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 shrink-0">{{ session('manual_error') }}</div>
        @endif

        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between gap-4 shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Manual Pengguna ODAF</h1>
                <p class="mt-1 text-sm text-slate-500">Panduan membuat menu baru — dapat dibaca semua pengguna@if($isAdmin), diedit oleh superuser@endif.</p>
            </div>
            @if ($isAdmin)
                <button wire:click="newSection"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Bagian
                </button>
            @endif
        </div>

        {{-- Quick access --}}
        <div class="mb-6 flex flex-wrap gap-2 shrink-0">
            <a href="/studio" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-indigo-300">Data Manager</a>
            <a href="/studio/designer" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-indigo-300">Applications</a>
            <a href="/studio/designer/lov/new" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-indigo-300">Visual Designer LOV</a>
            <a href="/app/{{ $appCode }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-indigo-300">Runtime</a>
        </div>

        {{-- Editor (admin) --}}
        @if ($isAdmin && $showEditor)
            <div class="mb-8 rounded-xl border border-indigo-200 bg-white shadow-sm shrink-0">
                <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">{{ $editingId ? 'Ubah Bagian' : 'Bagian Baru' }}</h2>
                    <button wire:click="cancel" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-5">
                    {{-- Form --}}
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Judul <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="formTitle" class="w-full rounded-lg border-slate-300" placeholder="mis. Membuat Menu Baru">
                                @error('formTitle')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="w-28">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Urutan</label>
                                <input type="number" wire:model="formOrder" class="w-full rounded-lg border-slate-300">
                                @error('formOrder')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Isi (Markdown) <span class="text-rose-500">*</span></label>
                            <textarea wire:model.live.debounce.500ms="formBody" rows="16"
                                      class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                      placeholder="Tulis dengan Markdown. Contoh link ke menu: [Data Manager](/studio)"></textarea>
                            @error('formBody')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-slate-400">Tautan ke menu asli: <code>[Judul](/studio)</code>, <code>(/studio/designer)</code>, <code>(/studio/designer/lov/new)</code>, <code>(/app/{{ $appCode }})</code>.</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button wire:click="cancel" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60">Simpan</button>
                        </div>
                    </div>
                    {{-- Live preview --}}
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 overflow-y-auto max-h-[32rem]">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Pratinjau</div>
                        <div class="manual-prose">{!! $previewHtml ?: '<p class="text-slate-400">Mulai mengetik untuk melihat pratinjau...</p>' !!}</div>
                    </div>
                </div>
            </div>
        @endif

        @if (count($sections) === 0)
            <div class="rounded-lg border border-slate-200 bg-white p-10 text-center text-slate-500">
                Belum ada konten manual.@if($isAdmin) Klik <span class="font-medium text-indigo-600">Tambah Bagian</span> untuk mulai menulis.@endif
            </div>
        @else
            <div class="flex gap-8 items-start">
                {{-- TOC --}}
                <aside class="hidden lg:block w-60 shrink-0 sticky top-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Daftar Isi</div>
                        <nav class="space-y-1">
                            @foreach ($sections as $i => $section)
                                <a href="#{{ $section['SLUG'] }}" class="block text-sm text-slate-600 hover:text-indigo-600 py-1 border-l-2 border-transparent hover:border-indigo-400 pl-2">
                                    {{ $i + 1 }}. {{ $section['TITLE'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Sections --}}
                <div class="flex-1 min-w-0 space-y-6">
                    @foreach ($sections as $i => $section)
                        <article id="{{ $section['SLUG'] }}" wire:key="sec-{{ $section['ID'] }}"
                                 class="scroll-mt-4 rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                                <h2 class="text-lg font-semibold text-slate-900">{{ $i + 1 }}. {{ $section['TITLE'] }}</h2>
                                @if ($isAdmin)
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button wire:click="edit('{{ $section['ID'] }}')" class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="Ubah">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button wire:click="delete('{{ $section['ID'] }}')" wire:confirm="Hapus bagian ini?" class="p-2 text-rose-600 hover:bg-rose-50 rounded" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="px-6 py-5 manual-prose">
                                {!! $section['HTML'] !!}
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </x-odaf-shell>
</div>
