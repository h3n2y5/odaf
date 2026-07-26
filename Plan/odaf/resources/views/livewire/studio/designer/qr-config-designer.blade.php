<div>
    <x-odaf-shell app-code="studio" app-name="ODAF Studio" title="QR Code Designer">
        <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">QR Code Designer</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Konfigurasi QR Code untuk form: <span class="font-semibold text-slate-700">{{ $page['OBJECT_NAME'] ?? $pageId }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('studio.designer.form', ['pageId' => $pageId]) }}"
                       wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
                        &larr; Kembali ke Form Builder
                    </a>
                    <button wire:click="addNew" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        + Tambah QR Code
                    </button>
                </div>
            </div>

            @if (session()->has('odaf.status'))
                <div class="rounded-md bg-emerald-50 p-4 mb-6 border border-emerald-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-emerald-800">{{ session('odaf.status') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Daftar QR Configs --}}
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl mb-8">
                <table class="min-w-full divide-y divide-slate-300">
                    <thead>
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900">Kode</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Nama</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Tipe QR</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Posisi</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($qrConfigs as $qr)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900">
                                    {{ $qr['OBJECT_CODE'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $qr['OBJECT_NAME'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                        {{ $qr['QR_TYPE'] ?? 'DOCUMENT_LINK' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    {{ $qr['POSITION'] ?? 'TOP_RIGHT' }}
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button wire:click="edit('{{ $qr['OBJECT_ID'] }}')" class="text-indigo-600 hover:text-indigo-900 mr-4">Ubah</button>
                                    <button wire:click="delete('{{ $qr['OBJECT_ID'] }}')" wire:confirm="Hapus konfigurasi QR ini?" class="text-rose-600 hover:text-rose-900">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                                    Belum ada konfigurasi QR Code untuk form ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Editor Modal --}}
            @if ($showEditor)
                <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-slate-500 bg-opacity-75" aria-hidden="true"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                            
                            <div class="mb-5 border-b border-slate-200 pb-3">
                                <h3 class="text-lg font-medium leading-6 text-slate-900">
                                    {{ $editor['id'] ? 'Ubah' : 'Tambah' }} Konfigurasi QR Code
                                </h3>
                            </div>

                            <form wire:submit.prevent="save" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Kode QR</label>
                                        <input type="text" wire:model="editor.code" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Nama</label>
                                        <input type="text" wire:model="editor.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Tipe Aksi QR Code</label>
                                    <select wire:model.live="editor.qr_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="DOCUMENT_LINK">Link ke Dokumen Ini (Default)</option>
                                        <option value="CROSS_DOCUMENT">Link ke Dokumen Lain (Cross-Document)</option>
                                        <option value="WORKFLOW_ACTION">Aksi Workflow Otomatis</option>
                                    </select>
                                </div>

                                @if ($editor['qr_type'] === 'CROSS_DOCUMENT')
                                    <div class="bg-slate-50 p-4 rounded-md border border-slate-200 space-y-4">
                                        <p class="text-xs text-slate-500">Pilih dokumen tujuan saat QR ini di-scan. Anda juga bisa menentukan kolom Foreign Key untuk di-prefill.</p>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Target Page</label>
                                                <select wire:model="editor.target_page_code" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    <option value="">-- Pilih Page --</option>
                                                    @foreach ($availablePages as $p)
                                                        <option value="{{ $p['OBJECT_CODE'] }}">{{ $p['OBJECT_NAME'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">FK Column (Prefill)</label>
                                                <input type="text" wire:model="editor.fk_column" placeholder="Misal: PO_ID" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($editor['qr_type'] === 'WORKFLOW_ACTION' || $editor['qr_type'] === 'CROSS_DOCUMENT')
                                    <div class="bg-indigo-50 p-4 rounded-md border border-indigo-100">
                                        <label class="block text-sm font-medium text-slate-700">Otomatis Eksekusi Workflow Action</label>
                                        <p class="text-xs text-slate-500 mb-2">Pilih aksi workflow yang akan langsung dikonfirmasi saat QR di-scan.</p>
                                        <select wire:model="editor.target_action" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Tidak Ada Aksi --</option>
                                            @foreach ($availableWorkflowActions as $a)
                                                <option value="{{ $a['ACTION_CODE'] }}">{{ $a['OBJECT_NAME'] }} ({{ $a['ACTION_CODE'] }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Posisi di Form</label>
                                        <select wire:model="editor.position" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="TOP_RIGHT">Kanan Atas (Default)</option>
                                            <option value="TOP_LEFT">Kiri Atas</option>
                                            <option value="BOTTOM_RIGHT">Kanan Bawah</option>
                                            <option value="HEADER_CENTER">Tengah Header</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Ukuran (Pixel)</label>
                                        <input type="number" wire:model="editor.size_px" min="50" max="500" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>

                                <div class="flex items-center gap-6 mt-4">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="editor.show_on_form" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-700">Tampilkan di Form UI</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="editor.show_on_print" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-slate-700">Tampilkan saat Print (Ctrl+P)</span>
                                    </label>
                                </div>

                                @if ($errors->any())
                                    <div class="text-sm text-rose-600 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <p>{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-6 flex justify-end gap-3 sm:mt-5">
                                    <button type="button" wire:click="$set('showEditor', false)" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none sm:text-sm">
                                        Batal
                                    </button>
                                    <button type="submit" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none sm:text-sm">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </x-odaf-shell>
</div>
