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

                @if($consecutiveYearsAccepted && !$postalCodeAccepted)
                    <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-white mb-2">Zone géographique</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Je confirme habiter dans une des communes suivantes :
                            @if(!empty($allowedPostalCodes))
                                <span class="font-medium">{{ implode(', ', $allowedPostalCodes) }}</span>
                            @else
                                <span class="italic">Toutes les communes sont acceptées</span>
                            @endif
                        </p>
                        <button
                            wire:click="acceptPostalCode"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                        >
                            Je confirme
                        </button>
                    </div>
                @elseif($postalCodeAccepted)
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
                            <input type="text" id="city" wire:model="city" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                            @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone *</label>
                        <input type="tel" id="phone" wire:model="phone" placeholder="079 123 45 67" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-zinc-700 dark:text-white">
                        @error('phone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Children --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-600 pb-2">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Enfants ({{ count($children) }})
                        </h3>
                        <button type="button" wire:click="addChild" class="text-green-600 hover:text-green-700 font-medium text-sm flex items-center gap-1">
                            <span>+</span> Ajouter un enfant
                        </button>
                    </div>

                    @foreach($children as $index => $child)
                        <div class="border border-gray-200 dark:border-zinc-600 rounded-lg p-4 {{ !($child['can_modify'] ?? true) ? 'bg-gray-50 dark:bg-zinc-700/50' : '' }}">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-medium text-gray-800 dark:text-white">Enfant {{ $index + 1 }}</h4>
                                @if(!($child['can_modify'] ?? true))
                                    <span class="text-sm text-orange-600 dark:text-orange-400">Non modifiable ({{ $child['status'] ?? '' }})</span>
                                @elseif(count($children) > 1)
                                    <button type="button" wire:click="removeChild({{ $index }})" class="text-red-600 hover:text-red-700 text-sm">
                                        Supprimer
                                    </button>
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
                </div>

                <datalist id="gift-suggestions">
                    @foreach($giftSuggestions as $suggestion)
                        <option value="{{ $suggestion }}">
                    @endforeach
                </datalist>

                {{-- Submit --}}
                <div class="pt-4">
                    <button
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                    >
                        <span wire:loading.remove>{{ $isModifying ? 'Enregistrer les modifications' : 'Envoyer ma demande' }}</span>
                        <span wire:loading>Enregistrement...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
