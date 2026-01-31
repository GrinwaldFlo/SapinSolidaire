<?php

namespace App\Livewire\Admin;

use App\Mail\GiftReceivedMail;
use App\Models\Child;
use App\Models\Season;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SendConfirmations extends Component
{
    public ?Season $activeSeason = null;
    public int $receivedCount = 0;
    public bool $sending = false;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->loadCount();
    }

    protected function loadCount(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $this->receivedCount = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_RECEIVED)->count();
    }

    public function sendEmails(): void
    {
        if (! $this->activeSeason || $this->receivedCount === 0) {
            return;
        }

        $this->sending = true;

        $children = Child::with('giftRequest.family')
            ->whereHas('giftRequest', function ($q) {
                $q->where('season_id', $this->activeSeason->id);
            })
            ->where('status', Child::STATUS_RECEIVED)
            ->get();

        foreach ($children as $child) {
            $familyEmail = $child->giftRequest->family->email;
            Mail::to($familyEmail)->queue(new GiftReceivedMail($child, $this->activeSeason));

            $child->confirmation_email_sent_at = now();
            $child->save();
        }

        $this->sending = false;
        session()->flash('message', count($children).' e-mail(s) envoyé(s) avec succès.');
    }

    public function render()
    {
        $children = collect();

        if ($this->activeSeason) {
            $children = Child::with('giftRequest.family')
                ->whereHas('giftRequest', function ($q) {
                    $q->where('season_id', $this->activeSeason->id);
                })
                ->where('status', Child::STATUS_RECEIVED)
                ->orderBy('first_name')
                ->get();
        }

        return view('livewire.admin.send-confirmations', [
            'children' => $children,
        ]);
    }
}
