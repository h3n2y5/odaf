@props([
    'title' => 'ODAF Studio',
])

<div class="flex min-h-screen bg-slate-50" x-data="{
    sidebarOpen: localStorage.getItem('studio_sidebar_open') !== 'false',
    sidebarWidth: parseInt(localStorage.getItem('studio_sidebar_width') || 256),
    minWidth: 200,
    maxWidth: 500,
    isResizing: false,

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        localStorage.setItem('studio_sidebar_open', this.sidebarOpen);
    },
    startResize() {
        this.isResizing = true;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    },
    resize(e) {
        if (!this.isResizing) return;
        const newWidth = e.clientX;
        if (newWidth >= this.minWidth && newWidth <= this.maxWidth) {
            this.sidebarWidth = newWidth;
            localStorage.setItem('studio_sidebar_width', newWidth);
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
        <a href="{{ route('studio.designer') }}"
           class="px-5 py-4 border-b border-slate-700 block">
            <div class="text-xs uppercase tracking-widest text-slate-400">ODAF Studio</div>
            <div class="text-lg font-semibold text-white">Visual Designer</div>
        </a>

        <nav class="flex-1 overflow-y-auto py-3 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-900">
            {{-- Designer Section --}}
            <div class="px-3 mb-2">
                <div class="text-xs uppercase tracking-widest text-slate-500 px-2 py-1">Designer</div>
            </div>

            <a href="{{ route('studio.designer') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.designer*') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Applications
            </a>



            <a href="{{ route('studio.designer.lov.list') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.designer.lov*') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                LOVs
            </a>

            <a href="{{ route('studio.designer.access') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.designer.access') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Access Control
            </a>

            <a href="{{ route('studio.designer.roles-users') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.designer.roles-users') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Roles & Users
            </a>
            <div class="px-3 mt-6 mb-2">
                <div class="text-xs uppercase tracking-widest text-slate-500 px-2 py-1">Data Manager</div>
            </div>

            <a href="{{ route('studio.home') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.home') || request()->routeIs('studio.grid') || request()->routeIs('studio.form') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                </svg>
                All Tables
            </a>

            <a href="{{ route('studio.sql') }}"
               class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('studio.sql') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                SQL Runner
            </a>
        </nav>

        <div class="px-5 py-3 text-xs text-slate-500 border-t border-slate-800 flex items-center justify-between">
            <span>F3 - Visual Designer</span>
            <span class="text-slate-600" x-text="`${sidebarWidth}px`"></span>
        </div>

        {{-- Resize handle --}}
        <div @mousedown="startResize()"
             class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-500 transition-colors group">
            <div class="absolute right-0 top-0 bottom-0 w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
    </aside>

    {{-- Toggle button (saat sidebar tersembunyi) --}}
    <button x-show="!sidebarOpen" @click="toggleSidebar()"
            class="fixed left-0 top-4 z-50 bg-slate-900 text-white p-2 rounded-r-lg shadow-lg hover:bg-slate-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>

    {{-- Main --}}
    <main class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="toggleSidebar()"
                        class="text-slate-500 hover:text-slate-700 hover:bg-slate-100 p-2 rounded transition-colors"
                        :title="sidebarOpen ? 'Sembunyikan Menu' : 'Tampilkan Menu'">
                    <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="font-medium text-slate-700">{{ $title }}</div>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('manual') }}" class="text-sm text-slate-500 hover:text-indigo-600">Manual</a>
                    <a href="{{ route('odaf.launcher') }}" class="text-sm text-slate-500 hover:text-indigo-600">Runtime</a>
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
            {{ $slot }}
        </div>
    </main>
</div>
