<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Remise des cadeaux</h1>

    @if(!$activeSeason)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">Aucune saison n'est actuellement active.</p>
        </div>
    @else
        {{-- Search input --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom de famille</label>
            <div class="flex items-center gap-3">
                <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    wire:model.live.debounce.300ms="searchName"
                    placeholder="Rechercher par nom…"
                    class="w-full md:w-64 rounded-lg border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white focus:ring-green-500 focus:border-green-500 text-lg"
                />
                @if($searchName !== '')
                    <button wire:click="clearFilter" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 shrink-0">
                        Effacer
                    </button>
                @endif
            </div>
        </div>

        {{-- Results --}}
        @if($searchName !== '')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Family list --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Familles
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">— {{ count($families) }} résultat(s)</span>
                        </h2>
                    </div>

                    @if(count($families) === 0)
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            Aucune famille trouvée avec des cadeaux à remettre.
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($families as $family)
                                <li wire:click="selectFamily('{{ $family->id }}')" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer {{ $selectedFamilyId === $family->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $family->last_name }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $family->first_name }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Desktop detail panel --}}
                <div class="hidden lg:block bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Détails de la famille</h2>
                    </div>

                    @if($selectedFamily && $selectedChildren->count() > 0)
                        <div class="p-6 space-y-4">
                            <div class="text-center mb-4">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $selectedFamily->last_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedChildren->count() }} enfant(s) à remettre</div>
                            </div>

                            <div class="space-y-3">
                                @foreach($selectedChildren as $child)
                                    <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <div class="space-y-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $child->first_name }}</span>
                                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $child->code }}</span>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    Né(e) en {{ $child->birth_year }} — {{ $child->gift }}
                                                </div>
                                            </div>
                                            <button wire:click="markAsGiven('{{ $child->id }}')" class="shrink-0 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                                🎁 Remis
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($selectedChildren->count() > 1)
                                <div class="pt-4 border-t border-gray-200 dark:border-zinc-700">
                                    <button wire:click="markAllAsGiven('{{ $selectedFamily->id }}')" wire:confirm="Confirmer la remise de tous les cadeaux de cette famille ?" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition">
                                        🎁 Tout remettre ({{ $selectedChildren->count() }} cadeaux)
                                    </button>
                                </div>
                            @endif
                        </div>
                    @elseif($selectedFamily)
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun cadeau à remettre pour cette famille.
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            Sélectionnez une famille dans la liste pour voir les détails.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mobile detail modal --}}
            @if($selectedFamily && $showMobileDetail)
                <div class="lg:hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center" wire:click.self="closeMobileDetail">
                    <div class="fixed inset-0 bg-black/50" wire:click="closeMobileDetail"></div>
                    <div class="relative bg-white dark:bg-zinc-800 w-full sm:max-w-md sm:rounded-lg rounded-t-2xl shadow-xl max-h-[90vh] overflow-y-auto">
                        <div class="p-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                            <h2 class="font-semibold text-gray-900 dark:text-white">{{ $selectedFamily->last_name }}</h2>
                            <button wire:click="closeMobileDetail" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-4 space-y-3">
                            @if($selectedChildren->count() > 0)
                                @foreach($selectedChildren as $child)
                                    <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ $child->first_name }}</span>
                                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $child->code }}</span>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                Né(e) en {{ $child->birth_year }} — {{ $child->gift }}
                                            </div>
                                            <button wire:click="markAsGiven('{{ $child->id }}')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition mt-1">
                                                🎁 Remis
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                @if($selectedChildren->count() > 1)
                                    <div class="pt-3 border-t border-gray-200 dark:border-zinc-700">
                                        <button wire:click="markAllAsGiven('{{ $selectedFamily->id }}')" wire:confirm="Confirmer la remise de tous les cadeaux de cette famille ?" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition">
                                            🎁 Tout remettre ({{ $selectedChildren->count() }} cadeaux)
                                        </button>
                                    </div>
                                @endif
                            @else
                                <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Aucun cadeau à remettre pour cette famille.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-8 text-center text-gray-500 dark:text-gray-400">
                Entrez un nom de famille pour rechercher les cadeaux à remettre.
            </div>
        @endif
    @endif
</div>
