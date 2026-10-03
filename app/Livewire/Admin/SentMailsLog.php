<?php

namespace App\Livewire\Admin;

use App\Models\SentMailLog;
use Livewire\Component;
use Livewire\WithPagination;

class SentMailsLog extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.sent-mails-log', [
            'mailLogs' => SentMailLog::query()
                ->with('sender')
                ->latest()
                ->paginate(100),
        ]);
    }
}
