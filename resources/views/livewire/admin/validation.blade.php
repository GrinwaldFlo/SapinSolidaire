<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Validation des demandes</h1>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ $pendingFamiliesCount }} famille(s) · {{ $pendingChildrenCount }} enfant(s) en attente
        </div>
    </div>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @elseif(!$currentRequest)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">🎉 Toutes les demandes ont été traitées !</p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de la famille</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Nom :</span>
                        <span class="ml-2 text-gray-900 dark:text-white">{{ $currentRequest->family->first_name }} {{ $currentRequest->family->last_name }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Email :</span>
                        <span class="ml-2 text-gray-900 dark:text-white">{{ $currentRequest->family->email }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Téléphone :</span>
                        <span class="ml-2 text-gray-900 dark:text-white">{{ $currentRequest->family->phone }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Adresse :</span>
                        <span class="ml-2 text-gray-900 dark:text-white">{{ $currentRequest->family->full_address }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Statut famille :</span>
                    @if($currentRequest->status === 'pending')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">À valider</span>
                    @elseif($currentRequest->status === 'validated')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Validé</span>
                    @endif
                </div>

                @if($currentRequest->status === 'pending')
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="validateFamily" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                            ✓ Valider la famille
                        </button>
                        <button wire:click="openRejectionModal('family', {{ $currentRequest->id }}, false)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm">
                            Demander correction
                        </button>
                        <button wire:click="openRejectionModal('family', {{ $currentRequest->id }}, true)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                            Refuser définitivement
                        </button>
                    </div>
                @endif
            </div>

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enfants ({{ $currentRequest->children->count() }})</h2>

            <div class="space-y-4">
                @foreach($currentRequest->children as $child)
                    <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Prénom :</span>
                                <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $child->first_name }}</span>
                                @if($child->anonymous)
                                    <span class="ml-2 text-xs text-orange-600 dark:text-orange-400">(Anonyme)</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Genre :</span>
                                <span class="ml-2 text-gray-900 dark:text-white">{{ $child->gender_label }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Âge :</span>
                                <span class="ml-2 text-gray-900 dark:text-white">{{ $child->age }} ans ({{ $child->birth_year }})</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Taille :</span>
                                <span class="ml-2 text-gray-900 dark:text-white">{{ $child->height ? $child->height . ' cm' : '-' }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Cadeau :</span>
                                <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $child->gift }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Pointure :</span>
                                <span class="ml-2 text-gray-900 dark:text-white">{{ $child->shoe_size ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Code :</span>
                                <span class="ml-2 text-gray-900 dark:text-white font-mono font-bold">{{ $child->code }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $child->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ $child->status_label }}
                            </span>

                            @if($child->status === 'pending')
                                <div class="flex gap-2">
                                    <button wire:click="validateChild({{ $child->id }})" class="text-green-600 hover:text-green-800 text-sm">
                                        ✓ Valider
                                    </button>
                                    <button wire:click="openRejectionModal('child', {{ $child->id }}, false)" class="text-yellow-600 hover:text-yellow-800 text-sm">
                                        Demander correction
                                    </button>
                                    <button wire:click="openRejectionModal('child', {{ $child->id }}, true)" class="text-red-600 hover:text-red-800 text-sm">
                                        Refuser
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rejection Modal --}}
    @if($showRejectionModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 max-w-lg w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $isFinalRejection ? 'Refus définitif' : 'Demande de correction' }}
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Commentaire / Motif *
                    </label>
                    <textarea wire:model="rejectionComment" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white" placeholder="Expliquez le motif du refus ou les corrections à apporter..."></textarea>
                    @error('rejectionComment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button wire:click="closeRejectionModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                        Annuler
                    </button>
                    <button wire:click="confirmRejection" class="{{ $isFinalRejection ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white px-4 py-2 rounded-lg">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
