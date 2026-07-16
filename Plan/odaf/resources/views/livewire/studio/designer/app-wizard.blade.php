<div>
    <x-studio-shell title="App Wizard">
        <div class="max-w-4xl mx-auto py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">App Wizard</h1>
                <p class="text-slate-500 mt-1">Buat aplikasi baru dengan cepat dalam beberapa langkah mudah.</p>
            </div>

            <!-- Stepper -->
            <div class="mb-8">
                <ol class="flex items-center w-full">
                    <li class="flex w-full items-center text-indigo-600 after:content-[''] after:w-full after:h-1 after:border-b after:border-indigo-100 after:border-4 after:inline-block">
                        <span class="flex items-center justify-center w-8 h-8 bg-indigo-100 rounded-full lg:h-10 lg:w-10 shrink-0">
                            <span class="text-sm font-medium {{ $step >= 1 ? 'text-indigo-600' : 'text-slate-500' }}">1</span>
                        </span>
                    </li>
                    <li class="flex w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-slate-100 after:border-4 after:inline-block {{ $step >= 2 ? 'text-indigo-600 after:border-indigo-100' : 'text-slate-500' }}">
                        <span class="flex items-center justify-center w-8 h-8 {{ $step >= 2 ? 'bg-indigo-100' : 'bg-slate-100' }} rounded-full lg:h-10 lg:w-10 shrink-0">
                            <span class="text-sm font-medium {{ $step >= 2 ? 'text-indigo-600' : 'text-slate-500' }}">2</span>
                        </span>
                    </li>
                    <li class="flex w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-slate-100 after:border-4 after:inline-block {{ $step >= 3 ? 'text-indigo-600 after:border-indigo-100' : 'text-slate-500' }}">
                        <span class="flex items-center justify-center w-8 h-8 {{ $step >= 3 ? 'bg-indigo-100' : 'bg-slate-100' }} rounded-full lg:h-10 lg:w-10 shrink-0">
                            <span class="text-sm font-medium {{ $step >= 3 ? 'text-indigo-600' : 'text-slate-500' }}">3</span>
                        </span>
                    </li>
                    <li class="flex items-center {{ $step >= 4 ? 'text-indigo-600' : 'text-slate-500' }}">
                        <span class="flex items-center justify-center w-8 h-8 {{ $step >= 4 ? 'bg-indigo-100' : 'bg-slate-100' }} rounded-full lg:h-10 lg:w-10 shrink-0">
                            <span class="text-sm font-medium {{ $step >= 4 ? 'text-indigo-600' : 'text-slate-500' }}">4</span>
                        </span>
                    </li>
                </ol>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                @if ($step === 1)
                    <!-- Step 1: Info Aplikasi -->
                    <div class="p-8">
                        <h2 class="text-lg font-semibold text-slate-900 mb-6">Langkah 1: Informasi Aplikasi</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Aplikasi <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model.live.debounce.500ms="appName" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: Sales System">
                                @error('appName') <span class="text-sm text-rose-500 mt-1">{{ $message }}</span> @enderror
                                <p class="text-xs text-slate-500 mt-2">Nama yang akan tampil di menu utama pengguna.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Kode Aplikasi <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="appCode" class="w-full rounded-lg border-slate-300 font-mono focus:ring-indigo-500 focus:border-indigo-500" placeholder="APP_SALES">
                                @error('appCode') <span class="text-sm text-rose-500 mt-1">{{ $message }}</span> @enderror
                                <p class="text-xs text-slate-500 mt-2">Kode unik (tanpa spasi). Dibuat otomatis namun dapat diubah.</p>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button wire:click="nextStep" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                <svg wire:loading wire:target="nextStep" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                @elseif ($step === 2)
                    <!-- Step 2: Data Source -->
                    <div class="p-8">
                        <h2 class="text-lg font-semibold text-slate-900 mb-6">Langkah 2: Hubungkan Data</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Sumber Tabel Database</label>
                                <div class="flex items-center space-x-4 mb-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" wire:model.live="isNewTable" value="1" class="text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <span class="ml-2 text-sm text-slate-700">Buat Tabel Baru</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" wire:model.live="isNewTable" value="0" class="text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <span class="ml-2 text-sm text-slate-700">Pilih Tabel yang Sudah Ada</span>
                                    </label>
                                </div>

                                @if($isNewTable)
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Tabel Baru <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.500ms="tableName" class="w-full rounded-lg border-slate-300 font-mono focus:ring-indigo-500 focus:border-indigo-500" placeholder="Misal: CUST_INVOICE">
                                    <p class="text-xs text-slate-500 mt-2">Sistem otomatis membuatkan struktur tabel dasar dengan standard ODAF (ID, Auditing Columns).</p>
                                @else
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Tabel <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="tableName" class="w-full rounded-lg border-slate-300 font-mono focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">-- Pilih Tabel Database --</option>
                                        @foreach($this->availableTables as $t)
                                            <option value="{{ $t['TABLE_NAME'] }}">{{ $t['TABLE_NAME'] }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-slate-500 mt-2">Kolom-kolom di tabel lama ini akan dibaca otomatis (Introspeksi).</p>
                                @endif
                                @error('tableName') <span class="text-sm text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Judul Halaman <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="pageTitle" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Master Data">
                                @error('pageTitle') <span class="text-sm text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Kode Halaman <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="pageCode" class="w-full rounded-lg border-slate-300 font-mono focus:ring-indigo-500 focus:border-indigo-500">
                                @error('pageCode') <span class="text-sm text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button wire:click="prevStep" class="text-slate-600 px-6 py-2 rounded-lg font-medium hover:bg-slate-50 border border-slate-300 transition-colors">Kembali</button>
                            <button wire:click="nextStep" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                <svg wire:loading wire:target="nextStep" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                @elseif ($step === 3)
                    <!-- Step 3: Admin User -->
                    <div class="p-8">
                        <h2 class="text-lg font-semibold text-slate-900 mb-6">Langkah 3: User Admin Aplikasi</h2>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Username Admin <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="adminUsername" class="w-full rounded-lg border-slate-300 font-mono focus:ring-indigo-500 focus:border-indigo-500" placeholder="admin.sales">
                                    @error('adminUsername') <span class="text-sm text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="adminName" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Admin Sales">
                                    @error('adminName') <span class="text-sm text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-slate-400">(Opsional)</span></label>
                                    <input type="email" wire:model="adminEmail" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="admin@domain.com">
                                    @error('adminEmail') <span class="text-sm text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                                    <input type="password" wire:model="adminPassword" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('adminPassword') <span class="text-sm text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <p class="text-xs text-slate-500">Akun ini akan dibuat sebagai super-admin khusus untuk aplikasi baru ini. Role baru (<code>ROLE_ADMIN_{{ $appCode ?: 'XXX' }}</code>) akan otomatis dibuat dan dipasangkan ke user ini.</p>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button wire:click="prevStep" class="text-slate-600 px-6 py-2 rounded-lg font-medium hover:bg-slate-50 border border-slate-300 transition-colors">Kembali</button>
                            <button wire:click="nextStep" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                <svg wire:loading wire:target="nextStep" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                @elseif ($step === 4)
                    <!-- Step 4: Review & Submit -->
                    <div class="p-8">
                        <h2 class="text-lg font-semibold text-slate-900 mb-6">Langkah 4: Konfirmasi</h2>
                        
                        <div class="bg-slate-50 rounded-lg p-6 border border-slate-200 mb-8 space-y-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-slate-500">Nama Aplikasi</div>
                                <div class="col-span-2 text-sm font-medium text-slate-900">{{ $appName }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-slate-500">Kode Aplikasi</div>
                                <div class="col-span-2 text-sm font-mono font-medium text-slate-900">{{ $appCode }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-slate-500">Tabel Sumber</div>
                                <div class="col-span-2 text-sm font-mono font-medium text-slate-900">{{ $tableName }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-slate-500">Judul Halaman</div>
                                <div class="col-span-2 text-sm font-medium text-slate-900">{{ $pageTitle }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 border-t border-slate-200 pt-4 mt-2">
                                <div class="text-sm text-slate-500">User Admin (Baru)</div>
                                <div class="col-span-2 text-sm font-medium text-slate-900">{{ $adminName }} (<code>{{ $adminUsername }}</code>)</div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-blue-50 p-4 border border-blue-200 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Sistem akan melakukan:</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @if($isNewTable)
                                            <li>Sistem otomatis mengeksekusi DDL <strong>CREATE TABLE {{ $tableName }}</strong> dengan standar ODAF.</li>
                                            @endif
                                            <li>Membuat metadata Aplikasi & Modul (Status: <strong>DRAFT</strong>).</li>
                                            <li>Membuat koneksi Dataset ke tabel <strong class="font-mono">{{ $tableName }}</strong>.</li>
                                            <li>Membuat Halaman Form & Grid (<strong class="font-mono">{{ $pageCode }}</strong>).</li>
                                            @if(!$isNewTable)
                                            <li>Otomatis membaca struktur tabel (Introspeksi) dan membuat Fields UI.</li>
                                            @endif
                                            <li>Membuat Role <strong>ROLE_ADMIN_{{ $appCode }}</strong> dan User <strong>{{ $adminUsername }}</strong>.</li>
                                            <li>Melakukan <em>Compile</em> agar aplikasi siap digunakan.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (session()->has('error'))
                            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Gagal Membuat Aplikasi</h3>
                                        <div class="mt-2 text-sm text-red-700 whitespace-pre-wrap">{{ session('error') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-8 flex justify-between">
                            <button wire:click="prevStep" class="text-slate-600 px-6 py-2 rounded-lg font-medium hover:bg-slate-50 border border-slate-300 transition-colors">Kembali</button>
                            <button wire:click="generateApp" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-emerald-700 transition-colors flex items-center gap-2">
                                <svg wire:loading wire:target="generateApp" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Buat Aplikasi Sekarang
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-studio-shell>
</div>
