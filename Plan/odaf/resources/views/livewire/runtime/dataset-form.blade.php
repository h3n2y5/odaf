<div>
    @php $hasSplit = ! empty($details) && $isEdit && $key; @endphp
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" :title="$vm['title']">
      <div @if($hasSplit) x-data="hdSplit(@js($pageCode))" @endif>
        {{-- ===== PANE ATAS: HEADER ===== --}}
        <div @if($hasSplit) x-ref="topPane" :style="`height:${topH}px`" class="overflow-auto pr-1 border-b border-slate-200" @endif>
        <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                    {{ $isEdit ? 'Ubah' : 'Baru' }} &mdash; {{ $vm['title'] }}
                    @if ($isAdmin ?? false)
                        <a href="{{ route('studio.grid', ['table' => $tableName]) }}" target="_blank"
                           class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 hover:bg-indigo-100 hover:text-indigo-700" title="Buka Data Manager (Admin)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                            {{ $tableName }}
                        </a>
                    @endif
                </h1>
                <a href="{{ route('odaf.grid', ['appCode' => $appCode, 'pageCode' => $pageCode]) }}"
                   wire:navigate class="text-sm text-slate-500 hover:underline">&larr; Kembali</a>
            </div>

            <form wire:submit="save" class="rounded-lg border border-slate-200 bg-white p-6">
                <div class="grid gap-5 grid-cols-{{ $vm['columns'] }}">
                    @foreach ($vm['fields'] as $field)
                        @php
                            $model = 'form.' . $field['column'];
                            // Field parameter LOV disinkronkan saat blur agar dropdown
                            // dependen (mis. Customer Group) ikut ter-refresh.
                            $wm = 'wire:model' . (($field['isLovParam'] ?? false) ? '.blur' : '');
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                {{ $field['label'] }}
                                @if ($field['required'])
                                    <span class="text-rose-500">*</span>
                                @endif
                            </label>

                            @if ($field['masked'] ?? false)
                                <input type="text" value="&bull;&bull;&bull;&bull;&bull;&bull;" disabled
                                       class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-400 tracking-widest"
                                       title="Data rahasia (disamarkan)">
                                <p class="mt-1 text-xs text-slate-400">Data rahasia &mdash; disamarkan untuk peran Anda.</p>
                            @elseif ($field['widget'] === 'textarea')
                                <textarea {{ $wm }}="{{ $model }}" rows="3"
                                          @if ($field['readonly']) readonly @endif
                                          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            @elseif ($field['widget'] === 'checkbox')
                                <input type="checkbox" {{ $wm }}="{{ $model }}"
                                       @if ($field['readonly']) disabled @endif
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            @elseif ($field['widget'] === 'select')
                                <select wire:model.live="{{ $model }}"
                                        @if ($field['readonly']) disabled @endif
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- pilih --</option>
                                    @foreach (($field['options'] ?? []) as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['widget'] === 'number')
                                @php
                                    $cfg = $field['config'] ?? [];
                                    $decimals = array_key_exists('decimals', $cfg) && $cfg['decimals'] !== null && $cfg['decimals'] !== ''
                                        ? (int) $cfg['decimals'] : null;
                                    $sep = (bool) ($cfg['thousandsSep'] ?? false);
                                @endphp
                                <input type="text" inputmode="decimal"
                                       x-data="odafNumber(@js((string) ($field['value'] ?? '')), { model: @js($model), decimals: {{ $decimals === null ? 'null' : $decimals }}, sep: {{ $sep ? 'true' : 'false' }} })"
                                       :value="display"
                                       @focus="onFocus" @input="onInput($event)" @blur="onBlur"
                                       @if ($field['readonly']) readonly @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-right focus:border-indigo-500 focus:ring-indigo-500">
                            @else
                                <input type="{{ $field['widget'] }}" {{ $wm }}="{{ $model }}"
                                       @if ($field['readonly']) readonly @endif
                                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @endif

                            @error($model)
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                @error('form')
                    <p class="mt-4 text-sm text-rose-600">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex items-center gap-3">
                    @if ($canSave)
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    @else
                        <span class="inline-flex items-center rounded-md bg-slate-100 px-4 py-2 text-slate-500 text-sm font-medium" title="Akses hanya-baca">Hanya baca &mdash; tidak dapat menyimpan</span>
                    @endif
                    <a href="{{ route('odaf.grid', ['appCode' => $appCode, 'pageCode' => $pageCode]) }}"
                       wire:navigate class="text-sm text-slate-500 hover:underline">{{ $canSave ? 'Batal' : 'Kembali' }}</a>
                </div>
            </form>

            {{-- Panel workflow (BB-06) --}}
            @if ($wf)
                <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Workflow</h2>
                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                            'bg-emerald-100 text-emerald-700' => $wf['final'],
                            'bg-amber-100 text-amber-700' => ! $wf['final'],
                        ])>
                            {{ $wf['stateName'] ?? '-' }}
                        </span>
                    </div>

                    @error('workflow')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror

                    @if (! empty($wf['transitions']))
                        <div class="mt-4">
                            <input type="text" wire:model="workflowComment" placeholder="Komentar (opsional)"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm mb-3 focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($wf['transitions'] as $t)
                                    <button type="button"
                                            wire:click="performWorkflow('{{ $t['action'] }}')"
                                            wire:confirm="Jalankan aksi: {{ $t['label'] }}?"
                                            class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                                        {{ $t['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($wf['final'])
                        <p class="mt-3 text-sm text-emerald-600">Proksi selesai. Tidak ada aksi lanjutan.</p>
                    @else
                        <p class="mt-3 text-sm text-slate-400">Tidak ada aksi yang tersedia untuk peran Anda.</p>
                    @endif

                    {{-- Riwayat --}}
                    @if (! empty($wf['history']))
                        <div class="mt-6">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Riwayat</h3>
                            <ol class="space-y-2">
                                @foreach ($wf['history'] as $h)
                                    <li class="flex items-start gap-3 text-sm">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-slate-300"></span>
                                        <div>
                                            <span class="font-medium text-slate-700">{{ $h['action'] }}</span>
                                            @if ($h['fromState'])
                                                <span class="text-slate-400">{{ $h['fromState'] }} &rarr; {{ $h['toState'] }}</span>
                                            @else
                                                <span class="text-slate-400">&rarr; {{ $h['toState'] }}</span>
                                            @endif
                                            <div class="text-xs text-slate-400">
                                                {{ $h['actor'] ?? 'sistem' }} &middot; {{ $h['at'] }}
                                                @if ($h['comment']) &middot; "{{ $h['comment'] }}" @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Catatan mode create: detail tersedia setelah header disimpan --}}
            @if (! empty($details) && ! $hasSplit)
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Simpan data header terlebih dahulu, lalu grid detail untuk menambah baris akan tersedia di sini.
                </div>
            @endif
        </div>{{-- /max-w-3xl --}}
        </div>{{-- /pane atas --}}

        @if ($hasSplit)
            {{-- Splitter horizontal (geser atas/bawah, klik-ganda untuk reset) --}}
            <div class="hd-splitter" @mousedown="startDrag($event)" @touchstart="startDrag($event)" @dblclick="resetSplit()"
                 title="Geser untuk mengatur tinggi header/detail (klik ganda: reset)"></div>

            {{-- ===== PANE BAWAH: DETAIL TABS ===== --}}
            <div class="pr-1 flex flex-col min-h-0 bg-slate-50" x-data="{ activeTab: '{{ $details[0]['pageCode'] ?? '' }}' }">
                <div class="max-w-5xl bg-white border-b border-slate-200">
                    <nav class="-mb-px flex space-x-1 px-4 overflow-x-auto hide-scrollbar">
                        @foreach ($details as $d)
                            <button type="button"
                                    @click="activeTab = '{{ $d['pageCode'] }}'"
                                    :class="activeTab === '{{ $d['pageCode'] }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 border-transparent'"
                                    class="whitespace-nowrap border-b-2 py-3 px-4 text-sm font-semibold uppercase tracking-wider transition-colors duration-150">
                                {{ $d['title'] }}
                            </button>
                        @endforeach
                    </nav>
                </div>
                <div class="max-w-5xl flex-1 overflow-y-auto p-4">
                    @foreach ($details as $d)
                        <div x-show="activeTab === '{{ $d['pageCode'] }}'" x-cloak>
                            @livewire('runtime.detail-grid', [
                                'appCode' => $appCode,
                                'childPageCode' => $d['pageCode'],
                                'fkColumn' => $d['fkColumn'],
                                'parentKey' => $key,
                                'parentForm' => $this->form,
                                'title' => $d['title'],
                            ], key('detail-' . $d['pageCode']))
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
      </div>{{-- /kontainer split --}}
    </x-odaf-shell>

    @script
    <script>
        Alpine.data('odafNumber', (initial, opts) => ({
            raw: (initial === null || initial === undefined) ? '' : String(initial),
            display: '',
            opts: opts,

            init() {
                // Tampilan awal diformat sesuai locale client.
                this.display = window.odafNum(this.raw, this.opts.decimals, this.opts.sep);
            },

            // Saat fokus: tampilkan nilai tanpa pemisah ribuan, desimal sesuai locale.
            onFocus() {
                if (this.raw === '' || this.raw === null) {
                    this.display = '';
                    return;
                }
                const d = window.odafNumberParts.decimal;
                this.display = d === '.' ? this.raw : String(this.raw).split('.').join(d);
            },

            onInput(e) {
                this.display = e.target.value;
                // Parse sesuai locale -> angka kanonik untuk disimpan.
                this.raw = window.odafParseNum(e.target.value);
                this.$wire.set(this.opts.model, this.raw === '' ? null : this.raw, false);
            },

            onBlur() {
                this.display = window.odafNum(this.raw, this.opts.decimals, this.opts.sep);
            },
        }));

        // Splitter header-detail: geser tinggi pane atas (header) vs bawah (detail).
        // Berbasis delta + batas dari tinggi viewport agar bebas digeser dua arah.
        Alpine.data('hdSplit', (pageCode) => ({
            topH: 320,

            minTop() { return 80; },
            maxTop() { return Math.max(this.minTop(), window.innerHeight - 220); },

            init() {
                const saved = parseInt(localStorage.getItem('hdsplit:' + pageCode) || '0');
                this.topH = saved > 60 ? saved : Math.min(360, Math.round(window.innerHeight * 0.4));
                this.topH = Math.max(this.minTop(), Math.min(this.maxTop(), this.topH));
            },

            startDrag(e) {
                e.preventDefault();
                const startY = e.clientY;
                const startH = this.topH;
                const handle = e.currentTarget;
                handle.classList.add('active');

                const move = (ev) => {
                    const y = (ev.touches ? ev.touches[0].clientY : ev.clientY);
                    this.topH = Math.max(this.minTop(), Math.min(this.maxTop(), startH + (y - startY)));
                };
                const up = () => {
                    handle.classList.remove('active');
                    localStorage.setItem('hdsplit:' + pageCode, this.topH);
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                    document.removeEventListener('touchmove', move);
                    document.removeEventListener('touchend', up);
                    document.body.style.userSelect = '';
                };
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
                document.addEventListener('touchmove', move, { passive: false });
                document.addEventListener('touchend', up);
                document.body.style.userSelect = 'none';
            },

            resetSplit() {
                this.topH = Math.min(360, Math.round(window.innerHeight * 0.4));
                localStorage.setItem('hdsplit:' + pageCode, this.topH);
            },
        }));
    </script>
    @endscript
</div>
