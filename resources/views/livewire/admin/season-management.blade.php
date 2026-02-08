<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des saisons</h1>
        @if(!$showForm)
            <button wire:click="create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Nouvelle saison
            </button>
        @endif
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    @if($showForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $editing ? 'Modifier la saison' : 'Nouvelle saison' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début *</label>
                        <input type="date" wire:model="startDate" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('startDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin *</label>
                        <input type="date" wire:model="endDate" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('endDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date limite de modification</label>
                        <input type="date" wire:model="modificationDeadline" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de retrait des cadeaux</label>
                        <input type="date" wire:model="pickupStartDate" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse de retrait</label>
                    <textarea wire:model="pickupAddress" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white"></textarea>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <h3 class="text-md font-semibold text-gray-900 dark:text-white">Planification des créneaux</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limite de familles par créneau</label>
                        <input type="number" wire:model="familyLimitPerSlot" min="1" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('familyLimitPerSlot') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée d'un créneau (minutes)</label>
                        <input type="number" wire:model="slotDurationMinutes" min="5" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('slotDurationMinutes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <h3 class="text-md font-semibold text-gray-900 dark:text-white">Responsable</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du responsable</label>
                        <input type="text" wire:model="responsibleName" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('responsibleName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                        <input type="text" wire:model="responsiblePhone" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('responsiblePhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                        <input type="email" wire:model="responsibleEmail" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                        @error('responsibleEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <hr class="my-4 border-gray-300 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white">Plages horaires de récupération</h3>
                    <button type="button" wire:click="addPickupEntry" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm">
                        + Ajouter une plage
                    </button>
                </div>

                @foreach($pickupEntries as $index => $entry)
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end bg-gray-50 dark:bg-zinc-700 p-3 rounded-lg">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Début</label>
                            <input type="datetime-local" wire:model="pickupEntries.{{ $index }}.start_datetime" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                            @error("pickupEntries.{$index}.start_datetime") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fin</label>
                            <input type="datetime-local" wire:model="pickupEntries.{{ $index }}.end_datetime" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                            @error("pickupEntries.{$index}.end_datetime") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <button type="button" wire:click="removePickupEntry({{ $index }})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm w-full">
                                Supprimer
                            </button>
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        {{ $editing ? 'Enregistrer' : 'Créer' }}
                    </button>
                    <button type="button" wire:click="cancel" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($seasons as $season)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $season->name }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            {{ $season->start_date->format('d/m/Y') }} - {{ $season->end_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($season->isActive())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                            @elseif($season->isFuture())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">À venir</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Terminée</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="edit({{ $season->id }})" class="text-blue-600 hover:text-blue-800">Modifier</button>
                            <button wire:click="delete({{ $season->id }})" wire:confirm="Êtes-vous sûr de vouloir supprimer cette saison ?" class="text-red-600 hover:text-red-800">Supprimer</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aucune saison</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
