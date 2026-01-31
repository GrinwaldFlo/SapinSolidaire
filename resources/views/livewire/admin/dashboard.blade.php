<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de bord</h1>
    </div>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">
                <strong>Saison active :</strong> {{ $activeSeason->name }}
                ({{ $activeSeason->start_date->format('d/m/Y') }} - {{ $activeSeason->end_date->format('d/m/Y') }})
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 dark:text-gray-400">Familles</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalFamilies }}</div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 dark:text-gray-400">Enfants</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalChildren }}</div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 dark:text-gray-400">Familles en attente</div>
                <div class="text-3xl font-bold text-orange-600">{{ $pendingFamilies }}</div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-500 dark:text-gray-400">Enfants en attente</div>
                <div class="text-3xl font-bold text-orange-600">{{ $pendingChildren }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statut des cadeaux</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $validatedChildren }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Validés</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $printedChildren }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Imprimés</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $receivedChildren }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Reçus</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $givenChildren }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Donnés</div>
                </div>
            </div>
        </div>
    @endif
</div>
