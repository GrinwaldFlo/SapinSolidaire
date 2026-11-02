<div class="page-stack">
    <h1 class="section-title">Suivi des inscriptions</h1>

    <div class="notice-info">
        Statistiques anonymes : seuls des totaux par étape sont conservés, sans e-mail, adresse IP ni date.
        Les familles ayant déjà une demande pour la saison ne sont pas comptées.
    </div>

    <div class="card-sm">
        <label for="funnel-season" class="field-label">Saison</label>
        <select id="funnel-season" wire:model.live="selectedSeasonId" class="field-input">
            @foreach($seasons as $season)
                <option value="{{ $season->id }}">{{ $season->name }}</option>
            @endforeach
        </select>
    </div>

    @if(!$report)
        <div class="notice-warning">Aucune saison disponible.</div>
    @elseif($report['started'] === 0)
        <div class="notice-info">Aucune donnée pour cette saison.</div>
    @else
        <div class="stat-grid">
            <div class="stat-card">
                <div class="label-title">Parcours commencés</div>
                <div class="label-value">{{ $report['started'] }}</div>
            </div>

            <div class="stat-card">
                <div class="label-title">Demandes enregistrées</div>
                <div class="label-value">{{ $report['submitted'] }} ({{ $report['completionPercent'] }} %)</div>
            </div>

            <div class="stat-card">
                <div class="label-title">Principal point d'arrêt</div>
                @if($report['biggestStop'])
                    <div class="label-value--warning">{{ $report['biggestStop']['stopped'] }}</div>
                    <div class="field-help">après « {{ $report['biggestStop']['label'] }} »</div>
                @else
                    <div class="label-value">—</div>
                @endif
            </div>
        </div>

        <div class="table-container">
            <table class="table-full">
                <thead class="table-head">
                    <tr>
                        <th class="table-header">Étape atteinte</th>
                        <th class="table-header-center">Parcours</th>
                        <th class="table-header">Part des parcours</th>
                        <th class="table-header-center">Arrêts après cette étape</th>
                    </tr>
                </thead>
                <tbody class="table-divider">
                    @foreach($report['steps'] as $step)
                        <tr>
                            <td class="table-cell">{{ $step['label'] }}</td>
                            <td class="table-cell-center">{{ $step['count'] }}</td>
                            <td class="table-cell">
                                <progress class="stat-bar" value="{{ $step['percent'] }}" max="100" aria-label="{{ $step['percent'] }} %"></progress>
                                <div class="field-help">{{ $step['percent'] }} %</div>
                            </td>
                            <td class="table-cell-center">{{ $step['stopped'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="stat-card">
            <h2 class="section-title">Bloquées à l'ouverture du lien</h2>
            <p class="field-help">Ces familles n'ont pas abandonné : une règle les a arrêtées. Elles ne sont pas incluses dans les arrêts après « Lien ouvert ».</p>
            <div class="stat-grid stat-grid--spaced">
                @foreach($report['blocked'] as $blocked)
                    <div class="stat-item">
                        <div class="label-value--warning">{{ $blocked['count'] }}</div>
                        <div class="label-title">{{ $blocked['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="field-help">
            Une famille qui revient après l'expiration de son lien (48 h) compte comme un nouveau parcours.
            « Lien ouvert » peut être légèrement surévalué, certains services de messagerie ouvrant les liens automatiquement.
        </p>
    @endif
</div>
