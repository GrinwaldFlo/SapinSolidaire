<?php

namespace App\Livewire\Admin;

use App\Models\FamilySubmissionLog;
use Livewire\Component;
use Livewire\WithPagination;

class FamilySubmissionLogs extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.family-submission-logs', [
            'submissionLogs' => FamilySubmissionLog::query()
                ->with('family')
                ->latest()
                ->paginate(100),
        ]);
    }
}
