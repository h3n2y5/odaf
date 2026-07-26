<div>
    <x-studio-shell :groups="$groups" :current="$table" :title="$table">
        <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-slate-800">
                    {{ $isEdit ? 'Ubah' : 'Baru' }} &mdash; {{ $table }}
                </h1>
                <a href="{{ route('studio.grid', ['table' => $table]) }}" wire:navigate
                   class="text-sm text-slate-500 hover:underline">&larr; Kembali</a>
            </div>

            @error('form')
                <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-2 text-rose-700 text-sm">{{ $message }}</div>
            @enderror

            <form wire:submit="save" class="rounded-lg border border-slate-200 bg-white p-6">
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($fields as $field)
                        @php $model = 'form.' . $field['column']; @endphp
                        <div @class(['md:col-span-2' => in_array($field['widget'], ['textarea'])])>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                {{ $field['label'] }}
                                @if ($field['required']) <span class="text-rose-500">*</span> @endif
                                <span class="ml-1 text-[11px] font-normal text-slate-400">{{ $field['column'] }}</span>
                            </label>

                            @if ($field['widget'] === 'select')
                                <select wire:model="{{ $model }}" @if ($field['readonly']) disabled @endif
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- pilih --</option>
                                    @foreach ($field['options'] as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['widget'] === 'textarea')
                                <textarea wire:model="{{ $model }}" rows="3" @if ($field['readonly']) readonly @endif
                                          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            @elseif ($field['widget'] === 'number')
                                <input type="number" step="any" wire:model="{{ $model }}" @if ($field['readonly']) readonly @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @elseif ($field['widget'] === 'date')
                                <input type="date" wire:model="{{ $model }}" @if ($field['readonly']) readonly @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @elseif ($field['widget'] === 'datetime-local')
                                <input type="datetime-local" wire:model="{{ $model }}" @if ($field['readonly']) readonly @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @elseif ($field['column'] === 'PASSWORD_HASH')
                                <input type="password" wire:model="{{ $model }}"
                                       @if ($field['readonly']) readonly @endif
                                       placeholder="{{ $isEdit ? '(kosongkan jika tidak ingin mengubah sandi)' : '' }}"
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @elseif ($field['widget'] === 'photo')
                                <div class="space-y-3">
                                    @if ($this->form[$field['column']] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                        <img src="{{ $this->form[$field['column']]->temporaryUrl() }}" class="h-24 w-24 object-cover rounded-md border border-slate-200 shadow-sm">
                                    @elseif (!empty($this->form[$field['column']]))
                                        <button type="button" 
                                                @click="$dispatch('open-pdf-viewer', { id: 'pdfViewerModal', url: '{{ asset('storage/' . $this->form[$field['column']]) }}' })" 
                                                class="relative group block rounded-md border border-slate-200 shadow-sm overflow-hidden focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                title="Lihat Gambar Aman">
                                            <img src="{{ asset('storage/' . $this->form[$field['column']]) }}" class="h-24 w-24 object-cover pointer-events-none select-none">
                                            <div class="absolute inset-0 bg-slate-900 bg-opacity-0 group-hover:bg-opacity-30 transition-all flex items-center justify-center">
                                                <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </div>
                                        </button>
                                    @endif
                                    <input type="file" accept="image/*" wire:model="{{ $model }}" @if ($field['readonly']) disabled @endif
                                           class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 focus:outline-none">
                                </div>
                            @elseif ($field['widget'] === 'document')
                                <div class="space-y-3">
                                    @if (!empty($this->form[$field['column']]) && !($this->form[$field['column']] instanceof \Illuminate\Http\UploadedFile))
                                        @php
                                            $docUrl = asset('storage/' . $this->form[$field['column']]);
                                            $isPdf = strtolower(pathinfo($this->form[$field['column']], PATHINFO_EXTENSION)) === 'pdf';
                                        @endphp
                                        <div>
                                            @if ($isPdf)
                                                <button type="button" 
                                                        @click="$dispatch('open-pdf-viewer', { id: 'pdfViewerModal', url: '{{ $docUrl }}' })"
                                                        class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium bg-indigo-50 px-3 py-1.5 rounded-md transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    Lihat Dokumen (Secure)
                                                </button>
                                            @else
                                                <a href="{{ $docUrl }}" download class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium bg-indigo-50 px-3 py-1.5 rounded-md transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    Download Dokumen
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                    <input type="file" wire:model="{{ $model }}" @if ($field['readonly']) disabled @endif
                                           class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 focus:outline-none">
                                </div>
                            @else
                                <input type="text" wire:model="{{ $model }}"
                                       @if ($field['readonly']) readonly @endif
                                       @if ($field['widget'] === 'raw') placeholder="hex RAW(16)" @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            @endif

                            @error($model)
                                <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                    <a href="{{ route('studio.grid', ['table' => $table]) }}" wire:navigate
                       class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        </div>
    </x-studio-shell>

    <x-secure-pdf-viewer id="pdfViewerModal" />
</div>
