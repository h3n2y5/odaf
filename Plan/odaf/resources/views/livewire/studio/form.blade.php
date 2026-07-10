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
                            @else
                                <input type="text" wire:model="{{ $model }}"
                                       @if ($field['readonly']) readonly @endif
                                       @if ($field['widget'] === 'raw') placeholder="hex RAW(16)" @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            @endif
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
</div>
