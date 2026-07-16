<div>
    <x-studio-shell title="Access Control">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Kontrol Akses</h1>
            <p class="mt-1 text-sm text-slate-500">Atur tingkat akses per Role terhadap Halaman &amp; Field. Perubahan langsung berlaku di runtime (tanpa compile).</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('ac_status'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">
                ✅ {{ session('ac_status') }}
            </div>
        @endif
        @if (session('ac_error'))
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">
                ❌ {{ session('ac_error') }}
            </div>
        @endif

        {{-- Legenda --}}
        <div class="mb-6 p-4 bg-slate-100 rounded-lg border border-slate-200">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Legenda Tingkat Akses</div>
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="font-medium text-slate-700">FULL</span>
                    <span class="text-slate-500">— Baca + Tulis</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-teal-500"></span>
                    <span class="font-medium text-slate-700">APPEND</span>
                    <span class="text-slate-500">— Tambah Baru Saja</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="font-medium text-slate-700">READONLY</span>
                    <span class="text-slate-500">— Hanya Baca</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span class="font-medium text-slate-700">MASKED</span>
                    <span class="text-slate-500">— Isi disamarkan (••••)</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span class="font-medium text-slate-700">NONE</span>
                    <span class="text-slate-500">— Disembunyikan</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-slate-300"></span>
                    <span class="font-medium text-slate-700">DEFAULT</span>
                    <span class="text-slate-500">— Tanpa aturan (terbuka)</span>
                </span>
            </div>
        </div>

        {{-- App & Role Selectors --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="acApp" class="block text-sm font-medium text-slate-700 mb-1">Aplikasi</label>
                <select id="acApp" wire:model.live="appId"
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">— Pilih Aplikasi —</option>
                    @foreach ($applications as $app)
                        <option value="{{ $app['ID'] }}">{{ $app['OBJECT_NAME'] }} ({{ $app['OBJECT_CODE'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="acRole" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select id="acRole" wire:model.live="roleId"
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">— Pilih Role —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['ID'] }}">
                            {{ $role['OBJECT_NAME'] }}
                            ({{ $role['OBJECT_CODE'] }})
                            @if (!empty($role['PARENT_ROLE_ID']))
                                — mewarisi {{ $roleMap[strtoupper($role['PARENT_ROLE_ID'])]['OBJECT_NAME'] ?? '?' }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @if ($appId === '')
                    <p class="text-xs text-slate-400 mt-1">Pilih aplikasi terlebih dahulu untuk memfilter role.</p>
                @endif
            </div>
        </div>

        {{-- Role Hierarchy --}}
        @if ($roleId !== '')
            <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <div class="text-sm font-semibold text-indigo-800 mb-2">🔗 Hierarki Role</div>
                <p class="text-xs text-indigo-600 mb-3">Role ini mewarisi semua akses dari parent-nya. Jika role A punya parent B, maka user dengan role A juga mendapat akses role B.</p>

                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-indigo-700 mb-1">Parent Role (mewarisi dari)</label>
                        <select wire:model="parentRoleId"
                                class="w-full rounded-md border-indigo-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">— Tanpa Parent (Root) —</option>
                            @foreach ($roles as $role)
                                @if (strtoupper($role['ID']) !== strtoupper($roleId))
                                    <option value="{{ $role['ID'] }}">{{ $role['OBJECT_NAME'] }} ({{ $role['OBJECT_CODE'] }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <button wire:click="saveHierarchy"
                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Hierarki
                    </button>
                </div>

                @if ($parentInfo)
                    <div class="mt-2 text-xs text-indigo-600">
                        ↳ Saat ini mewarisi dari: <span class="font-semibold">{{ $parentInfo['OBJECT_NAME'] ?? '—' }}</span>
                        ({{ $parentInfo['OBJECT_CODE'] ?? '' }})
                    </div>
                @endif
            </div>
        @endif

        {{-- Access Rules Table --}}
        @if ($roleId !== '' && $appId !== '')
            @if (count($tree) === 0)
                <div class="text-center py-12 text-slate-400">
                    <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p>Aplikasi ini belum memiliki halaman.</p>
                </div>
            @else
                {{-- Save Button (sticky) --}}
                <div class="sticky top-0 z-10 bg-white/90 backdrop-blur-sm border-b border-slate-200 -mx-6 px-6 py-3 mb-4 flex items-center justify-between">
                    <div class="text-sm text-slate-500">
                        {{ count($tree) }} halaman • Mengatur akses untuk role terpilih
                    </div>
                    <button wire:click="save"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Simpan Aturan Akses
                    </button>
                </div>

                {{-- Pages & Fields --}}
                <div class="space-y-3">
                    @foreach ($tree as $page)
                        @php
                            $pageKey = 'PAGE:' . strtoupper($page['ID']);
                            $pageLevel = $levels[$pageKey] ?? '';
                        @endphp
                        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                            {{-- Page Header --}}
                            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 truncate">{{ $page['OBJECT_NAME'] }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ $page['OBJECT_CODE'] }} · {{ $page['PAGE_TYPE'] }}</div>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <select wire:model.lazy="levels.{{ $pageKey }}"
                                                class="rounded-md text-sm font-medium border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500
                                                    {{ match($pageLevel) {
                                                        'FULL' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                                                        'APPEND' => 'bg-teal-50 text-teal-700 border-teal-300',
                                                        'READONLY' => 'bg-blue-50 text-blue-700 border-blue-300',
                                                        'MASKED' => 'bg-amber-50 text-amber-700 border-amber-300',
                                                        'NONE' => 'bg-rose-50 text-rose-700 border-rose-300',
                                                        default => 'bg-white text-slate-600',
                                                    } }}">
                                            <option value="">DEFAULT</option>
                                            <option value="FULL">🟢 FULL</option>
                                            <option value="APPEND">🟩 APPEND</option>
                                            <option value="READONLY">🔵 READONLY</option>
                                            <option value="MASKED">🟡 MASKED</option>
                                            <option value="NONE">🔴 NONE</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Masa Berlaku per Page --}}
                                <div class="mt-2 flex items-center gap-2 text-xs">
                                    <span class="text-slate-500 shrink-0">Berlaku:</span>
                                    <input type="date"
                                           wire:model.lazy="validFroms.{{ $pageKey }}"
                                           class="rounded border-slate-300 text-xs px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Mulai">
                                    <span class="text-slate-400">s/d</span>
                                    <input type="date"
                                           wire:model.lazy="validTos.{{ $pageKey }}"
                                           class="rounded border-slate-300 text-xs px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Berakhir">
                                    <span class="text-slate-400 ml-1">(kosong = tanpa batas)</span>
                                </div>
                            </div>

                            {{-- Fields --}}
                            @if (count($page['FIELDS']) > 0)
                                <div class="divide-y divide-slate-100">
                                    @foreach ($page['FIELDS'] as $field)
                                        @php
                                            $fieldKey = 'FIELD:' . strtoupper($field['ID']);
                                            $fieldLevel = $levels[$fieldKey] ?? '';
                                        @endphp
                                        <div class="px-4 py-2 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-slate-300 pl-4">├─</span>
                                                <div class="min-w-0">
                                                    <span class="text-sm text-slate-700">{{ $field['LABEL'] ?? $field['COLUMN_NAME'] }}</span>
                                                    <span class="text-xs text-slate-400 font-mono ml-1">({{ $field['COLUMN_NAME'] }})</span>
                                                </div>
                                                @if ($field['FIELD_TYPE'] ?? false)
                                                    <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ $field['FIELD_TYPE'] }}</span>
                                                @endif
                                            </div>
                                            <select wire:model.lazy="levels.{{ $fieldKey }}"
                                                    class="rounded text-xs font-medium border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500
                                                        {{ match($fieldLevel) {
                                                            'FULL' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                                                            'APPEND' => 'bg-teal-50 text-teal-700 border-teal-300',
                                                            'READONLY' => 'bg-blue-50 text-blue-700 border-blue-300',
                                                            'MASKED' => 'bg-amber-50 text-amber-700 border-amber-300',
                                                            'NONE' => 'bg-rose-50 text-rose-700 border-rose-300',
                                                            default => 'bg-white text-slate-500',
                                                        } }}">
                                                <option value="">DEFAULT</option>
                                                <option value="FULL">🟢 FULL</option>
                                                <option value="APPEND">🟩 APPEND</option>
                                                <option value="READONLY">🔵 READONLY</option>
                                                <option value="MASKED">🟡 MASKED</option>
                                                <option value="NONE">🔴 NONE</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-4 py-3 text-xs text-slate-400 italic">Halaman ini belum memiliki field.</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Bottom Save Button --}}
                <div class="mt-6 flex justify-end">
                    <button wire:click="save"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Aturan Akses
                    </button>
                </div>
            @endif
        @elseif ($roleId === '' || $appId === '')
            <div class="text-center py-16 text-slate-400">
                <svg class="mx-auto h-16 w-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <h3 class="text-lg font-medium text-slate-600">Pilih Role dan Aplikasi</h3>
                <p class="mt-2 text-sm text-slate-400">Pilih role dan aplikasi di atas untuk melihat dan mengatur aturan akses.</p>
            </div>
        @endif
    </x-studio-shell>
</div>
