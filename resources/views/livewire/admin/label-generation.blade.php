<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Génération des cartes</h1>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="text-center">
                <div class="text-6xl mb-4">🏷️</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $validatedCount }} enfant(s) validé(s)
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    prêt(s) pour l'impression des cartes
                </p>

                @if($validatedCount > 0)
                    <button wire:click="generatePdf" wire:loading.attr="disabled" wire:target="generatePdf" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold">
                        <span wire:loading.remove wire:target="generatePdf">📄 Générer le PDF des cartes</span>
                        <span wire:loading wire:target="generatePdf">⏳ Génération en cours…</span>
                    </button>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                        Le statut des enfants passera de "Validé" à "Imprimé" après la génération.
                    </p>
                @else
                    <p class="text-gray-500 dark:text-gray-400">
                        Aucun enfant validé à imprimer.
                    </p>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    🔄 Réinitialiser les cartes imprimées
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Réinitialiser tous les enfants marqués comme "Imprimés" vers le statut "Validé"
                </p>
                <button
                    wire:click="resetPrintedLabels"
                    wire:loading.attr="disabled"
                    wire:target="resetPrintedLabels"
                    onclick="confirm('Êtes-vous sûr de vouloir réinitialiser toutes les cartes imprimées vers le statut « Validé » ? Cette action peut annuler du travail déjà effectué.') || event.stopImmediatePropagation()"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold">
                    <span wire:loading.remove wire:target="resetPrintedLabels">🔄 Réinitialiser</span>
                    <span wire:loading wire:target="resetPrintedLabels">⏳ Réinitialisation en cours…</span>
                </button>
            </div>
        </div>

        @if($generatedPdfs->count() > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📋 Historique des PDF générés
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Enfants</th>
                            <th class="px-4 py-3">Généré par</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($generatedPdfs as $pdf)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $pdf->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $pdf->children_count }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $pdf->user->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.labels.download', $pdf) }}" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-medium">
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
