<div>
    <x-studio-shell :title="'Workflow: ' . $dataset['OBJECT_NAME']">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('studio.designer.app.overview', ['appId' => $dataset['APPLICATION_ID']]) }}" wire:navigate class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Workflow Designer</h1>
            </div>
            @if($workflow)
                <div class="flex items-center gap-2">
                    <button wire:click="saveWorkflow"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Konfigurasi
                    </button>
                </div>
            @endif
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Dataset: {{ $dataset['OBJECT_NAME'] }} ({{ $dataset['OBJECT_CODE'] }})</h2>
                    <p class="text-sm text-slate-500 mt-1">Konfigurasi alur persetujuan (approval) berjenjang.</p>
                </div>
                <div>
                    @if(!$workflow)
                        <button wire:click="enableWorkflow" class="inline-flex items-center gap-2 rounded-lg border border-indigo-600 px-4 py-2 text-indigo-700 font-medium hover:bg-indigo-50">
                            Aktifkan Workflow
                        </button>
                    @else
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Workflow Aktif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($workflow)
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-900 mb-4">Level Approval Berjenjang</h3>
                
                <div class="space-y-4">
                    @foreach($levels as $index => $level)
                        <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-700 font-bold rounded-full flex items-center justify-center">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Level</label>
                                    <input type="text" wire:model.defer="levels.{{ $index }}.name" class="w-full rounded-md border-slate-300 shadow-sm sm:text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Manager">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Status (Internal Code)</label>
                                    <input type="text" wire:model.defer="levels.{{ $index }}.pending_state" class="w-full rounded-md border-slate-300 shadow-sm sm:text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: PENDING_MANAGER">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Role Berwenang (Approver)</label>
                                    <select wire:model.defer="levels.{{ $index }}.role_id" class="w-full rounded-md border-slate-300 shadow-sm sm:text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Pilih Role --</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role['ID'] }}">{{ $role['OBJECT_NAME'] }} ({{ $role['OBJECT_CODE'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button wire:click="removeLevel({{ $index }})" class="mt-6 text-rose-500 hover:text-rose-700" title="Hapus Level">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button wire:click="addLevel" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Level
                    </button>
                </div>
                
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <h4 class="text-sm font-medium text-slate-900 mb-2">Penjelasan Alur (FSM):</h4>
                    <ul class="list-disc list-inside text-sm text-slate-500 space-y-1">
                        <li>Saat data baru disimpan, otomatis berstatus <strong>DRAFT</strong> (Start).</li>
                        <li>User pembuat dapat melakukan <strong>Submit</strong> untuk memindahkan data ke Level 1.</li>
                        <li>Setiap level yang berwenang bisa melakukan <strong>Approve</strong> (lanjut ke level berikutnya) atau <strong>Reject</strong> (kembali ke DRAFT).</li>
                        <li>Setelah level terakhir di-approve, status akan menjadi <strong>COMPLETED</strong> (Selesai).</li>
                        <li>Peringatan: Pastikan Anda melakukan <strong>Kompilasi Aplikasi</strong> (di dashboard aplikasi) setelah menyimpan workflow agar Engine menangkap perubahan ini.</li>
                    </ul>
                </div>
            </div>
        @endif
    </x-studio-shell>
</div>
