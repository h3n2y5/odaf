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
                <p class="mt-1 text-sm text-slate-500">Kelola Forms dan Custom Reports untuk aplikasi ini.</p>
            </div>

            <div class="flex items-center gap-2">
                @if($activeTab === 'pages')
                    <button wire:click="openCustomPageModal"
                            class="inline-flex items-center gap-2 rounded-lg bg-pink-600 px-4 py-2 text-white font-medium hover:bg-pink-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Buat Custom Page
                    </button>
                    <button wire:click="openHdModal"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-600 px-4 py-2 text-indigo-700 font-medium hover:bg-indigo-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h10M4 14h10M4 18h16"></path></svg>
                        Header + Detail
                    </button>
                    <button wire:click="openNewModal"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Menu / Tabel Baru
                    </button>
                @elseif($activeTab === 'reports')
                    <a href="{{ route('studio.report.designer', ['appId' => $appId, 'reportId' => 'new']) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white font-medium hover:bg-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Buat Report Baru
                    </a>
                @else
                    <button wire:click="openCustomMenuModal"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add System Module
                    </button>
                @endif
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="border-b border-slate-200 mb-6 flex items-center gap-6">
            <button wire:click="$set('activeTab', 'pages')" class="pb-3 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'pages' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Forms & Pages ({{ count($pages) }})
                </span>
            </button>
            <button wire:click="$set('activeTab', 'reports')" class="pb-3 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'reports' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Custom Reports ({{ count($reports) }})
                </span>
            </button>
            <button wire:click="$set('activeTab', 'menus')" class="pb-3 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'menus' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
                    System Modules ({{ count($customMenus) }})
                </span>
            </button>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">{{ session('error') }}</div>
        @endif

        @if($activeTab === 'pages')
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
                            @if (($page['PAGE_TYPE'] ?? '') === 'CUSTOM')
                                <a href="/studio/designer/custom-page/{{ $page['ID'] }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-pink-600">
                                    Edit Custom Page
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </a>
                            @else
                                <a href="/studio/designer/form/{{ $page['ID'] }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600">
                                    Design form
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </a>
                            @endif
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
        @elseif($activeTab === 'reports')
            {{-- Reports List --}}
            @if (count($reports) === 0)
                <div class="text-center py-16 bg-white rounded-lg border border-slate-200">
                    <svg class="mx-auto h-14 w-14 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">Belum ada report</h3>
                    <p class="mt-2 text-sm text-slate-500">Aplikasi ini belum memiliki custom report.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($reports as $report)
                        <div class="group bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all flex flex-col">
                            <a href="{{ route('studio.report.designer', ['appId' => $appId, 'reportId' => $report['id']]) }}" wire:navigate class="p-6 block flex-1">
                                <div class="flex items-start justify-between">
                                    <div class="p-3 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 transition-colors">
                                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                </div>
                                <h3 class="mt-4 text-lg font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">{{ $report['name'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ $report['desc'] ?: 'Tidak ada deskripsi' }}</p>
                            </a>
                            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 rounded-b-lg flex items-center justify-between">
                                <button wire:click="deleteReport('{{ $report['id'] }}')" 
                                        wire:confirm="Yakin menghapus report ini?"
                                        class="inline-flex items-center text-sm font-medium text-rose-500 hover:text-rose-700 transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Custom Menus List --}}
            @if (count($customMenus) === 0)
                <div class="text-center py-16 bg-white rounded-lg border border-slate-200">
                    <svg class="mx-auto h-14 w-14 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">Belum ada System Module</h3>
                    <p class="mt-2 text-sm text-slate-500">Klik 'Add System Module' untuk menautkan modul kustom.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($customMenus as $cmenu)
                        <div class="group bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col hover:border-indigo-300 transition-colors">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600 rounded">{{ $cmenu['module'] }}</span>
                                </div>
                                <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $cmenu['name'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500 font-mono line-clamp-1">{{ $cmenu['route'] }}</p>
                            </div>
                            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 rounded-b-lg flex items-center justify-between mt-auto">
                                <a href="/studio/designer/custom-page/{{ $cmenu['id'] }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                    Edit Custom Page
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </a>
                                <button wire:click="deletePage('{{ $cmenu['id'] }}')" 
                                        wire:confirm="Yakin menghapus module kustom ini?"
                                        class="inline-flex items-center text-sm font-medium text-rose-400 hover:text-rose-600 transition-colors"
                                        title="Hapus Module">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif


    {{-- Modal Add Custom Menu --}}
    @if ($showCustomMenuModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" wire:click="closeCustomMenuModal" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-slate-100">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-xl leading-6 font-bold text-slate-900" id="modal-title">
                                    Tambah Modul Sistem (Visual Menu Linker)
                                </h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Tautkan fungsionalitas lanjutan (POS, Security, Workflow, dll) ke dalam aplikasi ini.
                                </p>
                            </div>
                        </div>
                    </div>
                    <form wire:submit="createCustomMenu">
                        <div class="px-6 py-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($availableSystemModules as $sysMod)
                                    <button type="button" 
                                            wire:click="selectSystemModule('{{ $sysMod['route'] }}', '{{ addslashes($sysMod['name']) }}')"
                                            class="text-left px-4 py-3 rounded-lg border-2 transition-all {{ $customMenuRoute === $sysMod['route'] ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-300' }}">
                                        <div class="font-medium text-slate-900">{{ $sysMod['name'] }}</div>
                                        <div class="text-xs text-slate-500 font-mono mt-1">{{ $sysMod['route'] }}</div>
                                    </button>
                                @endforeach
                            </div>

                            <div class="border-t border-slate-200 pt-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Nama Menu <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="customMenuName" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-10 px-3">
                                    @error('customMenuName') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-semibold text-slate-700">Route Link <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="customMenuRoute" class="mt-1 block w-full rounded-md border-slate-300 bg-slate-50 shadow-sm sm:text-sm h-10 px-3" readonly>
                                    @error('customMenuRoute') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Tambahkan ke Aplikasi
                            </button>
                            <button type="button" wire:click="closeCustomMenuModal" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

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
        {{-- Modal Custom Page --}}
        @if($showCustomPageModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Buat Custom Page Baru</h3>
                        <button wire:click="closeCustomPageModal" class="text-slate-400 hover:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <form wire:submit.prevent="createCustomPage" class="p-6">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Halaman Kustom</label>
                            <input type="text" wire:model.live="customPageName" class="w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Point of Sale, Promo Simulator">
                            @error('customPageName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-2">Halaman kustom memungkinkan Anda menuliskan kode HTML/Tailwind dan logika PHP secara bebas tanpa terikat kerangka form standar.</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8">
                            <button type="button" wire:click="closeCustomPageModal" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition-colors">Batal</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Buat Halaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </x-studio-shell>
</div>
