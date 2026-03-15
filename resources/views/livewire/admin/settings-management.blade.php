<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Paramètres du site</h1>

    @if(session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du site *</label>
                <input type="text" wire:model="siteName" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                @error('siteName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Communes autorisées</label>
                <textarea wire:model="allowedCities" rows="3" placeholder="Lausanne, Morges, Renens" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white"></textarea>
                <p class="mt-1 text-sm text-gray-500">Séparez les communes par des virgules. Laissez vide pour autoriser toutes les communes.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre maximal d'années consécutives *</label>
                <input type="number" wire:model="maxConsecutiveYears" min="1" max="10" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                @error('maxConsecutiveYears') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Propositions de cadeaux</label>
                <textarea wire:model="giftSuggestions" rows="6" placeholder="Un cadeau par ligne" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white"></textarea>
                <p class="mt-1 text-sm text-gray-500">Un cadeau par ligne. Ces suggestions apparaîtront dans l'autocomplétion du formulaire.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Texte d'introduction</label>
                <textarea wire:model="introductionText" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white"></textarea>
                <p class="mt-1 text-sm text-gray-500">Ce texte sera affiché aux familles sur la page d'accueil.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse e-mail de réponse</label>
                <input type="email" wire:model="replyToEmail" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                @error('replyToEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Préfixe code enfant</label>
                <input type="text" wire:model="codePrefix" placeholder="Y" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500">Préfixe de région utilisé dans le code enfant (ex: Y pour le format Y0001/1).</p>
                @error('codePrefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de chiffres du numéro de famille *</label>
                <input type="number" wire:model="codeFamilyPadding" min="1" max="10" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Style des cartes PDF</label>
                <select wire:model="pdfStyle" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    <option value="label">Étiquettes (cartes individuelles)</option>
                    <option value="grid">Grille (tableau récapitulatif)</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Choisissez le format de génération du PDF dans la page Cartes.</p>
            </div>

            <div class="pt-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                    Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</div>
