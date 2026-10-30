<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleAccessMap;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $roles;
    public ?string $editingUserId = null;
    public array $selectedRoles = [];

    public function mount(): void
    {
        $this->roles = Role::all();
    }

    public function editRoles(string $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $userId;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    public function saveRoles(): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $user = User::findOrFail($this->editingUserId);
        $user->syncRoles($this->selectedRoles);

        $this->editingUserId = null;
        $this->selectedRoles = [];

        session()->flash('message', 'Rôles mis à jour avec succès.');
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->selectedRoles = [];
    }

    public function render(RoleAccessMap $roleAccessMap)
    {
        return view('livewire.admin.user-management', [
            'users' => User::with('roles')->paginate(200),
            'accessRoles' => RoleAccessMap::ROLES,
            'accessPages' => $roleAccessMap->pages(),
        ]);
    }
}
