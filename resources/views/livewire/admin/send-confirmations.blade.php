<div class="space-y-6">
    <h1 class="section-title">Envoi des confirmations</h1>

    @if(session()->has('message'))
    <div class="notice-success">
        {{ session('message') }}
    </div>
    @endif
    @if(!$activeSeason)
    <div class="notice-warning"> Aucune saison n'est actuellement active.
    </div>
    @else
    @if(!$hasEnoughSlots)
    <div class="notice-error">
        ⚠️ <strong>Attention :</strong> Il n'y a pas assez de créneaux pour toutes les familles.
        Capacité totale : {{ $totalCapacity }} — Familles à planifier : {{ $familiesNeeded }}.
        Veuillez ajouter des plages horaires dans la gestion des saisons.
    </div>
    @endif
    <div class="card !p-6">
        <div class="text-center mb-6">
            <div class="text-4xl mb-4">📧</div>
            <h2 class="section-title"> {{ $familyCount }} famille(s) avec cadeaux reçus
            </h2>
            <p class="text-muted"> Envoyez un e-mail de confirmation aux familles dont les cadeaux sont arrivés.
            </p>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            @if($familyCount > 0)
            <button wire:click="sendEmails" wire:loading.attr="disabled" class="btn-primary !w-auto !py-3">
                <span wire:loading.remove wire:target="sendEmails">📤 Envoyer les e-mails</span>
                <span wire:loading wire:target="sendEmails">Envoi en cours...</span>
            </button>

            <button wire:click="showEmailPreview" class="btn-blue"> 👁️ Prévisualiser l'e-mail
            </button>
            @endif
            <button wire:click="recalculateSlots" wire:confirm="Êtes-vous sûr de vouloir recalculer tous les créneaux ? Les assignations existantes seront réinitialisées." class="btn-warning"> 🔄 Recalculer les créneaux
            </button>
        </div>
    </div>

    {{-- Email preview modal --}}
    @if($showPreview)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closePreview">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-zinc-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Prévisualisation de l'e-mail</h3>
                <button wire:click="closePreview" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"> ✕
                </button>
            </div>
            <div class="p-4"> {!! $previewHtml !!}
            </div>
        </div>
    </div>
    @endif
    @if($families->isNotEmpty())
    <div class="table-container">
        <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
            <h2 class="section-title">Liste des familles</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Famille</th>
                    <th class="table-header">Enfants</th>
                    <th class="table-header">Date de récupération</th>
                    <th class="table-header">Créneau</th>
                    <th class="table-header">Dernier e-mail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @foreach($families as $family)
                <tr>
                    <td class="table-cell">
                        <div>{{ $family['family_name'] }}</div>
                        <div class="text-sm text-muted">{{ $family['family_email'] }}</div>
                    </td>
                    <td class="table-cell">{{ $family['children_count'] }}</td>
                    <td class="table-cell-muted"> {{ $family['slot_date'] ?? '-' }}
                    </td>
                    <td class="table-cell-muted">
                        @if($family['slot_start'] && $family['slot_end'])
                        {{ $family['slot_start'] }} - {{ $family['slot_end'] }}
                        @else
                        <span class="text-orange-600 dark:text-orange-400">Non assigné</span>
                        @endif
                    </td>
                    <td class="table-cell-muted"> {{ $family['last_email'] ? \Carbon\Carbon::parse($family['last_email'])->format('d/m/Y H:i') : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif
</div>
