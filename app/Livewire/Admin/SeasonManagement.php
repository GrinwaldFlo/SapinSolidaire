<?php

namespace App\Livewire\Admin;

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
    public ?string $pickupStartDate = null;
    public ?string $pickupAddress = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after:startDate'],
            'modificationDeadline' => ['nullable', 'date'],
            'pickupStartDate' => ['nullable', 'date'],
            'pickupAddress' => ['nullable', 'string'],
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
        $this->pickupStartDate = $season->pickup_start_date?->format('Y-m-d');
        $this->pickupAddress = $season->pickup_address;
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
            'pickup_start_date' => $this->pickupStartDate ?: null,
            'pickup_address' => $this->pickupAddress ?: null,
        ];

        if ($this->editing && $this->editingId) {
            Season::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Saison mise à jour avec succès.');
        } else {
            Season::create($data);
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
        $this->pickupStartDate = null;
        $this->pickupAddress = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.season-management');
    }
}
