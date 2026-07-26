<div>
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" current-menu="MENU_USER_MGR">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">User & Role Manager</h1>
            <p class="text-slate-500 mt-1">Manage POS users, assign roles, and configure user access.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center justify-center py-16">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <h3 class="text-lg font-bold text-slate-700">Security Module</h3>
            <p class="text-slate-500 mt-2 text-center max-w-md">The ODAF Security Module is designed for Superusers. To manage users and roles, please access the Studio Designer.</p>
            <a href="{{ route('studio.designer.roles-users') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-white font-medium hover:bg-emerald-700 transition-colors">
                Buka Role & User Manager
            </a>
        </div>
    </x-odaf-shell>
</div>
