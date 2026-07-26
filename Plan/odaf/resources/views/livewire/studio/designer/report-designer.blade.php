<div>
    <x-studio-shell :title="($reportId === 'new' ? 'Buat Report' : 'Edit Report') . ' - ' . ($application['OBJECT_NAME'] ?? $application['object_name'] ?? '')">
        
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('studio.designer.app.overview', $appId) }}" wire:navigate class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $reportId === 'new' ? 'Buat Report Baru' : 'Edit Report: ' . $reportName }}
                </h1>
            </div>
            
            <button wire:click="save" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-white font-bold shadow-sm hover:bg-emerald-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Simpan Report
            </button>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Left Panel: Metadata --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Informasi Report
                    </h3>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Report <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="reportName" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Mis. Penjualan Bulanan">
                        @error('reportName') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Deskripsi</label>
                        <textarea wire:model="reportDesc" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Jelaskan fungsi report ini..."></textarea>
                    </div>
                </div>

                <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-6">
                    <h3 class="font-bold text-indigo-800 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Parameter Terdeteksi
                    </h3>
                    <p class="text-xs text-indigo-600 mb-4">Gunakan titik dua (<code class="font-bold">:</code>) pada query untuk membuat parameter dinamis. Sistem akan otomatis membuatkan form input untuk user.</p>
                    
                    @if(count($detectedParams) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($detectedParams as $param)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-600 text-white text-xs font-bold font-mono shadow-sm">
                                    <span class="text-indigo-200">:</span>{{ $param }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 border border-indigo-200 border-dashed rounded-lg text-center text-indigo-400 text-sm font-medium bg-white/50">
                            Belum ada parameter terdeteksi
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Panel: SQL Query --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 h-full flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between rounded-t-xl">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10V3a1 1 0 011-1h14a1 1 0 011 1v7M4 14v7a1 1 0 001 1h14a1 1 0 001-1v-7M4 14h16M4 10h16"></path></svg>
                            Raw SQL Query
                        </h3>
                    </div>
                    
                    <div class="flex-1 p-0 relative">
                        <textarea wire:model.live.debounce.500ms="sqlQuery" 
                                  class="w-full h-[500px] font-mono text-sm border-0 focus:ring-0 resize-none p-6 text-slate-700 leading-relaxed bg-slate-900 !text-slate-100 rounded-b-xl" 
                                  placeholder="SELECT * FROM ODAF.PURCHASE_ORDER WHERE COMPANY = :company..."
                                  spellcheck="false"></textarea>
                        
                        @error('sqlQuery') 
                            <div class="absolute bottom-4 left-4 right-4 bg-rose-100 text-rose-700 px-4 py-2 rounded shadow-sm text-sm border border-rose-200 font-medium">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

        </div>
    </x-studio-shell>
</div>
