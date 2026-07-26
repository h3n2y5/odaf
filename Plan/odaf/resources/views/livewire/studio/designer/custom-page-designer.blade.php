<div>
    <x-studio-shell :title="'Visual Page Builder - ' . ($application['OBJECT_NAME'] ?? $application['object_name'] ?? '')">
        
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('studio.designer.app.overview', $appId) }}" wire:navigate class="p-2.5 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $pageName }}
                    </h1>
                    <p class="text-sm text-slate-500 font-medium mt-0.5">Visual Page Builder</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Developer Mode Toggle -->
                <button wire:click="toggleDeveloperMode" class="flex items-center gap-2 px-4 py-2 rounded-xl border {{ $developerMode ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-slate-200 text-slate-600' }} hover:shadow-sm transition-all text-sm font-bold">
                    <div class="relative w-9 h-5 rounded-full transition-colors {{ $developerMode ? 'bg-indigo-500' : 'bg-slate-300' }}">
                        <div class="absolute top-0.5 left-0.5 bg-white w-4 h-4 rounded-full transition-transform shadow-sm {{ $developerMode ? 'translate-x-4' : 'translate-x-0' }}"></div>
                    </div>
                    Dev Mode
                </button>

                <button wire:click="save" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-white font-bold shadow-md hover:bg-indigo-600 hover:shadow-indigo-500/25 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Desain
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-5 py-4 text-emerald-700 font-medium flex items-center gap-3 animate-pulse-once">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="flex gap-8 h-[calc(100vh-190px)]">
            <!-- Sidebar: Block Library -->
            <div class="w-80 shrink-0 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden relative z-10">
                <div class="px-6 py-5 border-b border-slate-100 bg-white/80 backdrop-blur-md sticky top-0">
                    <h3 class="font-black text-slate-800 tracking-tight text-lg">Komponen</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Pilih elemen untuk merakit halaman</p>
                </div>
                
                <div class="p-6 flex-1 overflow-y-auto space-y-6 bg-slate-50/30">
                    <div class="grid grid-cols-2 gap-4">
                        <button wire:click="addBlock('hero')" class="flex flex-col items-center justify-center p-5 rounded-2xl border-2 border-transparent bg-white shadow-sm ring-1 ring-slate-200 hover:ring-indigo-500 hover:shadow-md hover:-translate-y-1 transition-all group">
                            <div class="p-3.5 bg-indigo-50 text-indigo-500 rounded-xl group-hover:bg-indigo-500 group-hover:text-white transition-colors mb-3">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                            </div>
                            <div class="text-sm font-bold text-slate-700">Hero</div>
                        </button>
                        
                        <button wire:click="addBlock('stats')" class="flex flex-col items-center justify-center p-5 rounded-2xl border-2 border-transparent bg-white shadow-sm ring-1 ring-slate-200 hover:ring-emerald-500 hover:shadow-md hover:-translate-y-1 transition-all group">
                            <div class="p-3.5 bg-emerald-50 text-emerald-500 rounded-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors mb-3">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            <div class="text-sm font-bold text-slate-700">Stats</div>
                        </button>
                    </div>

                    @if($developerMode)
                    <div class="pt-6 border-t border-slate-200 border-dashed">
                        <div class="text-[11px] font-black uppercase text-indigo-400 tracking-widest mb-4 px-1">Advanced Mode</div>
                        <button wire:click="addBlock('custom')" class="w-full flex items-center gap-4 p-4 rounded-2xl border border-indigo-200 bg-indigo-50/50 hover:bg-indigo-50 hover:border-indigo-300 hover:shadow-sm transition-all text-left group">
                            <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            </div>
                            <div>
                                <div class="font-bold text-indigo-900">Custom Code</div>
                                <div class="text-[11px] font-medium text-indigo-600/70 mt-1 uppercase tracking-wide">Blade & PHP Script</div>
                            </div>
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Main Canvas -->
            <div class="flex-1 rounded-2xl bg-[#F8FAFC] relative overflow-hidden flex flex-col shadow-inner border border-slate-200/70" 
                 style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px;">
                
                <div class="absolute inset-0 pointer-events-none shadow-[inset_0_4px_24px_rgba(0,0,0,0.02)] rounded-2xl z-0"></div>

                <div class="flex-1 overflow-y-auto p-8 relative z-10 space-y-8">
                    @if(count($blocks) === 0)
                        <div class="h-full flex flex-col items-center justify-center text-center p-12 max-w-md mx-auto">
                            <div class="w-28 h-28 mb-8 rounded-full bg-white shadow-2xl shadow-indigo-500/10 flex items-center justify-center border border-slate-100 ring-8 ring-white/50 animate-bounce-slow">
                                <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <h3 class="text-3xl font-black text-slate-800 tracking-tight">Kanvas Kosong</h3>
                            <p class="text-slate-500 mt-4 text-lg leading-relaxed">Mulai merakit halaman Anda dengan menambahkan komponen dari perpustakaan di sebelah kiri.</p>
                        </div>
                    @else
                        @foreach($blocks as $index => $block)
                            <div class="group relative bg-white/70 backdrop-blur-md rounded-2xl shadow-sm border border-slate-200/80 hover:shadow-lg hover:border-indigo-300 hover:bg-white transition-all duration-300" wire:key="block-{{ $block['id'] }}">
                                
                                <!-- Floating Action Menu (Shows on hover) -->
                                <div class="absolute -right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0 transition-all flex flex-col gap-2 bg-white p-2 rounded-xl shadow-xl border border-slate-100 z-20">
                                    <button wire:click="moveBlock({{ $index }}, -1)" @disabled($index === 0) class="p-2 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-30 disabled:hover:bg-transparent">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    </button>
                                    <button wire:click="moveBlock({{ $index }}, 1)" @disabled($index === count($blocks) - 1) class="p-2 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-30 disabled:hover:bg-transparent">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div class="h-px w-full bg-slate-200 my-1"></div>
                                    <button wire:click="removeBlock({{ $index }})" class="p-2 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                                
                                <!-- Block Header -->
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center 
                                            {{ $block['type'] === 'hero' ? 'bg-indigo-100 text-indigo-600' : ($block['type'] === 'stats' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-600') }}">
                                            @if($block['type'] === 'hero')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"></path></svg>
                                            @elseif($block['type'] === 'stats')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4"></path></svg>
                                            @endif
                                        </div>
                                        <span class="font-bold text-slate-800 uppercase tracking-wider text-xs">
                                            {{ $block['type'] }} BLOCK
                                        </span>
                                    </div>
                                    <div class="text-xs font-mono text-slate-400 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                        Seret atau gunakan menu di kanan
                                    </div>
                                </div>
                                
                                <!-- Block Configuration Body -->
                                <div class="p-6">
                                    @if($block['type'] === 'hero')
                                        <div class="grid grid-cols-1 gap-5">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Judul Utama</label>
                                                <input type="text" wire:model="blocks.{{ $index }}.config.title" class="block w-full rounded-xl border-slate-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-3 font-medium" placeholder="Masukkan judul yang menarik">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Deskripsi Subjudul</label>
                                                <textarea wire:model="blocks.{{ $index }}.config.subtitle" rows="3" class="block w-full rounded-xl border-slate-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-3" placeholder="Tuliskan penjelasan singkat mengenai tujuan halaman ini..."></textarea>
                                            </div>
                                            <div class="grid grid-cols-2 gap-5 p-5 bg-slate-50 rounded-xl border border-slate-100">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Teks Tombol (Opsional)</label>
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.buttonText" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 sm:text-sm" placeholder="Mulai Sekarang">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Route / URL Tombol</label>
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.buttonRoute" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 sm:text-sm" placeholder="/app/dashboard">
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($block['type'] === 'stats')
                                        <div class="space-y-5">
                                            <div class="flex items-center gap-2">
                                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Konfigurasi Kartu Metrik</label>
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-bold">Menampilkan hingga 3 kolom</span>
                                            </div>
                                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                                                <div class="p-5 border border-slate-200 rounded-xl bg-white shadow-sm hover:border-emerald-300 transition-colors group/stat">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest group-hover/stat:text-emerald-500 transition-colors">Kartu 1</label>
                                                    </div>
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat1_label" class="block w-full text-sm rounded-lg border-slate-200 bg-slate-50 focus:bg-white mb-3" placeholder="Label (mis: Pendapatan)">
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat1_value" class="block w-full text-lg rounded-lg border-slate-200 font-bold focus:border-emerald-500 focus:ring-emerald-500" placeholder="Rp 0">
                                                </div>
                                                <div class="p-5 border border-slate-200 rounded-xl bg-white shadow-sm hover:border-emerald-300 transition-colors group/stat">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest group-hover/stat:text-emerald-500 transition-colors">Kartu 2</label>
                                                    </div>
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat2_label" class="block w-full text-sm rounded-lg border-slate-200 bg-slate-50 focus:bg-white mb-3" placeholder="Label (mis: Pesanan)">
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat2_value" class="block w-full text-lg rounded-lg border-slate-200 font-bold focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
                                                </div>
                                                <div class="p-5 border border-slate-200 rounded-xl bg-white shadow-sm hover:border-emerald-300 transition-colors group/stat">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest group-hover/stat:text-emerald-500 transition-colors">Kartu 3</label>
                                                    </div>
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat3_label" class="block w-full text-sm rounded-lg border-slate-200 bg-slate-50 focus:bg-white mb-3" placeholder="Label">
                                                    <input type="text" wire:model="blocks.{{ $index }}.config.stat3_value" class="block w-full text-lg rounded-lg border-slate-200 font-bold focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($block['type'] === 'custom')
                                        @if(!$developerMode)
                                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-8 text-center flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-indigo-50 flex items-center justify-center mb-4 text-indigo-300">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                </div>
                                                <h4 class="text-lg font-bold text-indigo-900">Custom Code Hidden</h4>
                                                <p class="text-indigo-600/70 text-sm mt-2 max-w-md">Aktifkan <strong>Developer Mode</strong> di pojok kanan atas untuk melihat dan mengedit blok kustom ini.</p>
                                            </div>
                                        @else
                                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                                <div class="bg-[#0f172a] rounded-xl overflow-hidden flex flex-col h-[400px] shadow-lg border border-slate-700/50">
                                                    <div class="px-4 py-3 bg-[#1e293b] flex items-center gap-2 border-b border-slate-700/50">
                                                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
                                                        <span class="ml-2 text-xs font-bold text-slate-400 uppercase tracking-wider">Blade HTML</span>
                                                    </div>
                                                    <textarea wire:model="customViewBlade" class="flex-1 w-full bg-transparent text-emerald-400 font-mono text-sm border-none focus:ring-0 p-5 resize-none leading-relaxed" spellcheck="false" placeholder="<!-- Write your Tailwind HTML here -->"></textarea>
                                                </div>
                                                <div class="bg-[#0f172a] rounded-xl overflow-hidden flex flex-col h-[400px] shadow-lg border border-slate-700/50">
                                                    <div class="px-4 py-3 bg-[#1e293b] flex items-center gap-2 border-b border-slate-700/50">
                                                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></div>
                                                        <div class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></div>
                                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
                                                        <span class="ml-2 text-xs font-bold text-slate-400 uppercase tracking-wider">PHP Livewire</span>
                                                    </div>
                                                    <textarea wire:model="customLogicPhp" class="flex-1 w-full bg-transparent text-indigo-300 font-mono text-sm border-none focus:ring-0 p-5 resize-none leading-relaxed" spellcheck="false" placeholder="// Write Livewire logic here"></textarea>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </x-studio-shell>
    
    <style>
        .animate-bounce-slow {
            animation: bounce 3s infinite;
        }
        .animate-pulse-once {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) 1;
        }
    </style>
</div>
