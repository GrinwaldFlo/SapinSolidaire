<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="section-title text-2xl !border-0 !mb-0">🛠️ Outils développeur</h1>
    </div>

    @if($flashMessage)
    <div class="{{ $flashType === 'success' ? 'notice-success' : 'notice-error' }}">
        <p>{{ $flashMessage }}</p>
    </div>
    @endif
    @if(!$activeSeason)
    <div class="notice-warning"> Aucune saison n'est actuellement active.
    </div>
    @else
    <div class="notice-info">
        <strong>Saison active :</strong> {{ $activeSeason->name }}
        ({{ $activeSeason->start_date->format('d/m/Y') }} - {{ $activeSeason->end_date->format('d/m/Y') }})
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Familles</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['totalFamilies'] ?? 0 }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Familles en attente</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pendingFamilies'] ?? 0 }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Enfants</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['totalChildren'] ?? 0 }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Enfants en attente</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pendingChildren'] ?? 0 }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Enfants imprimés</div>
            <div class="text-2xl font-bold text-purple-600">{{ $stats['printedChildren'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Family Access Link --}}
    <div class="card !p-6">
        <h2 class="section-title text-lg !border-0 !mb-4">Récupérer un lien de connexion famille</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"> Génère un lien d'accès identique à celui envoyé par e-mail depuis la page d'accueil.
        </p>

        <form wire:submit="generateFamilyAccessLink" class="space-y-4">
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="familyEmail" class="field-label">E-mail de la famille</label>
                    <input type="email" id="familyEmail" wire:model="familyEmail"
                        class="field-input"
                        placeholder="exemple@email.com" />
                    @error('familyEmail')
                    <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm">
                    <span wire:loading.remove wire:target="generateFamilyAccessLink">🔗 Générer le lien</span>
                    <span wire:loading wire:target="generateFamilyAccessLink">Génération...</span>
                </button>
            </div>
        </form>

        @if($familyAccessLink)
        <div class="mt-4 border border-indigo-200 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/20 p-3">
            <p class="text-sm text-indigo-800 dark:text-indigo-200 mb-2">Lien de connexion :</p>
            <a href="{{ $familyAccessLink }}" target="_blank" rel="noopener"
                class="text-sm text-indigo-700 dark:text-indigo-300 break-all hover:underline"> {{ $familyAccessLink }}
            </a>
        </div>
        @endif
    </div>

    {{-- Seed Families --}}
    <div class="card !p-6">
        <h2 class="section-title text-lg !border-0 !mb-4">Générer des familles de test</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"> Crée des familles fictives avec 1 à 5 enfants chacune. Certaines peuvent être des familles existantes qui reviennent pour cette saison.
        </p>
        <div class="flex items-end gap-4">
            <div>
                <label for="familyCount" class="field-label">Nombre de familles</label>
                <input type="number" id="familyCount" wire:model="familyCount" min="1" max="50"
                    class="w-32 field-input" />
                @error('familyCount')
                <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
            <button wire:click="seedFamilies" wire:loading.attr="disabled"
                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm">
                <span wire:loading.remove wire:target="seedFamilies">🌱 Générer</span>
                <span wire:loading wire:target="seedFamilies">Génération...</span>
            </button>
        </div>
    </div>

    {{-- Batch Validate --}}
    <div class="card !p-6">
        <h2 class="section-title text-lg !border-0 !mb-4">Validation en masse</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"> Valide toutes les familles et enfants en attente pour la saison active.
        </p>
        <button wire:click="batchValidate" wire:loading.attr="disabled"
            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm"
            wire:confirm="Voulez-vous vraiment valider toutes les familles et enfants en attente ?">
            <span wire:loading.remove wire:target="batchValidate">✓ Tout valider</span>
            <span wire:loading wire:target="batchValidate">Validation...</span>
        </button>
    </div>

    {{-- Batch Receive --}}
    <div class="card !p-6">
        <h2 class="section-title text-lg !border-0 !mb-4">Réception en masse</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"> Marque tous les cadeaux imprimés comme reçus pour la saison active.
        </p>
        <button wire:click="batchReceive" wire:loading.attr="disabled"
            class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm"
            wire:confirm="Voulez-vous vraiment marquer tous les cadeaux imprimés comme reçus ?">
            <span wire:loading.remove wire:target="batchReceive">📦 Tout marquer reçu</span>
            <span wire:loading wire:target="batchReceive">Réception...</span>
        </button>
    </div>
    @endif
</div>
