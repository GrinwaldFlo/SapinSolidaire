<?php

namespace App\Livewire\Admin;

use App\Models\AdminActionLog;
use Livewire\Component;
use Livewire\WithPagination;

class AdminActionLogs extends Component
{
    use WithPagination;

    public string $actionTypeFilter = '';
    public string $userSearch = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatedActionTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AdminActionLog::query()
            ->with(['user', 'family', 'child']);

        if ($this->actionTypeFilter !== '') {
            $query->where('action_type', $this->actionTypeFilter);
        }

        if ($this->userSearch !== '') {
            $search = '%'.trim($this->userSearch).'%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('user_label', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search);
                    });
            });
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return view('livewire.admin.admin-action-logs', [
            'actionLogs' => $query
                ->latest()
                ->paginate(100),
            'actionTypeOptions' => [
                AdminActionLog::ACTION_FAMILY_VALIDATED => 'Validation famille',
                AdminActionLog::ACTION_CHILD_VALIDATED => 'Validation enfant',
                AdminActionLog::ACTION_FAMILY_STATUS_RESET => 'Réinitialisation statut famille',
                AdminActionLog::ACTION_EMAIL_SENT => 'Envoi e-mail',
            ],
        ]);
    }
}
