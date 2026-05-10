<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="section-title">Gestion des saisons</h1>
        @if(!$showForm)
            <button wire:click="create" class="btn-confirm">
                Nouvelle saison
            </button>
        @endif
    </div>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="notice-error">
            {{ session('error') }}
        </div>
    @endif

    @if($showForm)
        <div class="card">
            <h2 class="section-title">
                {{ $editing ? 'Modifier la saison' : 'Nouvelle saison' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="field-label">Nom *</label>
                    <input type="text" wire:model="name" class="field-input">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Date de début *</label>
                        <input type="date" wire:model="startDate" class="field-input">
                        @error('startDate') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Date de fin *</label>
                        <input type="date" wire:model="endDate" class="field-input">
                        @error('endDate') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="field-label">Date limite d'inscription et modification</label>
                    <input type="date" wire:model="modificationDeadline" class="field-input">
                </div>

                <div>
                    <label class="field-label">Adresse de retrait</label>
                    <textarea wire:model="pickupAddress" rows="3" class="field-input"></textarea>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <h3 class="section-title">Planification des créneaux</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Limite de familles par créneau</label>
                        <input type="number" wire:model="familyLimitPerSlot" min="1" class="field-input">
                        @error('familyLimitPerSlot') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Durée d'un créneau (minutes)</label>
                        <input type="number" wire:model="slotDurationMinutes" min="5" class="field-input">
                        @error('slotDurationMinutes') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <h3 class="section-title">Responsable</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="field-label">Nom du responsable</label>
                        <input type="text" wire:model="responsibleName" class="field-input">
                        @error('responsibleName') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">Téléphone</label>
                        <input type="text" wire:model="responsiblePhone" class="field-input">
                        @error('responsiblePhone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label">E-mail</label>
                        <input type="email" wire:model="responsibleEmail" class="field-input">
                        @error('responsibleEmail') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <h3 class="section-title">Plages horaires de récupération</h3>
                    <button type="button" wire:click="addPickupEntry" class="btn-blue">
                        + Ajouter une plage
                    </button>
                </div>

                @foreach($pickupEntries as $index => $entry)
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end bg-gray-50 dark:bg-zinc-700 p-3 rounded-lg">
                        <div class="md:col-span-2">
                            <label class="field-label">Début</label>
                            <input type="datetime-local" wire:model="pickupEntries.{{ $index }}.start_datetime" class="field-input">
                            @error("pickupEntries.{$index}.start_datetime") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="field-label">Fin</label>
                            <input type="datetime-local" wire:model="pickupEntries.{{ $index }}.end_datetime" class="field-input">
                            @error("pickupEntries.{$index}.end_datetime") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <button type="button" wire:click="removePickupEntry({{ $index }})" class="btn-danger !px-3 !py-2 text-sm w-full">
                                Supprimer
                            </button>
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-4">
                    <button type="submit" class="btn-primary !py-3 !w-auto">
                        {{ $editing ? 'Enregistrer' : 'Créer' }}
                    </button>
                    <button type="button" wire:click="cancel" class="btn-secondary">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Nom</th>
                    <th class="table-header">Période</th>
                    <th class="table-header">Statut</th>
                    <th class="table-header text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($this->seasons as $season)
                    <tr wire:key="season-{{ $season->id }}">
                        <td class="table-cell">{{ $season->name }}</td>
                        <td class="table-cell-muted">
                            {{ $season->start_date->format('d/m/Y') }} - {{ $season->end_date->format('d/m/Y') }}
                        </td>
                        <td class="table-cell">
                            @if($season->isActive())
                                <span class="badge--validated">Active</span>
                            @elseif($season->isFuture())
                                <span class="badge--info">À venir</span>
                            @else
                                <span class="badge--neutral">Terminée</span>
                            @endif
                        </td>
                        <td class="table-cell text-right space-x-2">
                            <button wire:click="edit('{{ $season->id }}')" class="link font-medium">Modifier</button>
                            <button wire:click="delete('{{ $season->id }}')" wire:confirm="Êtes-vous sûr de vouloir supprimer cette saison ?" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">Supprimer</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="table-empty">Aucune saison</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
