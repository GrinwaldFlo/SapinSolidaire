<div>
    @if(!$tokenValid)
        <div class="card text-center">
            <span class="text-6xl mb-4 block">⚠️</span>
            <h2 class="section-title">Lien invalide ou expiré</h2>
            <p class="notice-error">
                Ce lien n'est plus valide. Les liens expirent après 48 heures.
            </p>
             <a href="/" class="btn-primary">
                Retourner à l'accueil
            </a>
        </div>
    @elseif($submitted)
        <div class="card text-center">
            <span class="text-6xl mb-4 block">✅</span>
            <h2 class="section-title">Demande {{ $isModifying ? 'modifiée' : 'enregistrée' }} !</h2>
            <p class="notice-success">
                Votre demande a bien été {{ $isModifying ? 'mise à jour' : 'enregistrée' }}.
            </p>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                Nous examinerons votre demande et vous contacterons par e-mail.
            </p>
        </div>
    @elseif(!$canModify)
        <div class="card text-center">
            <span class="text-6xl mb-4 block">🔒</span>
            <h2 class="section-title">Modification impossible</h2>
            <p class="notice-warning">
                La date limite de modification est dépassée. Vous ne pouvez plus modifier votre demande.
            </p>

            @if($giftRequest)
                <div class="mt-6 text-left bg-gray-50 dark:bg-zinc-700 rounded-lg p-6">
                    <h3 class="section-title">Résumé de votre demande</h3>
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
        <div class="card">
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
                        <button wire:click="acceptConsecutiveYears" class="btn-confirm">
                            Je confirme
                        </button>
                    </div>
                @else
                    <div class="notice-success">
                        <div class="flex items-center gap-2">
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
                            <select wire:model="selectedCity" class="field-input mb-4">
                                <option value="">-- Sélectionnez votre commune --</option>
                                @foreach($allowedCities as $allowedCity)
                                    <option value="{{ $allowedCity }}">{{ $allowedCity }}</option>
                                @endforeach
                            </select>
                            @error('selectedCity') <p class="field-error mt-1">{{ $message }}</p> @enderror
                        @else
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                <span class="italic">Toutes les communes sont acceptées</span>
                            </p>
                        @endif
                        <button wire:click="acceptCity" class="btn-confirm">
                            Je confirme
                        </button>
                    </div>
                @elseif($cityAccepted)
                    <div class="notice-success">
                        <div class="flex items-center gap-2">
                            <span>✓</span>
                            <span class="font-semibold">Condition sur la zone géographique acceptée</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Main form --}}
        <div class="card">
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
                    <h3 class="section-title">Informations de la famille</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="field-label">Prénom *</label>
                            <input type="text" id="firstName" wire:model="firstName" wire:blur="validateFamilyFields"
                                autocomplete="given-name"
                                class="{{ isset($fieldErrors['firstName']) && in_array('firstName', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                            @if(isset($fieldErrors['firstName']) && in_array('firstName', $touchedFields))
                                <p class="field-error">{{ collect($fieldErrors['firstName'])->first() }}</p>
                            @elseif($errors->has('firstName'))
                                <p class="field-error">{{ $errors->first('firstName') }}</p>
                            @endif
                        </div>

                        <div>
                            <label for="lastName" class="field-label">Nom *</label>
                            <input type="text" id="lastName" wire:model="lastName" wire:blur="validateFamilyFields"
                                autocomplete="family-name"
                                class="{{ isset($fieldErrors['lastName']) && in_array('lastName', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                            @if(isset($fieldErrors['lastName']) && in_array('lastName', $touchedFields))
                                <p class="field-error">{{ collect($fieldErrors['lastName'])->first() }}</p>
                            @elseif($errors->has('lastName'))
                                <p class="field-error">{{ $errors->first('lastName') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="streetName" class="field-label">Rue *</label>
                            <input type="text" id="streetName" wire:model="streetName" wire:blur="validateAddress"
                                autocomplete="address-line1"
                                class="{{ isset($fieldErrors['address']) && in_array('streetName', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                        </div>

                        <div>
                            <label for="houseNo" class="field-label">N° *</label>
                            <input type="text" id="houseNo" wire:model="houseNo" wire:blur="validateAddress"
                                autocomplete="address-line2"
                                class="{{ isset($fieldErrors['address']) && in_array('houseNo', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="postalCode" class="field-label">Code postal *</label>
                            <input type="text" id="postalCode" wire:model="postalCode" wire:blur="validateAddress"
                                autocomplete="postal-code"
                                class="{{ isset($fieldErrors['address']) && in_array('postalCode', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                        </div>

                        <div>
                            <label for="city" class="field-label">Ville *</label>
                            @if(!empty($allowedCities))
                                <div class="flex items-center gap-2">
                                    <select
                                        id="city"
                                        wire:model="city"
                                            wire:blur="validateCity"
                                            wire:change="requestCityChange"
                                        class="{{ isset($fieldErrors['city']) && in_array('city', $touchedFields) ? 'field-input-error' : 'field-input' }}"
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
                                @if(isset($fieldErrors['city']) && in_array('city', $touchedFields))
                                    <p class="field-error">{{ collect($fieldErrors['city'])->first() }}</p>
                                @endif
                            @else
                                <input type="text" id="city" wire:model="city" wire:blur.debounce="validateAddress" autocomplete="address-level2" class="field-input">
                                @error('city') <p class="field-error">{{ $message }}</p> @enderror
                            @endif
                        </div>
                    </div>

                    @if(isset($fieldErrors['address']) && (in_array('streetName', $touchedFields) || in_array('houseNo', $touchedFields) || in_array('postalCode', $touchedFields)))
                        <p class="field-error">{{ collect($fieldErrors['address'])->first() }}</p>
                    @endif

                    <div>
                        <label for="phone" class="field-label">Téléphone *</label>
                        <input type="tel" id="phone" wire:model="phone" wire:blur="validatePhone" placeholder="079 123 45 67"
                            autocomplete="tel"
                            class="{{ isset($fieldErrors['phone']) && in_array('phone', $touchedFields) ? 'field-input-error' : 'field-input' }}">
                        @if(isset($fieldErrors['phone']) && in_array('phone', $touchedFields))
                            <p class="field-error">{{ collect($fieldErrors['phone'])->first() }}</p>
                        @elseif($errors->has('phone'))
                            <p class="field-error">{{ $errors->first('phone') }}</p>
                        @endif
                    </div>
                </div>

                @if($proofOfHabitationEnabled)
                    {{-- Proof of habitation --}}
                    <div class="space-y-4">
                        <h3 class="section-title">Justificatif de domicile</h3>

                        <div class="notice-info">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                📎 Veuillez télécharger une photo de justificatif de domicile (facture de téléphone, courrier, etc.)
                            </p>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-2">
                                ℹ️ Formats acceptés : image (jpg, png, webp) ou PDF, maximum 10 Mo. Ce justificatif sera supprimé en fin de saison et ne sera utilisé que pour vérifier votre adresse.
                            </p>
                        </div>

                        @if($existingProofPath)
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
                                <span>✓</span>
                                <span>Un justificatif a déjà été envoyé. Vous pouvez en envoyer un nouveau pour le remplacer.</span>
                            </div>
                        @endif

                        <div>
                            <label for="proofOfHabitation" class="field-label">
                                Justificatif (image ou PDF) {{ $existingProofPath ? '' : '*' }}
                            </label>
                            <input
                                type="file"
                                id="proofOfHabitation"
                                wire:model="proofOfHabitation"
                                accept="image/*,.pdf,application/pdf"
                                class="{{ isset($fieldErrors['proofOfHabitation']) && in_array('proofOfHabitation', $touchedFields) ? 'field-input-error' : 'field-input' }} file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                            >
                            <div wire:loading wire:target="proofOfHabitation" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Téléchargement en cours...
                            </div>
                            @if(isset($fieldErrors['proofOfHabitation']) && in_array('proofOfHabitation', $touchedFields))
                                <p class="field-error">{{ collect($fieldErrors['proofOfHabitation'])->first() }}</p>
                            @elseif($errors->has('proofOfHabitation'))
                                <p class="field-error">{{ $errors->first('proofOfHabitation') }}</p>
                            @endif
                        </div>

                        @if($proofOfHabitation)
                            <div class="mt-2">
                                @if(str_starts_with((string) $proofOfHabitation->getMimeType(), 'image/'))
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Aperçu :</p>
                                    <img src="{{ $proofOfHabitation->temporaryUrl() }}" alt="Aperçu du justificatif" class="max-w-xs max-h-48 rounded-lg border border-gray-200 dark:border-zinc-600">
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Fichier sélectionné : {{ $proofOfHabitation->getClientOriginalName() }}</p>
                                @endif
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

                    <div class="mt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="isAnonymous" wire:model="isAnonymous" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:border-zinc-600 dark:bg-zinc-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Demande anonyme (les prénoms des enfants ne seront pas affichés sur les étiquettes des cadeaux)
                            </span>
                        </label>
                        <p class="mt-1 ml-6 text-xs text-gray-500 dark:text-gray-400">
                            Si coché, les personnes qui achèteront les cadeaux ne verront pas les prénoms des enfants de la famille.
                        </p>
                    </div>

                    @foreach($children as $index => $child)
                        @php
                            $borderColorClass = 'border-gray-200 dark:border-zinc-600';
                            $bgColorClass = '';

                            if (isset($child['status'])) {
                                if ($child['status'] === 'rejected') {
                                    $borderColorClass = 'border-red-300 dark:border-red-700';
                                    $bgColorClass = 'bg-red-50/50 dark:bg-red-900/10';
                                } elseif ($child['status'] === 'validated') {
                                    $borderColorClass = 'border-green-300 dark:border-green-700';
                                }
                            }

                            if (!($child['can_modify'] ?? true)) {
                                $bgColorClass = 'bg-gray-50 dark:bg-zinc-700/50';
                            }
                        @endphp
                        <div class="border {{ $borderColorClass }} rounded-lg p-4 {{ $bgColorClass }}">
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
                                    @php
                                        $statusLabel = match($child['status'] ?? '') {
                                            'pending' => 'À valider',
                                            'validated' => 'Validé',
                                            'rejected' => 'Refusé',
                                            'rejected_final' => 'Refusé définitivement',
                                            'printed' => 'Imprimé',
                                            'received' => 'Reçu',
                                            'given' => 'Donné',
                                            default => $child['status'] ?? ''
                                        };
                                    @endphp
                                    <span class="text-sm text-orange-600 dark:text-orange-400">Non modifiable ({{ $statusLabel }})</span>
                                @elseif(isset($child['status']))
                                    @if($child['status'] === 'rejected')
                                        <span class="text-sm font-semibold text-red-600 dark:text-red-400 px-2 py-1 bg-red-100 dark:bg-red-900/30 rounded-md">
                                            À corriger
                                        </span>
                                    @elseif($child['status'] === 'validated')
                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400 px-2 py-1 bg-green-100 dark:bg-green-900/30 rounded-md flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            Validé
                                        </span>
                                    @endif
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Prénom *</label>
                                    @php $childKey = "children.{$index}.first_name"; @endphp
                                    <input type="text" wire:model="children.{{ $index }}.first_name" wire:blur="validateChild({{ $index }})"
                                        class="{{ (isset($fieldErrors[$childKey]) && in_array("children.{$index}.first_name", $touchedFields)) || $errors->has($childKey) ? 'field-input-error' : 'field-input' }}"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @if(isset($fieldErrors[$childKey]) && in_array("children.{$index}.first_name", $touchedFields))
                                        <p class="field-error">{{ collect($fieldErrors[$childKey])->first() }}</p>
                                    @elseif($errors->has($childKey))
                                        <p class="field-error">{{ $errors->first($childKey) }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="field-label">Genre *</label>
                                    @php $genderKey = "children.{$index}.gender"; @endphp
                                    <select wire:model="children.{{ $index }}.gender" wire:blur="validateChild({{ $index }})"
                                        class="{{ isset($fieldErrors[$genderKey]) && in_array("children.{$index}.gender", $touchedFields) ? 'field-input-error' : 'field-input' }}"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                        <option value=""></option>
                                        <option value="boy">Garçon</option>
                                        <option value="girl">Fille</option>
                                        <option value="unspecified">Non précisé</option>
                                    </select>
                                    @if(isset($fieldErrors[$genderKey]) && in_array("children.{$index}.gender", $touchedFields))
                                        <p class="field-error">{{ collect($fieldErrors[$genderKey])->first() }}</p>
                                    @elseif($errors->has($genderKey))
                                        <p class="field-error">{{ $errors->first($genderKey) }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="field-label">Année de naissance *</label>
                                    @php $birthYearKey = "children.{$index}.birth_year"; @endphp
                                    <input type="number" wire:model="children.{{ $index }}.birth_year" wire:blur="validateChild({{ $index }})"
                                        min="{{ date('Y') - $maxChildAge }}" max="{{ date('Y') }}"
                                        class="{{ (isset($fieldErrors[$birthYearKey]) && in_array("children.{$index}.birth_year", $touchedFields)) || $errors->has($birthYearKey) ? 'field-input-error' : 'field-input' }}"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Année minimale : <strong>{{ date('Y') - $maxChildAge }}</strong> — les enfants doivent avoir au maximum {{ $maxChildAge }} ans au 31.12.{{ date('Y') }}.
                                    </p>
                                    @if(isset($fieldErrors[$birthYearKey]) && in_array("children.{$index}.birth_year", $touchedFields))
                                        <p class="field-error">{{ collect($fieldErrors[$birthYearKey])->first() }}</p>
                                    @elseif($errors->has($birthYearKey))
                                        <p class="field-error">{{ $errors->first($birthYearKey) }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="field-label">Taille (cm)</label>
                                    <input type="number" wire:model="children.{{ $index }}.height" min="50" max="200"
                                        class="field-input"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                </div>

                                <div>
                                    <label class="field-label">Cadeau souhaité *</label>
                                    @php $giftKey = "children.{$index}.gift"; @endphp
                                    <input type="text" wire:model="children.{{ $index }}.gift" wire:blur="validateChild({{ $index }})"
                                        list="gift-suggestions"
                                        class="{{ (isset($fieldErrors[$giftKey]) && in_array("children.{$index}.gift", $touchedFields)) || $errors->has($giftKey) ? 'field-input-error' : 'field-input' }}"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @if(isset($fieldErrors[$giftKey]) && in_array("children.{$index}.gift", $touchedFields))
                                        <p class="field-error">{{ collect($fieldErrors[$giftKey])->first() }}</p>
                                    @elseif($errors->has($giftKey))
                                        <p class="field-error">{{ $errors->first($giftKey) }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="field-label">Pointure (si chaussures)</label>
                                    @php $shoeSizeKey = "children.{$index}.shoe_size"; @endphp
                                    <input type="text" wire:model="children.{{ $index }}.shoe_size" wire:blur="validateChild({{ $index }})"
                                        class="{{ isset($fieldErrors[$shoeSizeKey]) && in_array("children.{$index}.shoe_size", $touchedFields) ? 'field-input-error' : 'field-input' }}"
                                        {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                                    @if(isset($fieldErrors[$shoeSizeKey]) && in_array("children.{$index}.shoe_size", $touchedFields))
                                        <p class="field-error">{{ collect($fieldErrors[$shoeSizeKey])->first() }}</p>
                                    @elseif($errors->has($shoeSizeKey))
                                        <p class="field-error">{{ $errors->first($shoeSizeKey) }}</p>
                                    @endif
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
                        class="btn-primary {{ $submitDisabled ? '!bg-gray-400 dark:!bg-gray-600 cursor-not-allowed' : '' }}"
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

                    @if($errors->any())
                        <div class="mt-4 rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4">
                            <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">
                                Veuillez corriger les erreurs suivantes :
                            </p>
                            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($hasAttemptedSubmit && !empty($fieldErrors))
                        <div class="mt-4 rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4 flex items-start gap-3">
                            <span class="text-red-500 dark:text-red-400 text-lg leading-none mt-0.5">⚠️</span>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                Le formulaire contient des erreurs. Veuillez les corriger avant de soumettre votre demande.
                            </p>
                        </div>
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
                    <button wire:click="cancelCityChange" class="btn-secondary flex-1">Annuler</button>
                    <button wire:click="confirmCity" class="btn-confirm flex-1">Je confirme</button>
                </div>
            </div>
        </div>
    @endif
</div>

