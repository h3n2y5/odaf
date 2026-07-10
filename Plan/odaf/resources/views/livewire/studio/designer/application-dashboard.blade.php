<div>
    <x-studio-shell>
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Applications</h1>
                    <p class="mt-1 text-sm text-slate-500">Design and manage your metadata-driven applications</p>
                </div>
                <button wire:click="createApplication" 
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Application
                </button>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Applications Grid --}}
        @if (count($applications) === 0)
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-slate-900">No applications yet</h3>
                <p class="mt-2 text-sm text-slate-500">Get started by creating your first application.</p>
                <button wire:click="createApplication" 
                        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Application
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($applications as $app)
                    <div class="bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        {{-- Card Header --}}
                        <div class="p-6 border-b border-slate-100">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-slate-900 truncate">
                                        {{ $app['OBJECT_NAME'] }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500 font-mono">
                                        {{ $app['OBJECT_CODE'] }}
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-700' => $app['STATUS'] === 'PUBLISHED',
                                    'bg-amber-100 text-amber-700' => $app['STATUS'] === 'DRAFT',
                                    'bg-slate-100 text-slate-700' => !in_array($app['STATUS'], ['PUBLISHED', 'DRAFT']),
                                ])>
                                    {{ $app['STATUS'] }}
                                </span>
                            </div>

                            @if ($app['DESCRIPTION'])
                                <p class="mt-3 text-sm text-slate-600 line-clamp-2">
                                    {{ $app['DESCRIPTION'] }}
                                </p>
                            @endif
                        </div>

                        {{-- Stats --}}
                        <div class="px-6 py-4 bg-slate-50">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-indigo-600">{{ $app['PAGE_COUNT'] ?? 0 }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Forms</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-emerald-600">{{ $app['FIELD_COUNT'] ?? 0 }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Fields</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-amber-600">{{ $app['MENU_COUNT'] ?? 0 }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Menus</div>
                                </div>
                            </div>

                            {{-- Additional Stats Row --}}
                            <div class="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                                <span>{{ $app['MODULE_COUNT'] ?? 0 }} modules</span>
                                <span>{{ $app['LOV_COUNT'] ?? 0 }} LOVs</span>
                                @if ($app['ACTIVE_VERSION'])
                                    <span class="text-indigo-600 font-medium">v{{ $app['ACTIVE_VERSION'] }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="px-6 py-4 flex items-center gap-2">
                            <a href="/studio/designer/app/{{ $app['ID'] }}" 
                               wire:navigate
                               class="flex-1 inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                                Design
                            </a>
                            <button wire:click="compileApp('{{ $app['ID'] }}')" 
                                    wire:confirm="Compile and activate {{ $app['OBJECT_CODE'] }}?"
                                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </button>
                            <a href="/studio/t/APP_APPLICATION/edit/{{ base64_encode(json_encode(['OBJECT_ID' => $app['ID']])) }}" 
                               class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Footer Stats --}}
        <div class="mt-12 pt-8 border-t border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold text-slate-900">{{ count($applications) }}</div>
                    <div class="text-sm text-slate-500 mt-1">Total Applications</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-indigo-600">{{ array_sum(array_column($applications, 'PAGE_COUNT')) }}</div>
                    <div class="text-sm text-slate-500 mt-1">Total Forms</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-emerald-600">{{ array_sum(array_column($applications, 'FIELD_COUNT')) }}</div>
                    <div class="text-sm text-slate-500 mt-1">Total Fields</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-amber-600">{{ array_sum(array_column($applications, 'LOV_COUNT')) }}</div>
                    <div class="text-sm text-slate-500 mt-1">Total LOVs</div>
                </div>
            </div>
        </div>
    </x-studio-shell>
</div>
