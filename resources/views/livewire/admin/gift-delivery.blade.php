<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Remise des cadeaux</h1>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Children list --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Cadeaux à remettre ({{ $children->total() }})</h2>
                </div>
                
                @if($children->isEmpty())
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        Aucun cadeau en attente de remise.
                    </div>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($children as $child)
                            <li wire:click="selectChild({{ $child->id }})" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer {{ $selectedChild?->id === $child->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $child->first_name }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $child->age }} ans)</span>
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $child->giftRequest->family->last_name }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4">
                        {{ $children->links() }}
                    </div>
                @endif
            </div>

            {{-- Detail panel --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Détails</h2>
                </div>

                @if($selectedChild)
                    <div class="p-6 space-y-4">
                        <div class="text-center mb-6">
                            <div class="text-4xl font-mono font-bold text-green-600">{{ $selectedChild->code }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Prénom</span>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->first_name }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Âge</span>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->age }} ans</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Famille</span>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->giftRequest->family->last_name }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Cadeau</span>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $selectedChild->gift }}</p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-200 dark:border-zinc-700">
                            <button wire:click="markAsGiven({{ $selectedChild->id }})" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold">
                                🎁 Cadeau remis
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        Sélectionnez un enfant dans la liste.
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
