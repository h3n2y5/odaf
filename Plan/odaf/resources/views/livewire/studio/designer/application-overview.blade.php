<div>
    <x-studio-shell :title="$application['OBJECT_NAME'] . ' - Forms'">
        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('studio.designer') }}" wire:navigate class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $application['OBJECT_NAME'] }}</h1>
                    <span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-700 rounded font-mono">{{ $application['OBJECT_CODE'] }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Pilih form yang ingin Anda desain, atau buat menu/tabel baru</p>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="openHdModal"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-600 px-4 py-2 text-indigo-700 font-medium hover:bg-indigo-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h10M4 14h10M4 18h16"></path>
                    </svg>
                    Header + Detail
                </button>
                <button wire:click="openNewModal"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Menu / Tabel Baru
                </button>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">{{ session('error') }}</div>
        @endif

        {{-- Forms List --}}
        @if (count($pages) === 0)
            <div class="text-center py-16 bg-white rounded-lg border border-slate-200">
                <svg class="mx-auto h-14 w-14 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-slate-900">Belum ada form</h3>
                <p class="mt-2 text-sm text-slate-500">Aplikasi ini belum memiliki halaman/form.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($pages as $page)
                    <div class="group bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all flex flex-col">
                        <a href="/studio/designer/form/{{ $page['ID'] }}" wire:navigate class="p-6 block flex-1">
                            <div class="flex items-start justify-between">
                                <div class="p-3 rounded-lg bg-indigo-50 group-hover:bg-indigo-100 transition-colors">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 rounded">{{ $page['PAGE_TYPE'] }}</span>
                            </div>

                            <h3 class="mt-4 text-lg font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                {{ $page['OBJECT_NAME'] }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500 font-mono">{{ $page['OBJECT_CODE'] }}</p>

                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-sm">
                                <span class="text-slate-500">
                                    <span class="font-semibold text-slate-900">{{ $page['FIELD_COUNT'] }}</span> fields
                                </span>
                                <span class="text-slate-400">{{ $page['DATASET_CODE'] ?? 'No dataset' }}</span>
                            </div>
                        </a>
                        <div class="px-6 pb-6 pt-2 flex items-center gap-4">
                            <a href="/studio/designer/form/{{ $page['ID'] }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600">
                                Design form
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a>
                            @if(!empty($page['DATASET_ID']))
                                <a href="/studio/designer/workflow/{{ $page['DATASET_ID'] }}" wire:navigate
                                   class="inline-flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Workflow
                                </a>
                            @endif
                            <button wire:click="deletePage('{{ $page['ID'] }}')" 
                                    wire:confirm="Yakin menghapus menu ini beserta semua metadatanya? Jika ini menu transaksi, TABEL FISIK-nya juga akan di-drop!"
                                    class="ml-auto inline-flex items-center text-sm font-medium text-slate-400 hover:text-rose-600 transition-colors"
                                    title="Hapus Menu & Tabel">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Modal: Menu/Tabel Baru --}}
        @if ($showNewModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Buat Menu / Tabel Baru</h3>
                        <button wire:click="closeNewModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Nama Menu / Entitas <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model.live.debounce.300ms="newEntityName"
                                   class="w-full rounded-lg border-slate-300" placeholder="mis. Supplier" autofocus>
                            @error('newEntityName')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Tabel (otomatis)</label>
                            <input type="text" value="{{ $this->tableNamePreview }}" readonly
                                   class="w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-slate-600" placeholder="T_...">
                            <p class="mt-1 text-xs text-slate-400">Tabel transaksi diberi awalan <span class="font-mono">T_</span>. Kolom dasar: ID, Nama, audit.</p>
                        </div>

                        <div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-xs text-blue-700">
                            Setelah dibuat, tabel + menu langsung dikompilasi. Anda akan diarahkan ke Form Builder untuk menambah kolom/field lain.
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                        <button wire:click="closeNewModal"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            Batal
                        </button>
                        <button wire:click="createTable"
                                wire:loading.attr="disabled" wire:target="createTable"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="createTable">Buat &amp; Buka</span>
                            <span wire:loading wire:target="createTable">Membuat...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal: Header + Detail --}}
        @if ($showHdModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Buat Header + Detail</h3>
                        <button wire:click="closeHdModal" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Nama Header <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="hdHeaderName"
                                   class="w-full rounded-lg border-slate-300" placeholder="mis. Purchase Order" autofocus>
                            @error('hdHeaderName')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Nama Detail (baris) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="hdDetailName"
                                   class="w-full rounded-lg border-slate-300" placeholder="mis. PO Line">
                            @error('hdDetailName')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-xs text-blue-700">
                            Dibuat 2 tabel: header (mis. <span class="font-mono">{{ strtoupper($application['OBJECT_CODE'] ?? 'APP') }}_T_PURCHASE_ORDER</span>) &amp; detail (mis. <span class="font-mono">{{ strtoupper($application['OBJECT_CODE'] ?? 'APP') }}_T_PO_LINE</span>) dengan kolom FK ke header. Tambah kolom lain via Form Builder.
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                        <button wire:click="closeHdModal"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            Batal
                        </button>
                        <button wire:click="createHeaderDetail"
                                wire:loading.attr="disabled" wire:target="createHeaderDetail"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="createHeaderDetail">Buat &amp; Buka Header</span>
                            <span wire:loading wire:target="createHeaderDetail">Membuat...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </x-studio-shell>
</div>
