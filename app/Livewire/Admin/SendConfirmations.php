<?php

namespace App\Livewire\Admin;

use App\Mail\GiftReceivedMail;
use App\Models\Child;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Services\SlotAssignmentService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SendConfirmations extends Component
{
    public ?Season $activeSeason = null;
    public int $familyCount = 0;
    public bool $sending = false;
    public bool $hasEnoughSlots = true;
    public int $totalCapacity = 0;
    public int $familiesNeeded = 0;
    public ?string $previewHtml = null;
    public bool $showPreview = false;

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
        $this->autoAssignSlots();
        $this->loadData();
    }

    protected function loadData(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $service = app(SlotAssignmentService::class);
        $summary = $service->getSummary($this->activeSeason);

        $this->familyCount = $this->getReceivedFamiliesQuery()->count();
        $this->totalCapacity = $summary['total_capacity'];
        $this->familiesNeeded = $summary['families_needed'];
        $this->hasEnoughSlots = $summary['has_enough'];
    }

    protected function autoAssignSlots(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $service = app(SlotAssignmentService::class);
        $service->assignUnassigned($this->activeSeason);
    }

    public function recalculateSlots(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $service = app(SlotAssignmentService::class);
        $count = $service->recalculateAll($this->activeSeason);

        $this->loadData();
        session()->flash('message', "Créneaux recalculés : {$count} famille(s) assignée(s).");
    }

    public function sendEmails(): void
    {
        if (! $this->activeSeason || $this->familyCount === 0) {
            return;
        }

        $this->sending = true;

        $requests = $this->getReceivedFamiliesQuery()
            ->with(['family', 'children' => function ($q) {
                $q->where('status', Child::STATUS_RECEIVED);
            }])
            ->get();

        $sent = 0;
        foreach ($requests as $request) {
            $familyEmail = $request->family->email;
            Mail::to($familyEmail)->queue(new GiftReceivedMail($request, $this->activeSeason));

            // Update confirmation timestamp on all received children
            $request->children()
                ->where('status', Child::STATUS_RECEIVED)
                ->update(['confirmation_email_sent_at' => now()]);

            $sent++;
        }

        $this->sending = false;
        $this->loadData();
        session()->flash('message', "{$sent} e-mail(s) envoyé(s) avec succès.");
    }

    public function showEmailPreview(): void
    {
        if (! $this->activeSeason) {
            return;
        }

        $request = $this->getReceivedFamiliesQuery()
            ->with(['family', 'children' => function ($q) {
                $q->where('status', Child::STATUS_RECEIVED);
            }])
            ->first();

        if (! $request) {
            $this->previewHtml = '<p>Aucune famille à prévisualiser.</p>';
            $this->showPreview = true;

            return;
        }

        $mail = new GiftReceivedMail($request, $this->activeSeason);
        $this->previewHtml = $mail->render();
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewHtml = null;
    }

    /**
     * Get gift requests with received children for the active season.
     */
    protected function getReceivedFamiliesQuery()
    {
        return GiftRequest::where('season_id', $this->activeSeason->id)
            ->whereHas('children', function ($q) {
                $q->where('status', Child::STATUS_RECEIVED);
            });
    }

    public function render()
    {
        $families = collect();

        if ($this->activeSeason) {
            $families = $this->getReceivedFamiliesQuery()
                ->with(['family' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'email');
                }])
                ->withCount(['children as received_children_count' => function ($q) {
                    $q->where('status', Child::STATUS_RECEIVED);
                }])
                ->withMax(['children as last_confirmation_email' => function ($q) {
                    $q->where('status', Child::STATUS_RECEIVED);
                }], 'confirmation_email_sent_at')
                ->get()
                ->map(function (GiftRequest $request) {
                    return [
                        'family_name' => $request->family->full_name,
                        'family_email' => $request->family->email,
                        'children_count' => $request->received_children_count,
                        'slot_date' => $request->slot_start_datetime?->format('d/m/Y'),
                        'slot_start' => $request->slot_start_datetime?->format('H:i'),
                        'slot_end' => $request->slot_end_datetime?->format('H:i'),
                        'last_email' => $request->last_confirmation_email,
                    ];
                });
        }

        return view('livewire.admin.send-confirmations', [
            'families' => $families,
        ]);
    }
}
