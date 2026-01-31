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
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <div class="text-center mb-6">
                <div class="text-4xl mb-4">📧</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $receivedCount }} cadeau(x) reçu(s)
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Envoyez un e-mail de confirmation aux familles dont les cadeaux sont arrivés.
                </p>
            </div>

            @if($receivedCount > 0)
                <div class="text-center">
                    <button wire:click="sendEmails" wire:loading.attr="disabled" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-3 rounded-lg font-semibold">
                        <span wire:loading.remove>📤 Envoyer les e-mails de confirmation</span>
                        <span wire:loading>Envoi en cours...</span>
                    </button>
                </div>
            @endif
        </div>

        @if($children->isNotEmpty())
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Liste des cadeaux reçus</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prénom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cadeau</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Famille</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dernier e-mail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($children as $child)
                            <tr>
                                <td class="px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">{{ $child->code }}</td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $child->first_name }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $child->gift }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $child->giftRequest->family->email }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $child->confirmation_email_sent_at ? $child->confirmation_email_sent_at->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
