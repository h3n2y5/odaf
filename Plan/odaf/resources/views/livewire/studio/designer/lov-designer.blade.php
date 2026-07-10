<div>
    <x-studio-shell :title="($isNewLov ? 'New' : 'Edit') . ' List of Values'">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('studio.designer') }}" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ $isNewLov ? 'Create New LOV' : 'Edit LOV: ' . $lov['object_name'] }}
                    </h1>
                </div>
                <p class="mt-1 text-sm text-slate-500">List of Values - Visual Designer</p>
            </div>

            <button wire:click="save" 
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save LOV
            </button>
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

        @if (session('info'))
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-blue-700">
                {{ session('info') }}
            </div>
        @endif

        @error('lov.object_code')
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">{{ $message }}</div>
        @enderror
        @error('lov.object_name')
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">{{ $message }}</div>
        @enderror

        {{-- Main Layout: 2 columns (Editor + Preview) --}}
        <div class="flex gap-6 h-[calc(100vh-250px)]">
            {{-- Left: Editor --}}
            <div class="flex-1 bg-white rounded-lg border border-slate-200 overflow-y-auto">
                <div class="p-6 space-y-6">
                    {{-- Basic Info --}}
                    <div class="pb-6 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Basic Information</h2>
                        
                        <div class="space-y-4">
                            {{-- Object Code --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Code <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" wire:model="lov.object_code" 
                                       class="w-full rounded-lg border-slate-300 font-mono"
                                       placeholder="LOV_CUSTOMER_TYPE">
                            </div>

                            {{-- Object Name --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" wire:model="lov.object_name" 
                                       class="w-full rounded-lg border-slate-300"
                                       placeholder="Customer Types">
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                                <textarea wire:model="lov.description" rows="2" 
                                          class="w-full rounded-lg border-slate-300"
                                          placeholder="Optional description..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Source Type Selector --}}
                    <div class="pb-6 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Source Type</h2>
                        
                        <div class="grid grid-cols-3 gap-4">
                            {{-- STATIC --}}
                            <button wire:click="setSourceType('STATIC')"
                                    type="button"
                                    @class([
                                        'p-4 rounded-lg border-2 text-left transition-all',
                                        'border-indigo-500 bg-indigo-50' => $lov['lov_type'] === 'STATIC',
                                        'border-slate-200 bg-white hover:border-slate-300' => $lov['lov_type'] !== 'STATIC',
                                    ])>
                                <div class="flex items-center gap-3">
                                    <div @class([
                                        'p-2 rounded-lg',
                                        'bg-indigo-100' => $lov['lov_type'] === 'STATIC',
                                        'bg-slate-100' => $lov['lov_type'] !== 'STATIC',
                                    ])>
                                        <svg class="w-6 h-6 {{ $lov['lov_type'] === 'STATIC' ? 'text-indigo-600' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">Static</div>
                                        <div class="text-xs text-slate-500">Key-value pairs</div>
                                    </div>
                                </div>
                            </button>

                            {{-- SQL --}}
                            <button wire:click="setSourceType('SQL')"
                                    type="button"
                                    @class([
                                        'p-4 rounded-lg border-2 text-left transition-all',
                                        'border-indigo-500 bg-indigo-50' => $lov['lov_type'] === 'SQL',
                                        'border-slate-200 bg-white hover:border-slate-300' => $lov['lov_type'] !== 'SQL',
                                    ])>
                                <div class="flex items-center gap-3">
                                    <div @class([
                                        'p-2 rounded-lg',
                                        'bg-indigo-100' => $lov['lov_type'] === 'SQL',
                                        'bg-slate-100' => $lov['lov_type'] !== 'SQL',
                                    ])>
                                        <svg class="w-6 h-6 {{ $lov['lov_type'] === 'SQL' ? 'text-indigo-600' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">SQL Query</div>
                                        <div class="text-xs text-slate-500">Custom SELECT</div>
                                    </div>
                                </div>
                            </button>

                            {{-- VIEW (Table/View) --}}
                            <button wire:click="setSourceType('VIEW')"
                                    type="button"
                                    @class([
                                        'p-4 rounded-lg border-2 text-left transition-all',
                                        'border-indigo-500 bg-indigo-50' => $lov['lov_type'] === 'VIEW',
                                        'border-slate-200 bg-white hover:border-slate-300' => $lov['lov_type'] !== 'VIEW',
                                    ])>
                                <div class="flex items-center gap-3">
                                    <div @class([
                                        'p-2 rounded-lg',
                                        'bg-indigo-100' => $lov['lov_type'] === 'VIEW',
                                        'bg-slate-100' => $lov['lov_type'] !== 'VIEW',
                                    ])>
                                        <svg class="w-6 h-6 {{ $lov['lov_type'] === 'VIEW' ? 'text-indigo-600' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">Table/View</div>
                                        <div class="text-xs text-slate-500">From table or view</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Editor based on Source Type --}}
                    <div>
                        @if ($lov['lov_type'] === 'STATIC')
                            {{-- STATIC Editor --}}
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold text-slate-900">Static Values</h2>
                                    <div class="flex gap-2">
                                        <button wire:click="addStaticRow" type="button"
                                                class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add Row
                                        </button>
                                        <button wire:click="updatePreview" type="button"
                                                class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                                            Refresh Preview
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="grid grid-cols-12 gap-2 px-2 py-1 text-xs font-medium text-slate-500 uppercase">
                                        <div class="col-span-5">Value (Code)</div>
                                        <div class="col-span-6">Label (Display)</div>
                                        <div class="col-span-1"></div>
                                    </div>

                                    @foreach ($staticPairs as $index => $pair)
                                        <div wire:key="pair-{{ $index }}" class="grid grid-cols-12 gap-2">
                                            <input type="text" 
                                                   wire:model="staticPairs.{{ $index }}.value"
                                                   class="col-span-5 rounded-lg border-slate-300 font-mono text-sm"
                                                   placeholder="RETAIL">
                                            <input type="text" 
                                                   wire:model="staticPairs.{{ $index }}.label"
                                                   class="col-span-6 rounded-lg border-slate-300 text-sm"
                                                   placeholder="Retail Customer">
                                            <button wire:click="removeStaticRow({{ $index }})" 
                                                    type="button"
                                                    class="col-span-1 flex items-center justify-center text-rose-600 hover:bg-rose-50 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        @elseif ($lov['lov_type'] === 'SQL')
                            {{-- SQL Editor --}}
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold text-slate-900">SQL Query</h2>
                                    <button wire:click="testSqlQuery" type="button"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Test Query
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <textarea wire:model="sqlQuery" rows="8"
                                              class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                              placeholder="SELECT CUSTOMER_TYPE AS VALUE, CUSTOMER_TYPE_NAME AS LABEL FROM CUSTOMER_TYPES ORDER BY CUSTOMER_TYPE_NAME"></textarea>

                                    @if (!empty($sqlTestResult))
                                        <div @class([
                                            'rounded-lg p-4 font-mono text-xs whitespace-pre-wrap',
                                            'bg-emerald-50 text-emerald-800 border border-emerald-200' => $sqlTestSuccess,
                                            'bg-rose-50 text-rose-800 border border-rose-200' => !$sqlTestSuccess,
                                        ])>{{ $sqlTestResult }}</div>
                                    @endif

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Value Column</label>
                                            <input type="text" wire:model="lov.value_column" 
                                                   class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                                   placeholder="VALUE">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Label Column</label>
                                            <input type="text" wire:model="lov.label_column" 
                                                   class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                                   placeholder="LABEL">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @else
                            {{-- VIEW / Table Editor --}}
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 mb-4">Table / View Configuration</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Table / View Name</label>
                                        <select wire:model.live="lov.source_query" wire:change="updatePreview" class="w-full rounded-lg border-slate-300 font-mono text-sm">
                                            <option value="">-- Select Table --</option>
                                            @foreach ($availableTables as $table)
                                                <option value="{{ $table['TABLE_NAME'] }}">{{ $table['TABLE_NAME'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Value Column</label>
                                            <input type="text" wire:model="lov.value_column" 
                                                   class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                                   placeholder="OBJECT_ID">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Label Column</label>
                                            <input type="text" wire:model="lov.label_column" 
                                                   class="w-full rounded-lg border-slate-300 font-mono text-sm"
                                                   placeholder="OBJECT_NAME">
                                        </div>
                                    </div>

                                    <button wire:click="updatePreview" type="button"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                                        Refresh Preview
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Preview --}}
            <div class="w-96 shrink-0 bg-white rounded-lg border border-slate-200 overflow-y-auto">
                <div class="p-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="font-semibold text-slate-900">Preview</h2>
                    <p class="text-xs text-slate-500 mt-1">How it will appear in forms</p>
                </div>

                <div class="p-6">
                    {{-- Preview Dropdown --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            {{ $lov['object_name'] ?: 'List of Values' }}
                        </label>
                        <select class="w-full rounded-lg border-slate-300">
                            <option value="">-- Select {{ $lov['object_name'] ?: 'value' }} --</option>
                            @foreach ($previewOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Stats --}}
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-indigo-600">{{ count($previewOptions) }}</div>
                            <div class="text-sm text-slate-500 mt-1">Available Options</div>
                        </div>

                        @if (count($previewOptions) > 0)
                            <div class="mt-4 space-y-2">
                                <div class="text-xs font-medium text-slate-500 uppercase">Sample Values:</div>
                                @foreach (array_slice($previewOptions, 0, 5) as $option)
                                    <div class="flex items-start gap-2 text-xs">
                                        <code class="flex-shrink-0 px-2 py-1 bg-slate-100 rounded font-mono text-slate-700">{{ $option['value'] }}</code>
                                        <span class="text-slate-600">{{ $option['label'] }}</span>
                                    </div>
                                @endforeach
                                @if (count($previewOptions) > 5)
                                    <div class="text-xs text-slate-400 italic">+ {{ count($previewOptions) - 5 }} more...</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Source Info --}}
                    <div class="mt-6 pt-6 border-t border-slate-200 text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Type:</span>
                            <span class="font-medium text-slate-900">{{ $lov['lov_type'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Code:</span>
                            <code class="font-mono text-slate-900">{{ $lov['object_code'] ?: '—' }}</code>
                        </div>
                    </div>

                    {{-- Reminder --}}
                    <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                        Setelah menyimpan, jalankan kompilasi &amp; aktivasi aplikasi agar LOV muncul di runtime form.
                    </div>
                </div>
            </div>
        </div>
    </x-studio-shell>
</div>
