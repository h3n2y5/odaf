@props([
    'appCode',
    'appName' => 'ODAF',
    'nav' => [],
])

<div class="flex min-h-screen" x-data="{
    sidebarOpen: localStorage.getItem('odaf_sidebar_open') !== 'false',
    sidebarWidth: parseInt(localStorage.getItem('odaf_sidebar_width') || 256),
    minWidth: 200,
    maxWidth: 500,
    isResizing: false,
    
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        localStorage.setItem('odaf_sidebar_open', this.sidebarOpen);
    },
    
    startResize(e) {
        this.isResizing = true;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    },
    
    resize(e) {
        if (!this.isResizing) return;
        
        const newWidth = e.clientX;
        if (newWidth >= this.minWidth && newWidth <= this.maxWidth) {
            this.sidebarWidth = newWidth;
            localStorage.setItem('odaf_sidebar_width', newWidth);
        }
    },
    
    stopResize() {
        this.isResizing = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    }
}"
@mousemove.window="resize($event)"
@mouseup.window="stopResize()">

    {{-- Sidebar --}}
    <aside 
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        :style="`width: ${sidebarWidth}px`"
        class="shrink-0 bg-slate-900 text-slate-200 flex flex-col relative">
        
        {{-- Header --}}
        <a href="{{ route('odaf.home', ['appCode' => $appCode]) }}"
           class="px-5 py-4 border-b border-slate-700 block">
            <div class="text-xs uppercase tracking-widest text-slate-400">ODAF Runtime</div>
            <div class="text-lg font-semibold text-white truncate">{{ $appName }}</div>
        </a>
        
        {{-- Navigation with scroll --}}
        <nav class="flex-1 overflow-y-auto py-3 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-900">
            @include('runtime.partials.menu-items', ['items' => $nav, 'depth' => 0])
        </nav>
        
        {{-- Footer --}}
        <div class="px-5 py-3 text-xs text-slate-500 border-t border-slate-800 flex items-center justify-between">
            <span>Metadata-driven &middot; F1</span>
            <span class="text-slate-600" x-text="`${sidebarWidth}px`"></span>
        </div>
        
        {{-- Resize handle --}}
        <div 
            @mousedown="startResize($event)"
            class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-500 transition-colors group">
            <div class="absolute right-0 top-0 bottom-0 w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
    </aside>
    
    {{-- Toggle button (when sidebar is closed) --}}
    <button 
        x-show="!sidebarOpen"
        @click="toggleSidebar()"
        class="fixed left-0 top-4 z-50 bg-slate-900 text-white p-2 rounded-r-lg shadow-lg hover:bg-slate-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>

    {{-- Main --}}
    <main class="flex-1 flex flex-col">
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Sidebar toggle button --}}
                <button 
                    @click="toggleSidebar()"
                    class="text-slate-500 hover:text-slate-700 hover:bg-slate-100 p-2 rounded transition-colors"
                    :title="sidebarOpen ? 'Sembunyikan Menu' : 'Tampilkan Menu'">
                    <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                <div class="font-medium text-slate-700">{{ $title ?? $appName }}</div>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('manual') }}" class="text-sm {{ request()->routeIs('manual') ? 'text-indigo-600 font-medium' : 'text-slate-500 hover:text-indigo-600' }}">Manual</a>
                    <a href="{{ route('studio.home') }}" class="text-sm text-slate-500 hover:text-indigo-600">Studio</a>
                    
                    {{-- QR Scanner Button --}}
                    <a href="{{ route('odaf.scanner') }}" wire:navigate 
                       class="text-slate-400 hover:text-indigo-600 transition-colors" 
                       title="Buka QR Scanner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </a>

                    @livewire(\App\Livewire\Runtime\NotificationBell::class)
                    <span class="text-sm text-slate-500">
                        {{ auth()->user()->name ?? auth()->user()->username }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-rose-600 hover:underline">Keluar</button>
                    </form>
                @endauth
            </div>
        </header>

        <div class="p-6 flex-1 flex flex-col min-h-0">
            @if (session('odaf.status'))
                <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-2 text-emerald-700 text-sm shrink-0">
                    {{ session('odaf.status') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
