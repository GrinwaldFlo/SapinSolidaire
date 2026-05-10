<div class="space-y-6">
    <h1 class="section-title text-2xl !border-0 !mb-0">Paramètres du site</h1>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="card !p-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <label class="field-label">Nom du site *</label>
                <input type="text" wire:model="siteName" class="field-input">
                @error('siteName') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Communes autorisées</label>
                <textarea wire:model="allowedCities" rows="3" placeholder="Lausanne, Morges, Renens" class="field-input"></textarea>
                <p class="mt-1 text-sm text-gray-500">Séparez les communes par des virgules. Laissez vide pour autoriser toutes les communes.</p>
                @error('allowedCities') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Nombre maximal d'années consécutives *</label>
                <input type="number" wire:model="maxConsecutiveYears" min="1" max="10" class="field-input">
                @error('maxConsecutiveYears') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Âge maximum des enfants (inclus) *</label>
                <input type="number" wire:model.live="maxChildAge" min="1" max="25" class="field-input">
                @php $minBirthYear = date('Y') - $maxChildAge; @endphp
                <p class="mt-1 text-sm text-gray-500">
                    Année de naissance minimale acceptée : <strong>{{ $minBirthYear }}</strong>
                    — Un enfant né en {{ $minBirthYear }} aura exactement {{ $maxChildAge }} ans au 31.12.{{ date('Y') }} ({{ date('Y') }} − {{ $maxChildAge }} = {{ $minBirthYear }}).
                </p>
                @error('maxChildAge') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Propositions de cadeaux</label>
                <textarea wire:model="giftSuggestions" rows="6" placeholder="Un cadeau par ligne" class="field-input"></textarea>
                <p class="mt-1 text-sm text-gray-500">Un cadeau par ligne. Ces suggestions apparaîtront dans l'autocomplétion du formulaire.</p>
            </div>

            <div>
                <label class="field-label">Restrictions de cadeaux (cadeaux interdits)</label>
                <textarea wire:model="giftRestrictions" rows="4" placeholder="Un mot-clé par ligne" class="field-input"></textarea>
                <p class="mt-1 text-sm text-gray-500">Un mot-clé par ligne. Si le cadeau demandé contient un de ces mots, il sera refusé.</p>
            </div>

            <div>
                <label class="field-label">Texte d'introduction</label>
                <textarea wire:model="introductionText" rows="4" class="field-input"></textarea>
                <p class="mt-1 text-sm text-gray-500">Ce texte sera affiché aux familles sur la page d'accueil.</p>
            </div>

            <div>
                <label class="field-label">Adresse e-mail de réponse</label>
                <input type="email" wire:model="replyToEmail" class="field-input">
                @error('replyToEmail') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Préfixe code enfant</label>
                <input type="text" wire:model="codePrefix" placeholder="Y" class="field-input">
                <p class="mt-1 text-sm text-gray-500">Préfixe de région utilisé dans le code enfant (ex: Y pour le format Y0001/1).</p>
                @error('codePrefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label">Nombre de chiffres du numéro de famille *</label>
                <input type="number" wire:model="codeFamilyPadding" min="1" max="10" class="field-input">
                <p class="mt-1 text-sm text-gray-500">Nombre de chiffres pour le numéro de famille dans le code (ex: 4 donne Y0001/1).</p>
                @error('codeFamilyPadding') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="proofOfHabitationEnabled" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 dark:border-zinc-600 dark:bg-zinc-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activer le justificatif de domicile</span>
                </label>
                <p class="mt-1 text-sm text-gray-500">Si activé, les familles devront télécharger une photo de justificatif de domicile (facture de téléphone, courrier, etc.) lors de leur inscription.</p>
            </div>

            <div>
                <label class="field-label">Style des cartes PDF</label>
                <select wire:model="pdfStyle" class="field-input">
                    <option value="label">Étiquettes (cartes individuelles)</option>
                    <option value="grid">Grille (tableau récapitulatif)</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Choisissez le format de génération du PDF dans la page Cartes.</p>
            </div>

            <div class="pt-4">
                <button type="submit" wire:loading.attr="disabled" class="btn-confirm">
                    <span wire:loading.remove wire:target="save">Enregistrer les paramètres</span>
                    <span wire:loading wire:target="save">Enregistrement...</span>
                </button>
                @error('allowedCities') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </form>
    </div>
</div>
