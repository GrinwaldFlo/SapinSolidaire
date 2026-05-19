<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="section-title">🛠️ Outils développeur</h1>
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
        <div class="stat-card">
            <div class="label-title">Familles</div>
            <div class="label-value">{{ $stats['totalFamilies'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="label-title">Familles en attente</div>
            <div class="label-value--warning">{{ $stats['pendingFamilies'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="label-title">Enfants</div>
            <div class="label-value">{{ $stats['totalChildren'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="label-title">Enfants en attente</div>
            <div class="label-value--warning">{{ $stats['pendingChildren'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="label-title">Enfants imprimés</div>
            <div class="label-value">{{ $stats['printedChildren'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Family Access Link --}}
    <div class="card">
        <h2 class="section-title">Récupérer un lien de connexion famille</h2>
        <p class="text-muted mb-4"> Génère un lien d'accès identique à celui envoyé par e-mail depuis la page d'accueil.
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
                <button type="submit" wire:loading.attr="disabled" class="btn-blue disabled:opacity-50">
                    <span wire:loading.remove wire:target="generateFamilyAccessLink">🔗 Générer le lien</span>
                    <span wire:loading wire:target="generateFamilyAccessLink">Génération...</span>
                </button>
            </div>
        </form>

        @if($familyAccessLink)
        <div class="notice-info mt-4">
            <p class="text-sm mb-2">Lien de connexion :</p>
            <a href="{{ $familyAccessLink }}" target="_blank" rel="noopener" class="link text-sm break-all">
                {{ $familyAccessLink }}
            </a>
        </div>
        @endif
    </div>

    {{-- Seed Families --}}
    <div class="card">
        <h2 class="section-title">Générer des familles de test</h2>
        <p class="text-muted mb-4"> Crée des familles fictives avec 1 à 5 enfants chacune. Certaines peuvent être des familles existantes qui reviennent pour cette saison.
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
            <button wire:click="seedFamilies" wire:loading.attr="disabled" class="btn-blue disabled:opacity-50">
                <span wire:loading.remove wire:target="seedFamilies">🌱 Générer</span>
                <span wire:loading wire:target="seedFamilies">Génération...</span>
            </button>
        </div>
    </div>

    {{-- Batch Validate --}}
    <div class="card">
        <h2 class="section-title">Validation en masse</h2>
        <p class="text-muted mb-4"> Valide toutes les familles et enfants en attente pour la saison active.
        </p>
        <button wire:click="batchValidate" wire:loading.attr="disabled" class="btn-primary disabled:opacity-50"
            wire:confirm="Voulez-vous vraiment valider toutes les familles et enfants en attente ?">
            <span wire:loading.remove wire:target="batchValidate">✓ Tout valider</span>
            <span wire:loading wire:target="batchValidate">Validation...</span>
        </button>
    </div>

    {{-- Batch Receive --}}
    <div class="card">
        <h2 class="section-title">Réception en masse</h2>
        <p class="text-muted mb-4"> Marque tous les cadeaux imprimés comme reçus pour la saison active.
        </p>
        <button wire:click="batchReceive" wire:loading.attr="disabled" class="btn-blue disabled:opacity-50"
            wire:confirm="Voulez-vous vraiment marquer tous les cadeaux imprimés comme reçus ?">
            <span wire:loading.remove wire:target="batchReceive">📦 Tout marquer reçu</span>
            <span wire:loading wire:target="batchReceive">Réception...</span>
        </button>
    </div>
    {{-- Danger Zone --}}
    <div class="card border-2 border-red-500 dark:border-red-600">
        <h2 class="section-title text-red-600 dark:text-red-400">⚠️ Zone de danger</h2>
        <p class="text-muted mb-4">
            Supprime <strong>toutes</strong> les familles, enfants, demandes de cadeaux, créneaux, jetons e-mail, PDFs générés et fichiers associés (justificatifs, PDFs). Les utilisateurs et paramètres sont conservés.
        </p>
        <button wire:click="nukeDangerZone" wire:loading.attr="disabled" class="btn-danger disabled:opacity-50"
            wire:confirm="⚠️ ATTENTION : Cette action est irréversible. Toutes les familles, enfants et fichiers seront définitivement supprimés. Continuer ?">
            <span wire:loading.remove wire:target="nukeDangerZone">🗑️ Tout supprimer</span>
            <span wire:loading wire:target="nukeDangerZone">Suppression...</span>
        </button>
    </div>
    @endif
</div>
