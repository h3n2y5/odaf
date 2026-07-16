<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class RoleUserManager extends Component
{
    use NormalizesRows;

    public array $applications = [];
    public string $selectedAppId = '';

    public array $roles = [];
    public string $selectedRoleId = '';
    
    // User Grid for selected role
    public array $roleUsers = [];

    // All existing users (for assign modal)
    public array $allUsers = [];
    
    // Modals state
    public bool $showRoleModal = false;
    public bool $showAssignModal = false;
    public bool $showCreateUserModal = false;

    // Role Form
    public string $newRoleCode = '';
    public string $newRoleName = '';
    public string $newRoleDesc = '';

    // Assign User Form
    public string $assignUserId = '';

    // Create User Form
    public string $newUsername = '';
    public string $newFullName = '';
    public string $newEmail = '';
    public string $newPassword = '';

    public function mount(StudioAccess $access): void
    {
        $access->ensureAdmin();
        
        // Auto-patch database for APPLICATION_ID if not exists
        try {
            DB::selectOne("SELECT APPLICATION_ID FROM SEC_ROLE FETCH FIRST 1 ROWS ONLY");
        } catch (\Throwable $e) {
            DB::statement("ALTER TABLE SEC_ROLE ADD APPLICATION_ID RAW(16)");
            DB::statement("ALTER TABLE SEC_ROLE ADD CONSTRAINT FK_SEC_ROLE_APP FOREIGN KEY (APPLICATION_ID) REFERENCES APP_APPLICATION(OBJECT_ID)");
        }

        $this->loadApplications();
        $this->loadRoles();
    }

    public function loadApplications(): void
    {
        $apps = DB::select("SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE, OBJECT_NAME FROM APP_APPLICATION ORDER BY OBJECT_NAME");
        $this->applications = $this->normalizeRows($apps);
    }

    public function updatedSelectedAppId(): void
    {
        $this->selectedRoleId = '';
        $this->roleUsers = [];
        $this->loadRoles();
    }

    public function loadRoles(): void
    {
        if ($this->selectedAppId !== '') {
            $roles = DB::select("
                SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE, OBJECT_NAME, DESCRIPTION 
                FROM SEC_ROLE 
                WHERE APPLICATION_ID = HEXTORAW(?) OR OBJECT_CODE = 'ADMIN'
                ORDER BY OBJECT_CODE
            ", [$this->selectedAppId]);
        } else {
            // Jika tidak ada app yg dipilih, hanya tampilkan admin atau role global lainnya.
            $roles = DB::select("
                SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE, OBJECT_NAME, DESCRIPTION 
                FROM SEC_ROLE 
                WHERE APPLICATION_ID IS NULL
                ORDER BY OBJECT_CODE
            ");
        }
        
        $this->roles = $this->normalizeRows($roles);
        
        if ($this->selectedRoleId !== '') {
            $this->loadRoleUsers();
        }
    }

    public function updatedSelectedRoleId(): void
    {
        if ($this->selectedRoleId) {
            $this->loadRoleUsers();
        } else {
            $this->roleUsers = [];
        }
    }

    private function loadRoleUsers(): void
    {
        if (!$this->selectedRoleId) return;
        $users = DB::select("
            SELECT RAWTOHEX(U.OBJECT_ID) as ID, U.OBJECT_CODE as USERNAME, U.OBJECT_NAME as FULL_NAME, U.EMAIL 
            FROM SEC_USER U
            JOIN SEC_USER_ROLE UR ON U.OBJECT_ID = UR.USER_ID
            WHERE UR.ROLE_ID = HEXTORAW(?)
            ORDER BY U.OBJECT_CODE
        ", [$this->selectedRoleId]);
        $this->roleUsers = $this->normalizeRows($users);
    }

    // Role Methods
    public function openRoleModal(): void
    {
        $this->reset(['newRoleCode', 'newRoleName', 'newRoleDesc']);
        $this->showRoleModal = true;
    }

    public function createRole(): void
    {
        $this->validate([
            'newRoleCode' => 'required|string|max:100|regex:/^[A-Z0-9_]+$/',
            'newRoleName' => 'required|string|max:200',
        ]);

        try {
            $exists = DB::selectOne("SELECT 1 FROM SEC_ROLE WHERE OBJECT_CODE = ?", [$this->newRoleCode]);
            if ($exists) throw new \Exception("Role Code sudah ada.");

            if ($this->selectedAppId) {
                DB::insert("
                    INSERT INTO SEC_ROLE (OBJECT_ID, OBJECT_CODE, OBJECT_NAME, DESCRIPTION, APPLICATION_ID, CREATED_AT, UPDATED_AT)
                    VALUES (SYS_GUID(), ?, ?, ?, HEXTORAW(?), SYSTIMESTAMP, SYSTIMESTAMP)
                ", [strtoupper($this->newRoleCode), $this->newRoleName, $this->newRoleDesc, $this->selectedAppId]);
            } else {
                DB::insert("
                    INSERT INTO SEC_ROLE (OBJECT_ID, OBJECT_CODE, OBJECT_NAME, DESCRIPTION, CREATED_AT, UPDATED_AT)
                    VALUES (SYS_GUID(), ?, ?, ?, SYSTIMESTAMP, SYSTIMESTAMP)
                ", [strtoupper($this->newRoleCode), $this->newRoleName, $this->newRoleDesc]);
            }

            session()->flash('success', 'Role berhasil dibuat.');
            $this->showRoleModal = false;
            $this->loadRoles();
            
            // auto-select new role
            $newRole = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) as ID FROM SEC_ROLE WHERE OBJECT_CODE = ?", [strtoupper($this->newRoleCode)]);
            if ($newRole) {
                $this->selectedRoleId = $this->normalizeRow($newRole)['ID'];
                $this->loadRoleUsers();
            }
        } catch (\Throwable $e) {
            $this->addError('newRoleCode', $e->getMessage());
        }
    }

    // Assign User Methods
    public function openAssignModal(): void
    {
        if (!$this->selectedRoleId) return;
        $this->reset('assignUserId');
        
        // Load users not in this role
        $users = DB::select("
            SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE as USERNAME, OBJECT_NAME as FULL_NAME 
            FROM SEC_USER 
            WHERE OBJECT_ID NOT IN (
                SELECT USER_ID FROM SEC_USER_ROLE WHERE ROLE_ID = HEXTORAW(?)
            )
            ORDER BY OBJECT_CODE
        ", [$this->selectedRoleId]);
        $this->allUsers = $this->normalizeRows($users);
        $this->showAssignModal = true;
    }

    public function assignExistingUser(): void
    {
        if (!$this->selectedRoleId || !$this->assignUserId) return;

        try {
            DB::insert("
                INSERT INTO SEC_USER_ROLE (USER_ID, ROLE_ID)
                VALUES (HEXTORAW(?), HEXTORAW(?))
            ", [$this->assignUserId, $this->selectedRoleId]);

            session()->flash('success', 'User berhasil ditambahkan ke role.');
            $this->showAssignModal = false;
            $this->loadRoleUsers();
        } catch (\Throwable $e) {
            $this->addError('assignUserId', 'Gagal menambahkan: ' . $e->getMessage());
        }
    }

    public function removeUserFromRole(string $userId): void
    {
        if (!$this->selectedRoleId) return;
        try {
            DB::delete("DELETE FROM SEC_USER_ROLE WHERE USER_ID = HEXTORAW(?) AND ROLE_ID = HEXTORAW(?)", [$userId, $this->selectedRoleId]);
            session()->flash('success', 'User berhasil dihapus dari role.');
            $this->loadRoleUsers();
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // Create User Methods
    public function openCreateUserModal(): void
    {
        $this->reset(['newUsername', 'newFullName', 'newEmail', 'newPassword']);
        $this->showAssignModal = false;
        $this->showCreateUserModal = true;
    }

    public function createUserAndAssign(): void
    {
        if (!$this->selectedRoleId) return;

        $this->validate([
            'newUsername' => 'required|string|max:50|alpha_dash',
            'newFullName' => 'required|string|max:200',
            'newPassword' => 'required|string|min:6',
        ]);

        try {
            DB::beginTransaction();
            
            $exists = DB::selectOne("SELECT 1 FROM SEC_USER WHERE OBJECT_CODE = ?", [$this->newUsername]);
            if ($exists) throw new \Exception("Username sudah terdaftar.");

            $uidObj = DB::selectOne("SELECT RAWTOHEX(SYS_GUID()) AS NEW_ID FROM DUAL");
            $uid = $this->normalizeRow($uidObj)['NEW_ID'];

            $hashed = Hash::make($this->newPassword);

            DB::insert("
                INSERT INTO SEC_USER (OBJECT_ID, OBJECT_CODE, EMAIL, OBJECT_NAME, PASSWORD_HASH, STATUS, CREATED_AT, UPDATED_AT)
                VALUES (HEXTORAW(?), ?, ?, ?, ?, 'PUBLISHED', SYSTIMESTAMP, SYSTIMESTAMP)
            ", [$uid, $this->newUsername, $this->newEmail, $this->newFullName, $hashed]);

            // Assign to role
            DB::insert("
                INSERT INTO SEC_USER_ROLE (USER_ID, ROLE_ID)
                VALUES (HEXTORAW(?), HEXTORAW(?))
            ", [$uid, $this->selectedRoleId]);

            DB::commit();

            session()->flash('success', 'User baru berhasil dibuat dan ditambahkan ke Role.');
            $this->showCreateUserModal = false;
            $this->loadRoleUsers();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addError('newUsername', 'Gagal: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.studio.designer.role-user-manager');
    }
}
