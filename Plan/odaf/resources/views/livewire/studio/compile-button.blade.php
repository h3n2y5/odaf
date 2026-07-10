<div class="flex items-center gap-2">
    @if ($message)
        <span @class([
            'text-xs',
            'text-emerald-600' => $status === 'ok',
            'text-rose-600' => $status === 'error',
        ])>{{ $message }}</span>
    @endif
    <button type="button" wire:click="recompile"
            class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-white text-xs font-medium hover:bg-emerald-700">
        <span wire:loading.remove wire:target="recompile">&#9889; Kompilasi &amp; Aktifkan</span>
        <span wire:loading wire:target="recompile">Mengompilasi...</span>
    </button>
</div>
