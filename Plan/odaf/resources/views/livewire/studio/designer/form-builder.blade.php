<div>
    <x-studio-shell :title="$page['OBJECT_NAME'] . ' - Form Builder'">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <a href="/studio/designer/app/{{ $page['APPLICATION_ID'] }}" wire:navigate class="text-slate-400 hover:text-slate-600" title="Kembali ke daftar form">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $page['OBJECT_NAME'] }}</h1>
                    <span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-700 rounded">{{ $page['PAGE_TYPE'] }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $page['APPLICATION_NAME'] }} • {{ $page['DATASET_CODE'] ?? 'No dataset' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Preview Mode Toggle --}}
                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1">
                    <button wire:click="setPreviewMode('desktop')" 
                            @class([
                                'px-3 py-1 rounded text-sm transition-colors',
                                'bg-indigo-600 text-white' => $previewMode === 'desktop',
                                'text-slate-600 hover:bg-slate-100' => $previewMode !== 'desktop',
                            ])>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                    <button wire:click="setPreviewMode('tablet')"
                            @class([
                                'px-3 py-1 rounded text-sm transition-colors',
                                'bg-indigo-600 text-white' => $previewMode === 'tablet',
                                'text-slate-600 hover:bg-slate-100' => $previewMode !== 'tablet',
                            ])>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                    <button wire:click="setPreviewMode('mobile')"
                            @class([
                                'px-3 py-1 rounded text-sm transition-colors',
                                'bg-indigo-600 text-white' => $previewMode === 'mobile',
                                'text-slate-600 hover:bg-slate-100' => $previewMode !== 'mobile',
                            ])>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>

                <button wire:click="compile" 
                        wire:confirm="Kompilasi & aktifkan aplikasi ini sekarang?"
                        wire:loading.attr="disabled"
                        wire:target="compile"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white font-medium hover:bg-emerald-700 disabled:opacity-60">
                    <svg wire:loading.remove wire:target="compile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg wire:loading wire:target="compile" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="compile">Compile &amp; Activate</span>
                    <span wire:loading wire:target="compile">Compiling...</span>
                </button>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Main Layout: 3 columns --}}
        <div class="flex gap-6 h-[calc(100vh-250px)]">
            {{-- Left: Field Palette --}}
            <div class="w-64 shrink-0 bg-white rounded-lg border border-slate-200 overflow-y-auto">
                <div class="p-4 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-900">Field Types</h2>
                    <p class="text-xs text-slate-500 mt-1">Click to add field</p>
                </div>

                <div class="p-3 space-y-2">
                    @foreach ($fieldWidgets as $widget)
                        <button wire:click="openAddModal('{{ $widget['type'] }}')"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition-colors text-left group">
                            <div class="p-2 rounded bg-slate-100 group-hover:bg-indigo-100">
                                <svg class="w-5 h-5 text-slate-600 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $widget['icon'] }}"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-slate-900">{{ $widget['label'] }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Center: Canvas (Form Preview) --}}
            <div class="flex-1 bg-slate-100 rounded-lg overflow-hidden">
                <div class="h-full overflow-y-auto flex items-start justify-center p-6">
                    <div @class([
                        'bg-white rounded-lg shadow-lg transition-all duration-300',
                        'w-full max-w-4xl' => $previewMode === 'desktop',
                        'w-full max-w-2xl' => $previewMode === 'tablet',
                        'w-full max-w-sm' => $previewMode === 'mobile',
                    ])>
                        {{-- Form Header --}}
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $page['OBJECT_NAME'] }}</h3>
                            @if ($page['DESCRIPTION'])
                                <p class="text-sm text-slate-500 mt-1">{{ $page['DESCRIPTION'] }}</p>
                            @endif
                        </div>

                        {{-- Fields (Sortable) --}}
                        <div class="p-6" id="sortable-fields">
                            @if (count($fields) === 0)
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <p class="mt-4 text-slate-500">No fields yet</p>
                                    <p class="text-sm text-slate-400">Click field types on the left to add</p>
                                </div>
                            @else
                                <div class="space-y-4" x-data="formSorter()" x-ref="sortableContainer" wire:key="sortable-{{ $pageId }}">
                                    @foreach ($fields as $field)
                                        <div data-field-id="{{ $field['ID'] }}"
                                             wire:key="field-{{ $field['ID'] }}"
                                             wire:click="selectField('{{ $field['ID'] }}')"
                                             @class([
                                                 'group relative p-4 rounded-lg border-2 transition-all cursor-pointer',
                                                 'border-indigo-500 bg-indigo-50' => $selectedFieldId === $field['ID'],
                                                 'border-slate-200 bg-white hover:border-slate-300' => $selectedFieldId !== $field['ID'],
                                             ])>
                                            {{-- Drag Handle --}}
                                            <div class="drag-handle absolute left-2 top-4 cursor-move opacity-40 group-hover:opacity-100 transition-opacity"
                                                 wire:click.stop
                                                 title="Drag untuk mengurutkan">
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                </svg>
                                            </div>

                                            {{-- Field Content --}}
                                            <div class="pl-6">
                                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                                    {{ $field['LABEL'] }}
                                                    @if ($field['REQUIRED_FLAG'])
                                                        <span class="text-rose-500">*</span>
                                                    @endif
                                                </label>

                                                {{-- Widget Preview --}}
                                                @if ($field['FIELD_TYPE'] === 'CHECKBOX')
                                                    <div class="flex items-center gap-2">
                                                        <input type="checkbox" disabled class="rounded border-slate-300">
                                                        <span class="text-sm text-slate-500">{{ $field['LABEL'] }}</span>
                                                    </div>
                                                @elseif ($field['FIELD_TYPE'] === 'TEXTAREA')
                                                    <textarea disabled class="w-full rounded-lg border-slate-300 text-slate-400" rows="3" placeholder="Enter text..."></textarea>
                                                @elseif ($field['LOV_ID'])
                                                    <select disabled class="w-full rounded-lg border-slate-300 text-slate-400">
                                                        <option>-- Select {{ $field['LABEL'] }} --</option>
                                                    </select>
                                                @elseif (in_array($field['FIELD_TYPE'], ['DATE', 'DATETIME']))
                                                    <input type="date" disabled class="w-full rounded-lg border-slate-300 text-slate-400">
                                                @elseif ($field['FIELD_TYPE'] === 'NUMBER')
                                                    <input type="number" disabled class="w-full rounded-lg border-slate-300 text-slate-400" placeholder="0">
                                                @else
                                                    <input type="text" disabled class="w-full rounded-lg border-slate-300 text-slate-400" placeholder="Enter {{ strtolower($field['LABEL']) }}...">
                                                @endif

                                                {{-- Field Meta --}}
                                                <div class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                                                    <span class="font-mono">{{ $field['COLUMN_NAME'] }}</span>
                                                    <span>•</span>
                                                    <span>{{ $field['FIELD_TYPE'] }}</span>
                                                    @if ($field['LOV_CODE'])
                                                        <span>•</span>
                                                        <span class="text-indigo-600">LOV: {{ $field['LOV_CODE'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        {{-- Tabs Details --}}
                        <div class="px-6 py-4 border-t border-slate-200 bg-white">
                            <div class="border-b border-slate-200 flex justify-between items-center">
                                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                                    @if (!empty($page['DETAIL_CONFIG_ARRAY']))
                                        @foreach ($page['DETAIL_CONFIG_ARRAY'] as $index => $detail)
                                            @if(!empty($detail['pageId']))
                                                <a href="/studio/designer/form/{{ $detail['pageId'] }}" wire:navigate class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $index === 0 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                                                    {{ $detail['title'] ?? $detail['pageCode'] }}
                                                </a>
                                            @else
                                                <div class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $index === 0 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500' }}">
                                                    {{ $detail['title'] ?? $detail['pageCode'] }}
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="whitespace-nowrap pb-3 px-1 border-b-2 border-transparent font-medium text-sm text-slate-400">
                                            No detail tabs
                                        </div>
                                    @endif
                                </nav>
                                <button type="button" wire:click="openAddTabModal" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Tab
                                </button>
                            </div>
                            @if (!empty($page['DETAIL_CONFIG_ARRAY']))
                                <div class="py-8 text-center border border-dashed border-slate-200 rounded-b-lg mt-4 bg-slate-50">
                                    <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-slate-500">Detail grid content ({{ $page['DETAIL_CONFIG_ARRAY'][0]['title'] ?? 'Tab 1' }}) will be rendered here.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Form Footer --}}
                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                            <button type="button" disabled class="px-4 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-300 rounded-lg">
                                Cancel
                            </button>
                            <button type="button" disabled class="px-4 py-2 text-sm font-medium text-white bg-indigo-400 rounded-lg">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Property Editor --}}
            <div class="w-80 shrink-0 bg-white rounded-lg border border-slate-200 overflow-y-auto">
                @if ($selectedFieldId)
                    <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">Field Properties</h2>
                        <button wire:click="deleteField('{{ $selectedFieldId }}')"
                                wire:confirm="Delete this field?"
                                class="p-1 text-rose-600 hover:bg-rose-50 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="updateField" class="p-4 space-y-4">
                        {{-- Label --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
                            <input type="text" wire:model="fieldEditor.label" class="w-full rounded-lg border-slate-300">
                        </div>

                        {{-- Field Type --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                            <select wire:model.live="fieldEditor.field_type" class="w-full rounded-lg border-slate-300">
                                @foreach ($fieldTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- LOV --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">List of Values</label>
                            <select wire:model="fieldEditor.lov_id" class="w-full rounded-lg border-slate-300">
                                <option value="">-- None --</option>
                                @foreach ($availableLovs as $lov)
                                    <option value="{{ $lov['ID'] }}">{{ $lov['OBJECT_NAME'] }} ({{ $lov['LOV_TYPE'] }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Flags --}}
                        <div class="space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="fieldEditor.required_flag" value="1" class="rounded border-slate-300">
                                <span class="text-sm text-slate-700">Required</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="fieldEditor.readonly_flag" value="1" class="rounded border-slate-300">
                                <span class="text-sm text-slate-700">Read-only</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="fieldEditor.visible_flag" value="1" class="rounded border-slate-300">
                                <span class="text-sm text-slate-700">Visible</span>
                            </label>
                        </div>

                        {{-- Default Value --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Default Value</label>
                            <input type="text" wire:model="fieldEditor.default_value" class="w-full rounded-lg border-slate-300" placeholder="Optional">
                        </div>

                        {{-- Opsi format DATE/DATETIME --}}
                        @if (in_array($fieldEditor['field_type'], ['DATE', 'DATETIME']))
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opsi Tanggal</div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nilai Default</label>
                                    <select wire:model="fieldEditor.date_default" class="w-full rounded-lg border-slate-300">
                                        <option value="">Kosong</option>
                                        <option value="SYSDATE">Tanggal hari ini (SYSDATE)</option>
                                    </select>
                                </div>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="fieldEditor.date_with_time" value="1" class="rounded border-slate-300">
                                    <span class="text-sm text-slate-700">Sertakan jam (tanggal &amp; waktu)</span>
                                </label>
                            </div>
                        @endif

                        {{-- Opsi format NUMBER --}}
                        @if (in_array($fieldEditor['field_type'], ['NUMBER', 'INTEGER', 'DECIMAL']))
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opsi Angka</div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Desimal</label>
                                    <input type="number" min="0" max="10" wire:model="fieldEditor.num_decimals"
                                           class="w-full rounded-lg border-slate-300" placeholder="mis. 0 atau 2">
                                    <p class="mt-1 text-xs text-slate-400">Kosongkan untuk tampil apa adanya.</p>
                                </div>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="fieldEditor.num_thousands" value="1" class="rounded border-slate-300">
                                    <span class="text-sm text-slate-700">Pemisah ribuan (1.000.000)</span>
                                </label>
                            </div>
                        @endif

                        {{-- Submit --}}
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                            Update Field
                        </button>
                    </form>
                @else
                    <div class="p-8 text-center text-slate-400">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        <p class="mt-4 text-sm">Select a field to edit</p>
                    </div>
                @endif
            </div>
        </div>
    </x-studio-shell>

    {{-- Add Field Modal --}}
    @if ($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Tambah Field</h3>
                    <button wire:click="closeAddModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Label --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Label / Judul Field <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="newField.label"
                               class="w-full rounded-lg border-slate-300"
                               placeholder="mis. BUMN" autofocus>
                        @error('newField.label')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Column name preview --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kolom (otomatis)</label>
                        <input type="text" value="{{ $this->columnNamePreview }}" readonly
                               class="w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-slate-600">
                        <p class="mt-1 text-xs text-slate-400">Spasi &amp; karakter khusus diganti "_", huruf besar, maks 30 karakter.</p>
                    </div>

                    {{-- Field type --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Field</label>
                        <select wire:model.live="newField.field_type" class="w-full rounded-lg border-slate-300">
                            @foreach ($fieldTypes as $type => $typeLabel)
                                <option value="{{ $type }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Length (text-like only) --}}
                    @if (in_array($newField['field_type'], ['TEXT', 'EMAIL', 'PASSWORD']))
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Panjang Maksimum (VARCHAR2)</label>
                            <input type="number" wire:model="newField.length" min="1" max="4000"
                                   class="w-full rounded-lg border-slate-300">
                        </div>
                    @endif

                    {{-- Target table info --}}
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Tabel target:</span>
                            <span class="font-mono text-slate-900">{{ $page['TABLE_NAME'] ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span>Tipe dataset:</span>
                            <span class="font-medium">{{ $page['SOURCE_TYPE'] ?? '—' }}</span>
                        </div>
                    </div>

                    @if (strtoupper($page['SOURCE_TYPE'] ?? '') === 'TABLE')
                        <label class="flex items-start gap-2">
                            <input type="checkbox" wire:model="newField.add_column" class="mt-0.5 rounded border-slate-300">
                            <span class="text-sm text-slate-700">
                                Tambahkan kolom ke tabel jika belum ada
                                <span class="block text-xs text-slate-400">Menjalankan ALTER TABLE agar field bisa menyimpan data.</span>
                            </span>
                        </label>
                    @else
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                            Dataset ini bertipe {{ $page['SOURCE_TYPE'] ?? '?' }} (bukan TABLE), sehingga kolom fisik tidak dapat ditambah otomatis. Field tetap dibuat sebagai metadata.
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <button wire:click="closeAddModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="addField"
                            wire:loading.attr="disabled" wire:target="addField"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="addField">Tambah Field</span>
                        <span wire:loading wire:target="addField">Menambahkan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Tambah Tab --}}
    @if ($showAddTabModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity" wire:transition.opacity>
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden" @click.outside="$wire.closeAddTabModal()">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-semibold text-slate-900">Tambah Tab Detail</h3>
                    <button wire:click="closeAddTabModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul Tab</label>
                        <input type="text" wire:model="newTab.title" placeholder="Cth: Data Pembayaran" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('newTab.title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Suffix Nama Tabel</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">
                                {{ $page['TABLE_NAME'] ?? 'T_HEADER' }}_
                            </span>
                            <input type="text" wire:model="newTab.suffix" placeholder="Cth: ADDRESS" class="block w-full min-w-0 flex-1 rounded-none rounded-r-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase font-mono">
                        </div>
                        @error('newTab.suffix') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-slate-500 mt-1">Tabel detail fisik (beserta form-nya) akan otomatis dibuat dan ditautkan ke header ini.</p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2 bg-slate-50">
                    <button wire:click="closeAddTabModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                        Batal
                    </button>
                    <button wire:click="addTab"
                            wire:loading.attr="disabled" wire:target="addTab"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="addTab">Tambah Tab</span>
                        <span wire:loading wire:target="addTab">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Alpine.js Component --}}
    @assets
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    @endassets

    @script
    <script>
        Alpine.data('formSorter', () => ({
            sortable: null,

            init() {
                this.setup();

                // Re-setup setelah Livewire me-render ulang daftar field.
                this.$wire.on('fields-changed', () => {
                    this.$nextTick(() => this.setup());
                });
            },

            setup() {
                const container = this.$refs.sortableContainer;
                if (!container || typeof Sortable === 'undefined') {
                    return;
                }

                if (this.sortable) {
                    this.sortable.destroy();
                    this.sortable = null;
                }

                this.sortable = Sortable.create(container, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'opacity-50',
                    onEnd: () => {
                        const order = Array.from(container.querySelectorAll('[data-field-id]'))
                            .map(el => el.getAttribute('data-field-id'));
                        this.$wire.reorderFields(order);
                    },
                });
            },
        }));
    </script>
    @endscript
</div>
