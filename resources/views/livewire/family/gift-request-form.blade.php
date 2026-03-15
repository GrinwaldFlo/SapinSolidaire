<div>
    @if(!$tokenValid)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8 text-center">
            <span class="text-6xl mb-4 block">⚠️</span>
            <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-4">Lien invalide ou expiré</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Ce lien n'est plus valide. Les liens expirent après 48 heures.
            </p>
            <a href="/" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                Retourner à l'accueil
            </a>
        </div>
    @elseif($submitted)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8 text-center">
            <span class="text-6xl mb-4 block">✅</span>
            <h2 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-4">Demande {{ $isModifying ? 'modifiée' : 'enregistrée' }} !</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Votre demande a bien été {{ $isModifying ? 'mise à jour' : 'enregistrée' }}.
            </p>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Nous examinerons votre demande et vous contacterons par e-mail.
            </p>
        </div>
    @elseif(!$canModify)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8 text-center">
            <span class="text-6xl mb-4 block">🔒</span>
            <h2 class="text-2xl font-bold text-orange-600 dark:text-orange-400 mb-4">Modification impossible</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                La date limite de modification est dépassée. Vous ne pouvez plus modifier votre demande.
            </p>
            
            @if($giftRequest)
                <div class="mt-6 text-left bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Résumé de votre demande</h3>
                    <p><strong>Famille :</strong> {{ $firstName }} {{ $lastName }}</p>
                    <p><strong>Enfants :</strong></p>
                    <ul class="list-disc list-inside mt-2">
                        @foreach($children as $child)
                            <li>{{ $child['first_name'] }} - {{ $child['gift'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @elseif($step === 1)
        {{-- Eligibility checks --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <span class="text-6xl mb-4 block">📋</span>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Conditions d'éligibilité</h2>
                <p class="text-gray-600 dark:text-gray-300">
                    Veuillez confirmer que vous remplissez les conditions suivantes.
                </p>
            </div>

            <div class="space-y-6">
                @if(!$consecutiveYearsAccepted)
                    <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-white mb-2">Nombre d'années consécutives</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Je confirme ne pas avoir demandé de cadeau plus de {{ $maxConsecutiveYears - 1 }} années consécutives.
                        </p>
                        <button
                            wire:click="acceptConsecutiveYears"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                        >
                            Je confirme
                        </button>
                    </div>
                @else
                    <div class="border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <span>✓</span>
                            <span class="font-semibold">Condition sur les années consécutives acceptée</span>
                        </div>
                    </div>
                @endif

                @if($consecutiveYearsAccepted && !$cityAccepted)
                    <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-white mb-2">Zone géographique</h3>
                        @if(!empty($allowedCities))
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Je confirme habiter dans la commune :
                            </p>
                            <select
                                wire:model="selectedCity"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white mb-4"
                            >
                                <option value="">-- Sélectionnez votre commune --</option>
                                @foreach($allowedCities as $allowedCity)
                                    <option value="{{ $allowedCity }}">{{ $allowedCity }}</option>
                                @endforeach
                            </select>
                            @error('selectedCity') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        @else
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                <span class="italic">Toutes les communes sont acceptées</span>
                            </p>
                        @endif
                        <button
                            wire:click="acceptCity"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                        >
                            Je confirme
                        </button>
                    </div>
                @elseif($cityAccepted)
                    <div class="border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <span>✓</span>
                            <span class="font-semibold">Condition sur la zone géographique acceptée</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Main form --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <span class="text-6xl mb-4 block">🎁</span>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
                    {{ $isModifying ? 'Modifier votre demande' : 'Formulaire de demande' }}
                </h2>
                @if($isModifying)
                    <p class="text-orange-600 dark:text-orange-400 text-sm">
                        Vous consultez et modifiez une demande existante.
                    </p>
                @endif
            </div>

            <form wire:submit="submit" class="space-y-8">
                {{-- Email display --}}
                <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Adresse e-mail</label>
                    <p class="text-gray-800 dark:text-white font-medium">{{ $email }}</p>
                </div>

                {{-- Family information --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">
                        Informations de la famille
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom *</label>
                            <input type="text" id="firstName" wire:model="firstName" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                            @error('firstName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                            <input type="text" id="lastName" wire:model="lastName" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                            @error('lastName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse *</label>
                        <input type="text" id="address" wire:model="address" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                        @error('address') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="postalCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code postal *</label>
                            <input type="text" id="postalCode" wire:model="postalCode" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                            @error('postalCode') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ville *</label>
                            @if(!empty($allowedCities))
                                <div class="flex items-center gap-2">
                                    <select
                                        id="city"
                                        wire:model="city"
                                        wire:change="requestCityChange"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white"
                                    >
                                        <option value="">-- Sélectionnez votre commune --</option>
                                        @foreach($allowedCities as $allowedCity)
                                            <option value="{{ $allowedCity }}">{{ $allowedCity }}</option>
                                        @endforeach
                                    </select>
                                    @if($cityConfirmed)
                                        <span class="text-green-600 dark:text-green-400 text-lg" title="Commune confirmée">✓</span>
                                    @endif
                                </div>
                            @else
                                <input type="text" id="city" wire:model="city" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                            @endif
                            @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone *</label>
                        <input type="tel" id="phone" wire:model="phone" placeholder="079 123 45 67" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                        @error('phone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if($proofOfHabitationEnabled)
                    {{-- Proof of habitation --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-zinc-600 pb-2">
                            Justificatif de domicile
                        </h3>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                📷 Veuillez prendre en photo un courrier récent indiquant votre adresse (facture de téléphone, facture d'électricité, courrier officiel, etc.).
                            </p>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-2">
                                ℹ️ Ce justificatif sera supprimé en fin de saison et ne sera utilisé que pour vérifier votre adresse.
                            </p>
                        </div>

                        @if($existingProofPath)
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
                                <span>✓</span>
                                <span>Un justificatif a déjà été envoyé. Vous pouvez en envoyer un nouveau pour le remplacer.</span>
                            </div>
                        @endif

                        <div>
                            <label for="proofOfHabitation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Photo du justificatif {{ $existingProofPath ? '' : '*' }}
                            </label>
                            <input
                                type="file"
                                id="proofOfHabitation"
                                wire:model="proofOfHabitation"
                                accept="image/*"
                                capture="environment"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                            >
                            <div wire:loading wire:target="proofOfHabitation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Téléchargement en cours...
                            </div>
                            @error('proofOfHabitation') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        @if($proofOfHabitation)
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Aperçu :</p>
                                <img src="{{ $proofOfHabitation->temporaryUrl() }}" alt="Aperçu du justificatif" class="max-w-xs max-h-48 rounded-lg border border-gray-200 dark:border-zinc-600">
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Children --}}
                <div class="space-y-4">
                    <div class="border-b border-gray-200 dark:border-zinc-600 pb-2">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Enfants ({{ count($children) }})
                        </h3>
                    </div>

                    @foreach($children as $index => $child)
                        <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-4 {{ !($child['can_modify'] ?? true) ? 'bg-gray-50 dark:bg-zinc-700/50' : '' }}">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-gray-800 dark:text-white">Enfant {{ $index + 1 }}</h4>
                                    @if(($child['can_modify'] ?? true) && count($children) > 1)
                                        <button type="button" wire:click="removeChild({{ $index }})" wire:confirm="Êtes-vous sûr de vouloir supprimer cet enfant ?" class="text-red-400 hover:text-red-600 transition duration-200" title="Supprimer cet enfant">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                @if(!($child['can_modify'] ?? true))
                                    <span class="text-sm text-orange-600 dark:text-orange-400">Non modifiable ({{ $child['status'] ?? '' }})</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prénom *</label>
                                    <input type="text" wire:model="children.{{ $index }}.first_name" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @error("children.{$index}.first_name") <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Genre *</label>
                                    <select wire:model="children.{{ $index }}.gender" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                        <option value="unspecified">Non précisé</option>
                                        <option value="boy">Garçon</option>
                                        <option value="girl">Fille</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="children.{{ $index }}.anonymous" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:border-zinc-600 dark:bg-zinc-700" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            Anonyme (le prénom ne sera pas affiché sur l'étiquette du cadeau)
                                        </span>
                                    </label>
                                    <p class="mt-1 ml-6 text-xs text-gray-500 dark:text-gray-400">
                                        Si coché, la personne qui achètera le cadeau ne verra pas le prénom de l'enfant.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année de naissance *</label>
                                    <input type="number" wire:model="children.{{ $index }}.birth_year" min="2000" max="{{ date('Y') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @error("children.{$index}.birth_year") <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taille (cm)</label>
                                    <input type="number" wire:model="children.{{ $index }}.height" min="50" max="200" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cadeau souhaité *</label>
                                    <input type="text" wire:model="children.{{ $index }}.gift" list="gift-suggestions" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @error("children.{$index}.gift") <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pointure (si chaussures)</label>
                                    <input type="text" wire:model="children.{{ $index }}.shoe_size" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @error("children.{$index}.shoe_size") <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" wire:click="addChild" class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-zinc-600 rounded-lg text-green-600 hover:text-green-700 hover:border-green-300 dark:hover:border-green-700 font-medium text-sm flex items-center justify-center gap-1 transition duration-200">
                        <span>+</span> Ajouter un enfant
                    </button>
                </div>

                <datalist id="gift-suggestions">
                    @foreach($giftSuggestions as $suggestion)
                        <option value="{{ $suggestion }}">
                    @endforeach
                </datalist>

                {{-- Submit --}}
                <div class="pt-4">
                    @php
                        $submitDisabled = !empty($allowedCities) && !$cityConfirmed;
                    @endphp
                    <button
                        type="submit"
                        class="w-full font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2 {{ $submitDisabled ? 'bg-gray-400 dark:bg-gray-600 text-white cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white' }}"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        {{ $submitDisabled ? 'disabled' : '' }}
                    >
                        <span wire:loading.remove>{{ $isModifying ? 'Enregistrer les modifications' : 'Envoyer ma demande' }}</span>
                        <span wire:loading>Enregistrement...</span>
                    </button>
                    @if($submitDisabled)
                        <p class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">
                            Veuillez sélectionner et confirmer votre commune de résidence pour pouvoir envoyer votre demande.
                        </p>
                    @endif
                </div>
            </form>
        </div>
    @endif

    {{-- City confirmation modal --}}
    @if($showCityConfirmation)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="text-center mb-4">
                    <span class="text-4xl mb-2 block">📍</span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Confirmation de résidence</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                    Confirmez-vous habiter dans la commune de
                    <strong class="text-gray-800 dark:text-white">{{ $city }}</strong> ?
                    <br><br>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Il est obligatoire de résider dans une commune éligible pour pouvoir faire une demande de cadeau.
                    </span>
                </p>
                <div class="flex gap-3">
                    <button
                        wire:click="cancelCityChange"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition duration-200"
                    >
                        Annuler
                    </button>
                    <button
                        wire:click="confirmCity"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-200"
                    >
                        Je confirme
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
