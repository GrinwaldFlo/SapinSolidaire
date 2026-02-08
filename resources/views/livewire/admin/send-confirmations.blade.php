<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Envoi des confirmations</h1>

    @if(session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        @if(!$hasEnoughSlots)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-200">
                    ⚠️ <strong>Attention :</strong> Il n'y a pas assez de créneaux pour toutes les familles.
                    Capacité totale : {{ $totalCapacity }} — Familles à planifier : {{ $familiesNeeded }}.
                    Veuillez ajouter des plages horaires dans la gestion des saisons.
                </p>
            </div>
        @endif

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="text-center mb-6">
                <div class="text-4xl mb-4">📧</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $familyCount }} famille(s) avec cadeaux reçus
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Envoyez un e-mail de confirmation aux familles dont les cadeaux sont arrivés.
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-4">
                @if($familyCount > 0)
                    <button wire:click="sendEmails" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-3 rounded-lg font-semibold">
                        <span wire:loading.remove wire:target="sendEmails">📤 Envoyer les e-mails</span>
                        <span wire:loading wire:target="sendEmails">Envoi en cours...</span>
                    </button>

                    <button wire:click="showEmailPreview" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                        👁️ Prévisualiser l'e-mail
                    </button>
                @endif

                <button wire:click="recalculateSlots" wire:confirm="Êtes-vous sûr de vouloir recalculer tous les créneaux ? Les assignations existantes seront réinitialisées." class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-semibold">
                    🔄 Recalculer les créneaux
                </button>
            </div>
        </div>

        {{-- Email preview modal --}}
        @if($showPreview)
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closePreview">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Prévisualisation de l'e-mail</h3>
                        <button wire:click="closePreview" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            ✕
                        </button>
                    </div>
                    <div class="p-4">
                        {!! $previewHtml !!}
                    </div>
                </div>
            </div>
        @endif

        @if($families->isNotEmpty())
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Liste des familles</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Famille</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Enfants</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date de récupération</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Créneau</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dernier e-mail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($families as $family)
                            <tr>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    <div>{{ $family['family_name'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $family['family_email'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $family['children_count'] }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $family['slot_date'] ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    @if($family['slot_start'] && $family['slot_end'])
                                        {{ $family['slot_start'] }} - {{ $family['slot_end'] }}
                                    @else
                                        <span class="text-orange-600 dark:text-orange-400">Non assigné</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $family['last_email'] ? \Carbon\Carbon::parse($family['last_email'])->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
