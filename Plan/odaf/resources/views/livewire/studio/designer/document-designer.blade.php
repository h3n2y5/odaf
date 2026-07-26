<div x-data="{ previewHtml: '' }" x-init="
    $watch('$wire.htmlContent', val => {
        let css = $wire.cssContent || '';
        previewHtml = '<style>' + css + '</style>' + val;
    });
    previewHtml = '<style>' + ($wire.cssContent || '') + '</style>' + ($wire.htmlContent || '');
">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Document Designer &mdash; {{ $pageName }}
            </h2>
            <p class="text-sm text-slate-500">Rancang cetakan dokumen secara visual. Klik section untuk menambah blok.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="saveTemplate" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Simpan
            </button>
        </div>
    </div>

    @if (session('doc_success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">
            {{ session('doc_success') }}
        </div>
    @endif

    <div class="flex gap-4 h-[calc(100vh-200px)]">

        {{-- ═══ PANEL 1: Palette (Kiri) ═══ --}}
        <div class="w-64 shrink-0 bg-white rounded-lg border border-slate-200 overflow-y-auto">
            {{-- Template List --}}
            <div class="p-3 border-b border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Template Tersimpan</h3>
                <div class="space-y-1">
                    @foreach($templates as $t)
                        <button wire:click="editTemplate('{{ $t['OBJECT_ID'] }}')"
                                class="w-full text-left px-2 py-1.5 rounded text-sm transition-colors {{ $templateId === $t['OBJECT_ID'] ? 'bg-indigo-50 text-indigo-700 font-medium border border-indigo-200' : 'text-slate-600 hover:bg-slate-50' }}">
                            {{ $t['OBJECT_NAME'] }}
                            @if($t['IS_DEFAULT']) <span class="text-[9px] bg-emerald-100 text-emerald-700 px-1 rounded">Default</span> @endif
                        </button>
                    @endforeach
                    @if(empty($templates))
                        <div class="text-xs text-slate-400 italic px-2 py-1">Belum ada template.</div>
                    @endif
                </div>
                <button wire:click="newTemplate" class="mt-2 w-full text-xs text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1 py-1.5 border border-dashed border-indigo-300 rounded hover:bg-indigo-50 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Template Baru
                </button>
            </div>

            {{-- Preset Templates --}}
            <div class="p-3 border-b border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Mulai dari Preset</h3>
                <div class="space-y-1">
                    <button wire:click="applyPreset('purchase_order')" class="w-full flex items-center gap-2 px-2 py-2 rounded text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border border-slate-200">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Purchase Order
                    </button>
                    <button wire:click="applyPreset('invoice')" class="w-full flex items-center gap-2 px-2 py-2 rounded text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors border border-slate-200">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Invoice / Faktur
                    </button>
                </div>
            </div>

            {{-- Add Sections --}}
            <div class="p-3 border-b border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tambah Section</h3>
                <div class="space-y-1">
                    @foreach($sectionTypes as $type => $meta)
                        @php
                            $count = count(array_filter($sections, fn($s) => $s['type'] === $type));
                            $disabled = $count >= $meta['max'];
                            $iconMap = [
                                'columns' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7',
                                'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                                'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                'grid' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                                'table' => 'M3 10h18M3 14h18M3 18h18M3 6h18',
                                'calculator' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                                'pen' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
                                'note' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                                'qr' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
                                'line' => 'M5 12h14',
                            ];
                        @endphp
                        <button wire:click="addSection('{{ $type }}')"
                                @if($disabled) disabled @endif
                                class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-sm transition-colors {{ $disabled ? 'text-slate-300 cursor-not-allowed' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-4 h-4 {{ $disabled ? 'text-slate-300' : 'text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconMap[$meta['icon']] }}"></path></svg>
                            {{ $meta['label'] }}
                            @if($disabled) <span class="text-[9px] text-slate-300">(max)</span> @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Template Info --}}
            <div class="p-3">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Properti</h3>
                <div class="space-y-2">
                    <div>
                        <label class="text-xs text-slate-600">Kode</label>
                        <input type="text" wire:model="objectCode" class="w-full text-xs rounded border-slate-300 px-2 py-1">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600">Nama</label>
                        <input type="text" wire:model="objectName" class="w-full text-xs rounded border-slate-300 px-2 py-1">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-slate-600">Kertas</label>
                            <select wire:model="pageSize" class="w-full text-xs rounded border-slate-300 px-1 py-1">
                                <option value="A4">A4</option>
                                <option value="LETTER">Letter</option>
                                <option value="POS_RECEIPT">Struk</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-slate-600">Orientasi</label>
                            <select wire:model="orientation" class="w-full text-xs rounded border-slate-300 px-1 py-1">
                                <option value="PORTRAIT">Portrait</option>
                                <option value="LANDSCAPE">Landscape</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-1.5 text-xs text-slate-600">
                        <input type="checkbox" wire:model="isDefault" class="rounded border-slate-300 text-indigo-600">
                        Default Print
                    </label>
                </div>
            </div>
        </div>

        {{-- ═══ PANEL 2: Section Editor (Tengah) ═══ --}}
        <div class="flex-1 bg-white rounded-lg border border-slate-200 overflow-y-auto">
            {{-- Tab switcher --}}
            <div class="flex border-b border-slate-200 px-4">
                <button wire:click="$set('activeTab', 'visual')" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'visual' ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"></path></svg>
                    Visual Builder
                </button>
                <button wire:click="$set('activeTab', 'html')" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'html' ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    HTML (Advanced)
                </button>
            </div>

            <div class="p-4">
                @if($activeTab === 'visual')
                    {{-- Visual Section Editor --}}
                    @if(empty($sections))
                        <div class="text-center py-16 text-slate-400">
                            <svg class="mx-auto w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-lg font-medium text-slate-500">Mulai rancang dokumen Anda</p>
                            <p class="text-sm mt-1">Klik <b>Tambah Section</b> di panel kiri, atau pilih <b>Preset</b> untuk memulai dengan cepat.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($sections as $idx => $section)
                                <div class="border border-slate-200 rounded-lg overflow-hidden hover:border-indigo-300 transition-colors group">
                                    {{-- Section Header --}}
                                    <div class="bg-slate-50 px-3 py-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-slate-500 uppercase">{{ $sectionTypes[$section['type']]['label'] ?? $section['type'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click="moveSectionUp({{ $idx }})" class="p-1 text-slate-400 hover:text-slate-600 rounded" title="Pindah ke atas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                            </button>
                                            <button wire:click="moveSectionDown({{ $idx }})" class="p-1 text-slate-400 hover:text-slate-600 rounded" title="Pindah ke bawah">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>
                                            <button wire:click="removeSection({{ $idx }})" wire:confirm="Hapus section ini?" class="p-1 text-rose-400 hover:text-rose-600 rounded" title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Section Config --}}
                                    <div class="p-3 text-sm">
                                        @if($section['type'] === 'letterhead')
                                            <div class="grid grid-cols-1 gap-2">
                                                <div>
                                                    <label class="text-xs text-slate-500">Nama Perusahaan</label>
                                                    <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.companyName" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1">
                                                </div>
                                                <div>
                                                    <label class="text-xs text-slate-500">Alamat</label>
                                                    <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.address" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1">
                                                </div>
                                                <div>
                                                    <label class="text-xs text-slate-500">Telepon</label>
                                                    <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.phone" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1">
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'two_column')
                                            <div class="space-y-3">
                                                <div class="grid grid-cols-2 gap-4">
                                                    {{-- Kolom Kiri --}}
                                                    <div class="border border-slate-200 rounded p-2">
                                                        <div class="mb-1">
                                                            <label class="text-xs text-slate-500">Judul Kiri (opsional)</label>
                                                            <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.leftTitle" wire:change="regenerateHtml" class="w-full text-xs rounded border-slate-300 px-2 py-1">
                                                        </div>
                                                        <label class="text-xs text-slate-500">Field Kolom Kiri:</label>
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($section['config']['leftFields'] ?? [] as $fi => $field)
                                                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-mono">
                                                                    {{ $field }}
                                                                    <button wire:click="removeFieldFromTwoColumn({{ $idx }}, 'left', {{ $fi }})" class="text-blue-400 hover:text-rose-500">&times;</button>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                        <div class="flex flex-wrap gap-1 mt-2">
                                                            @foreach($availableFields as $af)
                                                                @if(!in_array($af, $section['config']['leftFields'] ?? []))
                                                                    <button wire:click="addFieldToTwoColumn({{ $idx }}, 'left', '{{ $af }}')" class="text-[10px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded hover:bg-blue-100 hover:text-blue-700 font-mono">+ {{ $af }}</button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    {{-- Kolom Kanan --}}
                                                    <div class="border border-slate-200 rounded p-2">
                                                        <div class="mb-1">
                                                            <label class="text-xs text-slate-500">Judul Kanan (opsional)</label>
                                                            <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.rightTitle" wire:change="regenerateHtml" class="w-full text-xs rounded border-slate-300 px-2 py-1">
                                                        </div>
                                                        <label class="text-xs text-slate-500">Field Kolom Kanan:</label>
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($section['config']['rightFields'] ?? [] as $fi => $field)
                                                                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded text-[10px] font-mono">
                                                                    {{ $field }}
                                                                    <button wire:click="removeFieldFromTwoColumn({{ $idx }}, 'right', {{ $fi }})" class="text-emerald-400 hover:text-rose-500">&times;</button>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                        <div class="flex flex-wrap gap-1 mt-2">
                                                            @foreach($availableFields as $af)
                                                                @if(!in_array($af, $section['config']['rightFields'] ?? []))
                                                                    <button wire:click="addFieldToTwoColumn({{ $idx }}, 'right', '{{ $af }}')" class="text-[10px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded hover:bg-emerald-100 hover:text-emerald-700 font-mono">+ {{ $af }}</button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'doc_info')
                                            <div class="space-y-2">
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="text-xs text-slate-500">Judul Dokumen</label>
                                                        <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.title" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1 font-semibold">
                                                    </div>
                                                    <div>
                                                        <label class="text-xs text-slate-500">Jumlah Kolom Layout</label>
                                                        <select wire:model.live="sections.{{ $idx }}.config.gridColumns" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1">
                                                            <option value="1">1 Kolom</option>
                                                            <option value="2">2 Kolom</option>
                                                            <option value="3">3 Kolom</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="text-xs text-slate-500">Field yang ditampilkan:</label>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        @foreach($section['config']['fields'] ?? [] as $fi => $field)
                                                            <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs font-mono">
                                                                {{ $field }}
                                                                <button wire:click="removeFieldFromDocInfo({{ $idx }}, {{ $fi }})" class="text-indigo-400 hover:text-rose-500">&times;</button>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                    <div class="mt-2">
                                                        <label class="text-xs text-slate-400">Klik untuk menambah:</label>
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($availableFields as $af)
                                                                @if(!in_array($af, $section['config']['fields'] ?? []))
                                                                    <button wire:click="addFieldToDocInfo({{ $idx }}, '{{ $af }}')" class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded hover:bg-indigo-100 hover:text-indigo-700 font-mono transition-colors">
                                                                        + {{ $af }}
                                                                    </button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'field_table')
                                            <div class="space-y-2">
                                                <div class="text-xs text-slate-500">Buat tabel dari field-field header. Pilih kolom yang ingin ditampilkan dalam format tabel:</div>
                                                <div class="grid grid-cols-3 gap-1">
                                                    @foreach($availableFields as $col)
                                                        <label class="flex items-center gap-1.5 text-xs px-2 py-1 rounded {{ in_array($col, $section['config']['columns'] ?? []) ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-50 text-slate-600' }} cursor-pointer hover:bg-indigo-50 transition-colors">
                                                            <input type="checkbox" wire:click="toggleFieldTableColumn({{ $idx }}, '{{ $col }}')"
                                                                   {{ in_array($col, $section['config']['columns'] ?? []) ? 'checked' : '' }}
                                                                   class="rounded border-slate-300 text-indigo-600">
                                                            <span class="font-mono truncate">{{ $col }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'table')
                                            @if(!empty($availableDetails))
                                                @php $det = $availableDetails[$section['config']['detailIndex'] ?? 0] ?? null; @endphp
                                                @if($det)
                                                    <div class="space-y-2">
                                                        <div class="text-xs text-slate-500">Dataset: <span class="font-semibold text-slate-700">{{ $det['name'] }}</span></div>
                                                        <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                                            <input type="checkbox" wire:model.live="sections.{{ $idx }}.config.showNumber" wire:change="regenerateHtml" class="rounded border-slate-300 text-indigo-600">
                                                            Tampilkan kolom nomor urut
                                                        </label>
                                                        <div>
                                                            <label class="text-xs text-slate-500">Pilih kolom yang ditampilkan:</label>
                                                            <div class="grid grid-cols-3 gap-1 mt-1">
                                                                @foreach($det['fields'] as $col)
                                                                    <label class="flex items-center gap-1.5 text-xs px-2 py-1 rounded {{ in_array($col, $section['config']['columns'] ?? []) ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-50 text-slate-600' }} cursor-pointer hover:bg-indigo-50 transition-colors">
                                                                        <input type="checkbox" wire:click="toggleDetailColumn({{ $idx }}, '{{ $col }}')"
                                                                               {{ in_array($col, $section['config']['columns'] ?? []) ? 'checked' : '' }}
                                                                               class="rounded border-slate-300 text-indigo-600">
                                                                        <span class="font-mono truncate">{{ $col }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-xs text-slate-400 italic">Form ini belum memiliki tab detail.</div>
                                            @endif

                                        @elseif($section['type'] === 'summary')
                                            <div>
                                                <label class="text-xs text-slate-500">Field total/ringkasan yang ditampilkan:</label>
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach($availableFields as $af)
                                                        <label class="flex items-center gap-1 text-xs px-2 py-0.5 rounded {{ in_array($af, $section['config']['fields'] ?? []) ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-50 text-slate-500' }} cursor-pointer hover:bg-indigo-50 transition-colors">
                                                            <input type="checkbox"
                                                                   wire:click="$set('sections.{{ $idx }}.config.fields', {{ json_encode(in_array($af, $section['config']['fields'] ?? []) ? array_values(array_diff($section['config']['fields'], [$af])) : array_merge($section['config']['fields'] ?? [], [$af])) }}); $wire.regenerateHtml()"
                                                                   {{ in_array($af, $section['config']['fields'] ?? []) ? 'checked' : '' }}
                                                                   class="rounded border-slate-300 text-indigo-600 sr-only">
                                                            <span class="font-mono">{{ $af }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'signatures')
                                            <div class="space-y-2">
                                                <div>
                                                    <label class="text-xs text-slate-500">Jumlah Tanda Tangan</label>
                                                    <select wire:model.live="sections.{{ $idx }}.config.count" wire:change="regenerateHtml" class="text-sm rounded border-slate-300 px-2 py-1">
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                    </select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    @for($i = 0; $i < ($section['config']['count'] ?? 2); $i++)
                                                        <div>
                                                            <label class="text-xs text-slate-500">Label {{ $i + 1 }}</label>
                                                            <input type="text" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.labels.{{ $i }}" wire:change="regenerateHtml" class="w-full text-sm rounded border-slate-300 px-2 py-1">
                                                        </div>
                                                    @endfor
                                                </div>
                                            </div>

                                        @elseif($section['type'] === 'notes')
                                            <div>
                                                <label class="text-xs text-slate-500">Teks Catatan</label>
                                                <textarea wire:model.live.debounce.500ms="sections.{{ $idx }}.config.text" wire:change="regenerateHtml" rows="2" class="w-full text-sm rounded border-slate-300 px-2 py-1" placeholder="Contoh: Barang yang sudah dibeli tidak dapat dikembalikan."></textarea>
                                            </div>

                                        @elseif($section['type'] === 'qrcode')
                                            <div class="flex items-center gap-4">
                                                <div>
                                                    <label class="text-xs text-slate-500">Ukuran (px)</label>
                                                    <input type="number" wire:model.live.debounce.500ms="sections.{{ $idx }}.config.size" wire:change="regenerateHtml" class="w-20 text-sm rounded border-slate-300 px-2 py-1" min="60" max="200">
                                                </div>
                                                <div class="text-xs text-slate-400">QR Code otomatis diisi data dari konfigurasi QR halaman ini.</div>
                                            </div>

                                        @elseif($section['type'] === 'separator')
                                            <div class="flex items-center gap-3">
                                                <label class="text-xs text-slate-500">Gaya Garis:</label>
                                                <select wire:model.live="sections.{{ $idx }}.config.style" wire:change="regenerateHtml" class="text-sm rounded border-slate-300 px-2 py-1">
                                                    <option value="solid">─── Solid</option>
                                                    <option value="dashed">- - - Dashed</option>
                                                    <option value="double">═══ Double</option>
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                @else
                    {{-- HTML Tab (Advanced) --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">HTML</label>
                            <textarea wire:model.live.debounce.1000ms="htmlContent" rows="14" class="block w-full font-mono text-xs rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">CSS</label>
                            <textarea wire:model.live.debounce.1000ms="cssContent" rows="6" class="block w-full font-mono text-xs rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══ PANEL 3: Live Preview (Kanan) ═══ --}}
        <div class="w-[380px] shrink-0 bg-white rounded-lg border border-slate-200 overflow-hidden flex flex-col">
            <div class="px-3 py-2 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pratinjau Cetakan</span>
                @if($templateId)
                    <button wire:click="deleteTemplate('{{ $templateId }}')" wire:confirm="Hapus template ini?" class="text-xs text-rose-500 hover:text-rose-700">Hapus</button>
                @endif
            </div>
            <div class="flex-1 overflow-auto p-2 bg-slate-100">
                <div class="bg-white shadow-md rounded mx-auto" style="width: 350px; min-height: 500px; padding: 20px; transform-origin: top center; font-size: 10px;">
                    <div x-html="previewHtml"></div>
                </div>
            </div>
        </div>

    </div>
</div>
