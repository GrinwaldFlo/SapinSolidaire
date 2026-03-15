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
                    <button wire:click="generatePdf" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                        📄 Générer le PDF des cartes
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
                    onclick="confirm('Êtes-vous sûr de vouloir réinitialiser toutes les cartes imprimées vers le statut « Validé » ? Cette action peut annuler du travail déjà effectué.') || event.stopImmediatePropagation()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                    🔄 Réinitialiser
                </button>
            </div>
        </div>
    @endif
</div>
