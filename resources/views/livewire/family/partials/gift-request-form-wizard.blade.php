<div class="card">
    <div class="space-y-4">
        <div class="text-center">
            <span class="text-5xl block">🎁</span>
            <h2 class="section-title border-0 pb-0 mt-2">
                {{ $isModifying ? 'Modifier votre demande' : 'Demande de cadeau' }}
            </h2>
            <p class="text-muted text-sm mt-2">
                Étape {{ $familyFormCurrentStep }} sur {{ $this->getFamilyFormStepCount() }}
            </p>
        </div>

        <div class="bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
            <div
                class="bg-green-600 h-2 rounded-full"
                style="width: {{ (int) (($familyFormCurrentStep / max(1, $this->getFamilyFormStepCount())) * 100) }}%;"
            ></div>
        </div>
    </div>

    <form wire:submit="submit" class="space-y-6 mt-6">
        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-4">
            <label class="detail-label">Adresse e-mail</label>
            <p class="detail-value">{{ $email }}</p>
        </div>

        @if($this->isFamilyFormOnFamilyStep())
            <div class="space-y-4">
                <h3 class="section-title">Informations de la famille</h3>

                <div>
                    <label for="firstNameWizard" class="field-label">Prénom *</label>
                    <input
                        id="firstNameWizard"
                        type="text"
                        wire:model="firstName"
                        wire:blur="validateFamilyFields"
                        autocomplete="given-name"
                        class="{{ isset($fieldErrors['firstName']) ? 'field-input-error' : 'field-input' }}"
                    >
                    @if(isset($fieldErrors['firstName']))
                        <p class="field-error">{{ collect($fieldErrors['firstName'])->first() }}</p>
                    @endif
                </div>

                <div>
                    <label for="lastNameWizard" class="field-label">Nom *</label>
                    <input
                        id="lastNameWizard"
                        type="text"
                        wire:model="lastName"
                        wire:blur="validateFamilyFields"
                        autocomplete="family-name"
                        class="{{ isset($fieldErrors['lastName']) ? 'field-input-error' : 'field-input' }}"
                    >
                    @if(isset($fieldErrors['lastName']))
                        <p class="field-error">{{ collect($fieldErrors['lastName'])->first() }}</p>
                    @endif
                </div>

                <div>
                    <label for="streetNameWizard" class="field-label">Rue *</label>
                    <input
                        id="streetNameWizard"
                        type="text"
                        wire:model="streetName"
                        wire:blur="validateAddress"
                        autocomplete="address-line1"
                        class="{{ isset($fieldErrors['address']) ? 'field-input-error' : 'field-input' }}"
                    >
                </div>

                <div>
                    <label for="houseNoWizard" class="field-label">N° *</label>
                    <input
                        id="houseNoWizard"
                        type="text"
                        wire:model="houseNo"
                        wire:blur="validateAddress"
                        autocomplete="address-line2"
                        class="{{ isset($fieldErrors['address']) ? 'field-input-error' : 'field-input' }}"
                    >
                </div>

                <div>
                    <label for="postalCodeWizard" class="field-label">Code postal *</label>
                    <input
                        id="postalCodeWizard"
                        type="text"
                        wire:model="postalCode"
                        wire:blur="validateAddress"
                        autocomplete="postal-code"
                        class="{{ isset($fieldErrors['address']) ? 'field-input-error' : 'field-input' }}"
                    >
                </div>

                <div>
                    <label for="cityWizard" class="field-label">Ville *</label>
                    @if(!empty($allowedCities))
                        <select
                            id="cityWizard"
                            wire:model="city"
                            wire:blur="validateCity"
                            wire:change="requestCityChange"
                            class="{{ isset($fieldErrors['city']) ? 'field-input-error' : 'field-input' }}"
                        >
                            <option value="">-- Sélectionnez votre commune --</option>
                            @foreach($allowedCities as $allowedCity)
                                <option value="{{ $allowedCity }}">{{ $allowedCity }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            id="cityWizard"
                            type="text"
                            wire:model="city"
                            wire:blur.debounce="validateAddress"
                            autocomplete="address-level2"
                            class="{{ isset($fieldErrors['city']) ? 'field-input-error' : 'field-input' }}"
                        >
                    @endif
                    @if(isset($fieldErrors['city']))
                        <p class="field-error">{{ collect($fieldErrors['city'])->first() }}</p>
                    @endif
                </div>

                @if(isset($fieldErrors['address']))
                    <p class="field-error">{{ collect($fieldErrors['address'])->first() }}</p>
                @endif

                <div>
                    <label for="phoneWizard" class="field-label">Téléphone *</label>
                    <input
                        id="phoneWizard"
                        type="tel"
                        wire:model="phone"
                        wire:blur="validatePhone"
                        autocomplete="tel"
                        placeholder="079 123 45 67"
                        class="{{ isset($fieldErrors['phone']) ? 'field-input-error' : 'field-input' }}"
                    >
                    @if(isset($fieldErrors['phone']))
                        <p class="field-error">{{ collect($fieldErrors['phone'])->first() }}</p>
                    @endif
                </div>
            </div>
        @elseif($this->isFamilyFormOnProofStep())
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
                    <div class="notice-success">
                        Un justificatif existe déjà. Vous pouvez le remplacer.
                    </div>
                @endif

                <div>
                    <label for="proofOfHabitationWizard" class="field-label">
                        Justificatif {{ $existingProofPath ? '' : '*' }}
                    </label>
                    <input
                        id="proofOfHabitationWizard"
                        type="file"
                        wire:model="proofOfHabitation"
                        accept="image/*,.pdf,application/pdf"
                        class="{{ isset($fieldErrors['proofOfHabitation']) ? 'field-input-error' : 'field-input' }}"
                    >
                    @if(isset($fieldErrors['proofOfHabitation']))
                        <p class="field-error">{{ collect($fieldErrors['proofOfHabitation'])->first() }}</p>
                    @endif
                </div>
            </div>
        @elseif($this->isFamilyFormOnAnonymousStep())
            <div class="space-y-4">
                <h3 class="section-title">Demande anonyme</h3>
                <p class="text-muted">
                    Voulez-vous masquer les prénoms des enfants sur les étiquettes des cadeaux ?
                </p>
                <button type="button" wire:click="setAnonymousChoice(false)" class="btn-primary">
                    Non, afficher les prénoms
                </button>
                <button type="button" wire:click="setAnonymousChoice(true)" class="btn-secondary w-full py-3">
                    Oui, masquer les prénoms
                </button>
            </div>
        @elseif($this->isFamilyFormOnSummaryStep())
            <div class="space-y-4">
                <h3 class="section-title">Résumé</h3>
                <div class="notice-info text-sm">
                    Vérifiez les informations avant l’envoi.
                </div>

                <div class="space-y-2">
                    <p><span class="detail-label">Famille :</span> <span class="detail-value">{{ $firstName }} {{ $lastName }}</span></p>
                    <p><span class="detail-label">Adresse :</span> <span class="detail-value">{{ $streetName }} {{ $houseNo }}, {{ $postalCode }} {{ $city }}</span></p>
                    <p><span class="detail-label">Téléphone :</span> <span class="detail-value">{{ $phone }}</span></p>
                    <p><span class="detail-label">Demande anonyme :</span> <span class="detail-value">{{ $isAnonymous ? 'Oui' : 'Non' }}</span></p>
                </div>

                <div class="space-y-3">
                    <p class="detail-label">Enfants :</p>
                    <ul class="space-y-3 list-none text-sm text-gray-700 dark:text-gray-300">
                        @foreach($children as $index => $child)
                            <li class="border border-gray-200 dark:border-zinc-600 rounded-lg p-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="detail-value">{{ $child['first_name'] ?: 'Sans prénom' }}</span>
                                    @if(!empty($child['gender']))
                                        <span class="text-lg" aria-label="{{ $child['gender'] === 'boy' ? 'Garçon' : ($child['gender'] === 'girl' ? 'Fille' : 'Genre non précisé') }}">
                                            {{ $child['gender'] === 'boy' ? '♂️' : ($child['gender'] === 'girl' ? '♀️' : '⚧️') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 space-y-1 text-gray-600 dark:text-gray-300">
                                    <p><span class="detail-label">Année de naissance :</span> {{ $child['birth_year'] ?: 'Non renseignée' }}</p>
                                    <p><span class="detail-label">Cadeau :</span> {{ $child['gift'] ?: 'Cadeau non renseigné' }}</p>
                                    @if(!empty($child['height']))
                                        <p><span class="detail-label">Taille :</span> {{ $child['height'] }} cm</p>
                                    @endif
                                    @if(!empty($child['shoe_size']))
                                        <p><span class="detail-label">Pointure :</span> {{ $child['shoe_size'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            @php $currentChildIndex = $this->getFamilyFormCurrentChildIndex(); @endphp
            @if($currentChildIndex !== null)
                    @php
                        $filledPreviousChildren = collect($children)
                            ->take($currentChildIndex)
                            ->filter(fn (array $existingChild): bool => trim((string) ($existingChild['first_name'] ?? '')) !== '');
                        $child = $children[$currentChildIndex];
                        $firstNameKey = "children.{$currentChildIndex}.first_name";
                        $genderKey = "children.{$currentChildIndex}.gender";
                        $birthYearKey = "children.{$currentChildIndex}.birth_year";
                        $giftKey = "children.{$currentChildIndex}.gift";
                        $heightKey = "children.{$currentChildIndex}.height";
                        $shoeSizeKey = "children.{$currentChildIndex}.shoe_size";
                    @endphp

                <div
                    class="space-y-4"
                    wire:key="wizard-child-step-{{ $currentChildIndex }}-{{ $child['id'] ?? 'new-'.$currentChildIndex }}"
                >
                    <h3 class="section-title">Enfant {{ $currentChildIndex + 1 }}</h3>

                    @if($filledPreviousChildren->isNotEmpty())
                        <div class="space-y-2">
                            <p class="detail-label">Enfants déjà remplis</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($filledPreviousChildren as $childIndex => $existingChild)
                                    <button
                                        type="button"
                                        wire:click="goToFamilyFormChildStep({{ $childIndex }})"
                                        class="badge--validated"
                                    >
                                        {{ $existingChild['first_name'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="field-label">Prénom *</label>
                        <input type="text" wire:model="children.{{ $currentChildIndex }}.first_name" wire:blur="validateChild({{ $currentChildIndex }})" class="{{ isset($fieldErrors[$firstNameKey]) ? 'field-input-error' : 'field-input' }}" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                        @if(isset($fieldErrors[$firstNameKey]))
                            <p class="field-error">{{ collect($fieldErrors[$firstNameKey])->first() }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="field-label">Genre *</label>
                        <select wire:model="children.{{ $currentChildIndex }}.gender" wire:blur="validateChild({{ $currentChildIndex }})" class="{{ isset($fieldErrors[$genderKey]) ? 'field-input-error' : 'field-input' }}" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                            <option value=""></option>
                            <option value="boy">Garçon</option>
                            <option value="girl">Fille</option>
                            <option value="unspecified">Non précisé</option>
                        </select>
                        @if(isset($fieldErrors[$genderKey]))
                            <p class="field-error">{{ collect($fieldErrors[$genderKey])->first() }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="field-label">Année de naissance *</label>
                        <input type="number" wire:model="children.{{ $currentChildIndex }}.birth_year" wire:blur="validateChild({{ $currentChildIndex }})" min="{{ date('Y') - $maxChildAge }}" max="{{ date('Y') }}" class="{{ isset($fieldErrors[$birthYearKey]) ? 'field-input-error' : 'field-input' }}" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                        @if(isset($fieldErrors[$birthYearKey]))
                            <p class="field-error">{{ collect($fieldErrors[$birthYearKey])->first() }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="field-label">Cadeau souhaité *</label>
                        <div wire:key="wizard-child-gift-{{ $currentChildIndex }}">
                            <x-combobox
                                :suggestions="$giftSuggestions"
                                model="children.{{ $currentChildIndex }}.gift"
                                blur="validateChild({{ $currentChildIndex }})"
                                :has-error="isset($fieldErrors[$giftKey])"
                                :disabled="!($child['can_modify'] ?? true)"
                            />
                        </div>
                        @if(isset($fieldErrors[$giftKey]))
                            <p class="field-error">{{ collect($fieldErrors[$giftKey])->first() }}</p>
                        @endif
                    </div>

                    @if($this->shouldShowHeightField($currentChildIndex))
                        <div>
                            <label class="field-label">Taille (cm)</label>
                            <input type="number" wire:model="children.{{ $currentChildIndex }}.height" wire:blur="validateChild({{ $currentChildIndex }})" min="50" max="200" class="{{ isset($fieldErrors[$heightKey]) ? 'field-input-error' : 'field-input' }}" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                            @if(isset($fieldErrors[$heightKey]))
                                <p class="field-error">{{ collect($fieldErrors[$heightKey])->first() }}</p>
                            @endif
                        </div>
                    @endif

                    @if($this->shouldShowShoeSizeField($currentChildIndex))
                        <div>
                            <label class="field-label">Pointure</label>
                            <input type="text" wire:model="children.{{ $currentChildIndex }}.shoe_size" wire:blur="validateChild({{ $currentChildIndex }})" class="{{ isset($fieldErrors[$shoeSizeKey]) ? 'field-input-error' : 'field-input' }}" {{ !($child['can_modify'] ?? true) ? 'disabled' : '' }}>
                            @if(isset($fieldErrors[$shoeSizeKey]))
                                <p class="field-error">{{ collect($fieldErrors[$shoeSizeKey])->first() }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endif

        <div class="space-y-3 pt-2">
            @if($this->isFamilyFormOnSummaryStep())
                <button type="button" wire:click="goToFamilyInformationStep" class="btn-secondary w-full py-3">
                    Modifier les informations
                </button>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $isModifying ? 'Enregistrer les modifications' : 'Envoyer ma demande' }}</span>
                    <span wire:loading>Enregistrement...</span>
                </button>
            @elseif($this->isFamilyFormOnAnonymousStep())
                <button type="button" wire:click="previousFamilyFormStep" class="btn-secondary w-full py-3">
                    Retour
                </button>
            @else
                @php
                    $currentChildIndex = $this->getFamilyFormCurrentChildIndex();
                    $isLastChild = $currentChildIndex !== null && $currentChildIndex === count($children) - 1;
                    $canProceedFromChildStep = $currentChildIndex !== null && $this->isChildValidForWizardStep($currentChildIndex);
                @endphp
                @if($this->isFamilyFormOnFamilyStep())
                    <button type="button" wire:click="nextFamilyFormStep" class="btn-primary">
                        Continuer
                    </button>
                @elseif($this->isFamilyFormOnProofStep())
                    <button type="button" wire:click="nextFamilyFormStep" class="btn-primary">
                        Continuer
                    </button>
                    <button type="button" wire:click="previousFamilyFormStep" class="btn-secondary w-full py-3">
                        Retour
                    </button>
                @elseif($currentChildIndex !== null)
                    @if($isLastChild)
                        <button
                            type="button"
                            wire:click="addChildAndContinue"
                            class="btn-secondary w-full py-3 {{ !$canProceedFromChildStep ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ !$canProceedFromChildStep ? 'disabled' : '' }}
                        >
                            Ajouter un autre enfant
                        </button>
                        <button
                            type="button"
                            wire:click="nextFamilyFormStep"
                            class="btn-primary {{ !$canProceedFromChildStep ? '!bg-gray-400 dark:!bg-gray-600 cursor-not-allowed' : '' }}"
                            {{ !$canProceedFromChildStep ? 'disabled' : '' }}
                        >
                            Valider et voir le résumé
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="nextFamilyFormStep"
                            class="btn-primary {{ !$canProceedFromChildStep ? '!bg-gray-400 dark:!bg-gray-600 cursor-not-allowed' : '' }}"
                            {{ !$canProceedFromChildStep ? 'disabled' : '' }}
                        >
                            Enfant suivant
                        </button>
                    @endif
                    <button type="button" wire:click="previousFamilyFormStep" class="btn-secondary w-full py-3">
                        Retour
                    </button>
                @endif
            @endif
        </div>

        @if($hasAttemptedSubmit && !empty($fieldErrors))
            <div class="notice-error">
                Le formulaire contient des erreurs. Veuillez les corriger avant de continuer.
            </div>
        @endif
    </form>
</div>
