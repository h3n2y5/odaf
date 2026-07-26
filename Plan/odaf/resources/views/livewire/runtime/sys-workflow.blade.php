<div>
    <x-odaf-shell :app-code="$appCode" :app-name="$appName" :nav="$nav" current-menu="MENU_WORKFLOW">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Workflow Setup</h1>
            <p class="text-slate-500 mt-1">Configure multi-step approval workflows for POS documents.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center justify-center py-16">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
            <h3 class="text-lg font-bold text-slate-700">Approval Workflows</h3>
            <p class="text-slate-500 mt-2 text-center max-w-md">The core approval engine is managed via ODAF Studio Dataset Workflows. Please open the Studio Designer to bind workflows to your POS Datasets.</p>
            <a href="{{ route('studio.designer') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-white font-medium hover:bg-emerald-700 transition-colors">
                Buka ODAF Studio
            </a>
        </div>
    </x-odaf-shell>
</div>
