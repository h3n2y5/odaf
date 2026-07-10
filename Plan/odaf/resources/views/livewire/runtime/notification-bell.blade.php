<div class="relative" wire:poll.30s>
    <button type="button" wire:click="toggle"
            class="relative inline-flex items-center rounded-md p-2 text-slate-500 hover:bg-slate-100">
        <span class="text-lg leading-none">&#128276;</span>
        @if ($unread > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">
                {{ $unread > 99 ? '99+' : $unread }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 mt-2 w-80 rounded-lg border border-slate-200 bg-white shadow-lg z-20">
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-sm font-semibold text-slate-700">Notifikasi</span>
                @if ($unread > 0)
                    <button type="button" wire:click="markAllRead" class="text-xs text-indigo-600 hover:underline">
                        Tandai semua dibaca
                    </button>
                @endif
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                @forelse ($items as $item)
                    <div @class([
                        'px-4 py-3 text-sm',
                        'bg-indigo-50/60' => ! $item['read'],
                    ])>
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-medium text-slate-700">{{ $item['subject'] }}</div>
                            @if (! $item['read'])
                                <button type="button" wire:click="markRead('{{ $item['id'] }}')"
                                        class="text-[11px] text-indigo-600 hover:underline shrink-0">tandai</button>
                            @endif
                        </div>
                        <div class="text-slate-500 mt-0.5">{{ $item['body'] }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">{{ $item['at'] }}</div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-400">Belum ada notifikasi.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
