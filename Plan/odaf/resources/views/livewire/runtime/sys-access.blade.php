<div>
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" current-menu="MENU_ACCESS_CTRL">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Menu Access Control</h1>
            <p class="text-slate-500 mt-1">Configure which roles can access specific menus in the application.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center justify-center py-16">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4v-5.257A6 6 0 0115 7h.01z"></path></svg>
            <h3 class="text-lg font-bold text-slate-700">Access Control Module</h3>
            <p class="text-slate-500 mt-2 text-center max-w-md">The ODAF Access Control is a centralized Studio feature. To configure menu access matrices, please open the Studio Designer.</p>
            <a href="{{ route('studio.designer.access') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-white font-medium hover:bg-emerald-700 transition-colors">
                Buka Menu Access Control
            </a>
        </div>
    </x-odaf-shell>
</div>
