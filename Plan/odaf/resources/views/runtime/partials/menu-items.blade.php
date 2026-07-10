{{-- Render item menu secara rekursif. Vars: $items, $depth --}}
@foreach ($items as $item)
    @php $hasChildren = ! empty($item['children']); @endphp
    @if (! empty($item['url']))
        {{-- Menu dengan halaman -> link yang dapat diklik --}}
        <a href="{{ $item['url'] }}"
           class="flex items-center gap-2 px-5 py-2 text-sm hover:bg-slate-800 hover:text-white transition"
           style="padding-left: {{ 1.25 + $depth * 0.75 }}rem">
            @if (! empty($item['icon']))
                <span class="text-slate-400">&#9679;</span>
            @endif
            <span>{{ $item['name'] }}</span>
        </a>
    @elseif ($hasChildren)
        {{-- Menu induk (punya submenu) -> judul grup --}}
        <div class="px-5 py-2 text-xs uppercase tracking-wide text-slate-500"
             style="padding-left: {{ 1.25 + $depth * 0.75 }}rem">
            {{ $item['name'] }}
        </div>
    @else
        {{-- Menu tanpa halaman & tanpa submenu -> belum bisa dibuka --}}
        <div class="flex items-center gap-2 px-5 py-2 text-sm text-slate-500"
             style="padding-left: {{ 1.25 + $depth * 0.75 }}rem">
            <span>{{ $item['name'] }}</span>
            @if (! empty($item['configUrl']))
                {{-- Khusus administrator: menuju konfigurasi lanjutan (atur halaman) --}}
                <a href="{{ $item['configUrl'] }}" wire:navigate
                   class="text-[10px] text-amber-400 underline hover:text-amber-300"
                   title="Atur halaman untuk menu ini (konfigurasi lanjutan)">(tanpa halaman)</a>
            @else
                <span class="text-[10px] text-amber-400/80">(tanpa halaman)</span>
            @endif
        </div>
    @endif

    @if ($hasChildren)
        @include('runtime.partials.menu-items', ['items' => $item['children'], 'depth' => $depth + 1])
    @endif
@endforeach
