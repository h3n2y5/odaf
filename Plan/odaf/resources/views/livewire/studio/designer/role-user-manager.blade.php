<div>
    <x-studio-shell>
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Roles & Users</h1>
                    <p class="mt-1 text-sm text-slate-500">Manage security roles and assign users to them</p>
                </div>
                <button wire:click="openRoleModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Role
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

        {{-- Master / Header: App & Role Selection --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 mb-6">
            <div class="max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Select Application</label>
                    <select wire:model.live="selectedAppId" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Global Roles --</option>
                        @foreach($applications as $app)
                            <option value="{{ $app['ID'] }}">{{ $app['OBJECT_NAME'] }} ({{ $app['OBJECT_CODE'] }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Select Role to Manage</label>
                    <div class="flex gap-4">
                        <select wire:model.live="selectedRoleId" class="flex-1 rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Choose Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role['ID'] }}">{{ $role['OBJECT_CODE'] }} - {{ $role['OBJECT_NAME'] }}</option>
                            @endforeach
                        </select>
                        @if($selectedRoleId)
                            <a href="/studio/designer/access-control" wire:navigate title="Manage Access Control" class="inline-flex items-center justify-center rounded-lg bg-slate-100 border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-200 transition-colors shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail / Grid: Role Users --}}
        @if($selectedRoleId)
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="text-lg font-semibold text-slate-900">Users in Role</h3>
                    <button wire:click="openAssignModal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-1.5 text-indigo-700 font-medium hover:bg-indigo-100 transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Add User
                    </button>
                </div>
                
                @if(count($roleUsers) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white border-b border-slate-200 text-sm text-slate-500">
                                    <th class="px-6 py-3 font-medium">Username</th>
                                    <th class="px-6 py-3 font-medium">Full Name</th>
                                    <th class="px-6 py-3 font-medium">Email</th>
                                    <th class="px-6 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($roleUsers as $user)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $user['USERNAME'] }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $user['FULL_NAME'] }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $user['EMAIL'] ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <button wire:click="removeUserFromRole('{{ $user['ID'] }}')" 
                                                    wire:confirm="Yakin menghapus user ini dari role?"
                                                    class="text-rose-600 hover:text-rose-800 text-sm font-medium">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center text-slate-500">
                        Tidak ada user yang terdaftar dalam role ini.
                    </div>
                @endif
            </div>
        @else
            <div class="p-12 text-center border border-dashed border-slate-300 rounded-lg text-slate-500">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                </svg>
                Silakan pilih sebuah role di atas untuk melihat dan mengelola user-nya.
            </div>
        @endif

        {{-- Modal: Create Role --}}
        @if ($showRoleModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Create New Role</h3>
                        <button wire:click="$set('showRoleModal', false)" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form wire:submit="createRole">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Role Code <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="newRoleCode" placeholder="e.g. ROLE_HR_MANAGER" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 uppercase font-mono text-sm">
                                @error('newRoleCode') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Role Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="newRoleName" placeholder="e.g. HR Manager" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('newRoleName') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                                <textarea wire:model="newRoleDesc" rows="2" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2 bg-slate-50 rounded-b-xl">
                            <button type="button" wire:click="$set('showRoleModal', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Role</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal: Assign Existing User --}}
        @if ($showAssignModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Add User to Role</h3>
                        <button wire:click="$set('showAssignModal', false)" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form wire:submit="assignExistingUser">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Select Existing User <span class="text-rose-500">*</span></label>
                                <select wire:model="assignUserId" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Choose User --</option>
                                    @foreach($allUsers as $u)
                                        <option value="{{ $u['ID'] }}">{{ $u['USERNAME'] }} ({{ $u['FULL_NAME'] }})</option>
                                    @endforeach
                                </select>
                                @error('assignUserId') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="relative py-4">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-slate-200"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="bg-white px-2 text-sm text-slate-500">OR</span>
                                </div>
                            </div>

                            <button type="button" wire:click="openCreateUserModal" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-indigo-700 font-medium hover:bg-indigo-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create New User
                            </button>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2 bg-slate-50 rounded-b-xl">
                            <button type="button" wire:click="$set('showAssignModal', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Assign User</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal: Create User --}}
        @if ($showCreateUserModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Create New User</h3>
                        <button wire:click="$set('showCreateUserModal', false)" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form wire:submit="createUserAndAssign">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="newUsername" placeholder="e.g. john.doe" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                @error('newUsername') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="newFullName" placeholder="e.g. John Doe" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                @error('newFullName') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" wire:model="newEmail" placeholder="e.g. john@example.com" class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                                <input type="password" wire:model="newPassword" required class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                @error('newPassword') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2 bg-slate-50 rounded-b-xl">
                            <button type="button" wire:click="$set('showCreateUserModal', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save & Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </x-studio-shell>
</div>
