<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="section-title">Tableau de bord</h1>
    </div>

    @if(!$activeSeason)
    <div class="notice-warning"> Aucune saison n'est actuellement active.
    </div>
    @else
    <div class="notice-success">
        <strong>Saison active :</strong> {{ $activeSeason->name }}
        ({{ $activeSeason->start_date->format('d/m/Y') }} - {{ $activeSeason->end_date->format('d/m/Y') }})
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="label-title">Familles</div>
            <div class="label-value">{{ $totalFamilies }}</div>
        </div>

        <div class="stat-card">
            <div class="label-title">Enfants</div>
            <div class="label-value">{{ $totalChildren }}</div>
        </div>

        <div class="stat-card">
            <div class="label-title">Familles en attente</div>
            <div class="label-value--warning">{{ $pendingFamilies }}</div>
        </div>

        <div class="stat-card">
            <div class="label-title">Enfants en attente</div>
            <div class="label-value--warning">{{ $pendingChildren }}</div>
        </div>
    </div>

    <div class="stat-card">
        <h2 class="section-title">Statut des cadeaux</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <div class="text-center">
                <div class="label-value--info">{{ $validatedChildren }}</div>
                <div class="label-title">Validés</div>
            </div>
            <div class="text-center">
                <div class="label-value--purple">{{ $printedChildren }}</div>
                <div class="label-title">Imprimés</div>
            </div>
            <div class="text-center">
                <div class="label-value--yellow">{{ $receivedChildren }}</div>
                <div class="label-title">Reçus</div>
            </div>
            <div class="text-center">
                <div class="label-value--success">{{ $givenChildren }}</div>
                <div class="label-title">Donnés</div>
            </div>
        </div>
    </div>
    @endif
    <div class="card-footer">
        Un bug à signaler ou une fonctionnalité à proposer ?
        <a href="https://github.com/GrinwaldFlo/SapinSolidaire/issues" target="_blank" rel="noopener noreferrer" class="link">Ouvrir un ticket sur GitHub</a>
    </div>
</div>
