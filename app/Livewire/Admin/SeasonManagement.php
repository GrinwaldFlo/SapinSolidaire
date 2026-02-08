<?php

namespace App\Livewire\Admin;

use App\Models\PickupSlot;
use App\Models\Season;
use App\Services\SeasonService;
use Livewire\Component;

class SeasonManagement extends Component
{
    public $seasons;
    public bool $showForm = false;
    public bool $editing = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $startDate = '';
    public string $endDate = '';
    public ?string $modificationDeadline = null;
    public ?string $pickupAddress = null;
    public ?int $familyLimitPerSlot = null;
    public ?int $slotDurationMinutes = null;
    public ?string $responsibleName = null;
    public ?string $responsiblePhone = null;
    public ?string $responsibleEmail = null;

    // Pickup slot entries
    public array $pickupEntries = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after:startDate'],
            'modificationDeadline' => ['nullable', 'date'],
            'pickupAddress' => ['nullable', 'string'],
            'familyLimitPerSlot' => ['nullable', 'integer', 'min:1'],
            'slotDurationMinutes' => ['nullable', 'integer', 'min:5'],
            'responsibleName' => ['nullable', 'string', 'max:255'],
            'responsiblePhone' => ['nullable', 'string', 'max:20'],
            'responsibleEmail' => ['nullable', 'email', 'max:255'],
            'pickupEntries.*.start_datetime' => ['required', 'date'],
            'pickupEntries.*.end_datetime' => ['required', 'date', 'after:pickupEntries.*.start_datetime'],
        ];
    }

    public function mount(): void
    {
        $this->loadSeasons();
    }

    protected function loadSeasons(): void
    {
        $this->seasons = Season::orderByDesc('start_date')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editing = false;
    }

    public function edit(int $id): void
    {
        $season = Season::findOrFail($id);
        $this->editingId = $id;
        $this->name = $season->name;
        $this->startDate = $season->start_date->format('Y-m-d');
        $this->endDate = $season->end_date->format('Y-m-d');
        $this->modificationDeadline = $season->modification_deadline?->format('Y-m-d');
        $this->pickupAddress = $season->pickup_address;
        $this->familyLimitPerSlot = $season->family_limit_per_slot;
        $this->slotDurationMinutes = $season->slot_duration_minutes;
        $this->responsibleName = $season->responsible_name;
        $this->responsiblePhone = $season->responsible_phone;
        $this->responsibleEmail = $season->responsible_email;

        $this->pickupEntries = $season->pickupSlots()
            ->orderBy('start_datetime')
            ->get()
            ->map(fn (PickupSlot $slot) => [
                'id' => $slot->id,
                'start_datetime' => $slot->start_datetime->format('Y-m-d\TH:i'),
                'end_datetime' => $slot->end_datetime->format('Y-m-d\TH:i'),
            ])
            ->toArray();

        $this->showForm = true;
        $this->editing = true;
    }

    public function save(): void
    {
        $this->validate();

        $seasonService = app(SeasonService::class);

        // Check for overlaps
        if ($seasonService->hasOverlap($this->startDate, $this->endDate, $this->editingId)) {
            $this->addError('startDate', 'Les dates chevauchent une saison existante.');

            return;
        }

        $data = [
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'modification_deadline' => $this->modificationDeadline ?: null,
            'pickup_address' => $this->pickupAddress ?: null,
            'family_limit_per_slot' => $this->familyLimitPerSlot ?: null,
            'slot_duration_minutes' => $this->slotDurationMinutes ?: null,
            'responsible_name' => $this->responsibleName ?: null,
            'responsible_phone' => $this->responsiblePhone ?: null,
            'responsible_email' => $this->responsibleEmail ?: null,
        ];

        if ($this->editing && $this->editingId) {
            $season = Season::findOrFail($this->editingId);
            $season->update($data);
            $this->syncPickupEntries($season);
            session()->flash('message', 'Saison mise à jour avec succès.');
        } else {
            $season = Season::create($data);
            $this->syncPickupEntries($season);
            session()->flash('message', 'Saison créée avec succès.');
        }

        $this->resetForm();
        $this->loadSeasons();
    }

    public function delete(int $id): void
    {
        $season = Season::findOrFail($id);

        // Check if season has requests
        if ($season->giftRequests()->count() > 0) {
            session()->flash('error', 'Cette saison contient des demandes et ne peut pas être supprimée.');

            return;
        }

        $season->delete();
        session()->flash('message', 'Saison supprimée avec succès.');
        $this->loadSeasons();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editing = false;
        $this->editingId = null;
        $this->name = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->modificationDeadline = null;
        $this->pickupAddress = null;
        $this->familyLimitPerSlot = null;
        $this->slotDurationMinutes = null;
        $this->responsibleName = null;
        $this->responsiblePhone = null;
        $this->responsibleEmail = null;
        $this->pickupEntries = [];
        $this->resetErrorBag();
    }

    public function addPickupEntry(): void
    {
        $this->pickupEntries[] = [
            'id' => null,
            'start_datetime' => '',
            'end_datetime' => '',
        ];
    }

    public function removePickupEntry(int $index): void
    {
        unset($this->pickupEntries[$index]);
        $this->pickupEntries = array_values($this->pickupEntries);
    }

    protected function syncPickupEntries(Season $season): void
    {
        $existingIds = $season->pickupSlots()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($this->pickupEntries as $entry) {
            if (! empty($entry['start_datetime']) && ! empty($entry['end_datetime'])) {
                if (! empty($entry['id'])) {
                    $slot = PickupSlot::find($entry['id']);
                    if ($slot && $slot->season_id === $season->id) {
                        $slot->update([
                            'start_datetime' => $entry['start_datetime'],
                            'end_datetime' => $entry['end_datetime'],
                        ]);
                        $keepIds[] = $slot->id;
                    }
                } else {
                    $slot = $season->pickupSlots()->create([
                        'start_datetime' => $entry['start_datetime'],
                        'end_datetime' => $entry['end_datetime'],
                    ]);
                    $keepIds[] = $slot->id;
                }
            }
        }

        // Delete entries that were removed
        $toDelete = array_diff($existingIds, $keepIds);
        if (! empty($toDelete)) {
            PickupSlot::whereIn('id', $toDelete)->delete();
        }
    }

    public function render()
    {
        return view('livewire.admin.season-management');
    }
}
