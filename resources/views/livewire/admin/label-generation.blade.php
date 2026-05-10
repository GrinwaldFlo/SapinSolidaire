<div class="space-y-6">
    <h1 class="section-title">Génération des cartes</h1>

    @if(!$activeSeason)
        <div class="notice-info">
            <p>Aucune saison n'est actuellement active.</p>
        </div>
    @else
        <div class="card">
            <div class="text-center">
                <div class="text-6xl mb-4">🏷️</div>
                <h2 class="section-title">
                    {{ $validatedCount }} enfant(s) validé(s)
                </h2>
                <p class="text-muted mb-6">
                    prêt(s) pour l'impression des cartes
                </p>

                @if($validatedCount > 0)
                    <button wire:click="generatePdf" wire:loading.attr="disabled" wire:target="generatePdf" class="btn-primary inline-flex !w-auto">
                        <span wire:loading.remove wire:target="generatePdf">📄 Générer le PDF des cartes</span>
                        <span wire:loading wire:target="generatePdf">⏳ Génération en cours…</span>
                    </button>
                    <p class="text-sm text-muted mt-4">
                        Le statut des enfants passera de «Validé» à «Imprimé» après la génération.
                    </p>
                @else
                    <p class="text-muted">
                        Aucun enfant validé à imprimer.
                    </p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="text-center">
                <h3 class="section-title">
                    🔄 Réinitialiser les cartes imprimées
                </h3>
                <p class="text-muted mb-4">
                    Réinitialiser tous les enfants marqués comme "Imprimés" vers le statut "Validé"
                </p>
                <button
                    wire:click="resetPrintedLabels"
                    wire:loading.attr="disabled"
                    wire:target="resetPrintedLabels"
                    onclick="confirm('Êtes-vous sûr de vouloir réinitialiser toutes les cartes imprimées vers le statut « Validé » ? Cette action peut annuler du travail déjà effectué.') || event.stopImmediatePropagation()"
                    class="btn-blue disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="resetPrintedLabels">🔄 Réinitialiser</span>
                    <span wire:loading wire:target="resetPrintedLabels">⏳ Réinitialisation en cours…</span>
                </button>
            </div>
        </div>

        @if($generatedPdfs->count() > 0)
        <div class="card">
            <h3 class="section-title">
                📋 Historique des PDF générés
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b dark:border-zinc-700">
                        <tr>
                            <th class="table-header">Date</th>
                            <th class="table-header">Enfants</th>
                            <th class="table-header">Généré par</th>
                            <th class="table-header text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($generatedPdfs as $pdf)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                            <td class="table-cell">{{ $pdf->created_at->format('d/m/Y H:i') }}</td>
                            <td class="table-cell">{{ $pdf->children_count }}</td>
                            <td class="table-cell">{{ $pdf->user->name ?? '—' }}</td>
                            <td class="table-cell text-right">
                                <a href="{{ route('admin.labels.download', $pdf) }}" class="link font-medium">
                                    📥 Télécharger
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif
</div>
