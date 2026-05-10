<div class="space-y-6">
    <h1 class="section-title">Remise des cadeaux</h1>

    @if(!$activeSeason)
        <div class="notice-warning">
            Aucune saison n'est actuellement active.
        </div>
    @else
        {{-- Search input --}}
        <div class="card-sm">
            <label class="field-label">Nom de famille</label>
            <div class="flex items-center gap-3">
                <input
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    wire:model.live.debounce.300ms="searchName"
                    placeholder="Rechercher par nom…"
                    class="w-full md:w-64 field-input"
                />
                @if($searchName !== '')
                    <button wire:click="clearFilter" class="btn-secondary text-sm shrink-0">
                        Effacer
                    </button>
                @endif
            </div>
        </div>

        {{-- Results --}}
        @if($searchName !== '')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Family list --}}
                <div class="table-container">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="section-title">
                            Familles
                            <span class="text-sm font-normal text-muted">— {{ count($families) }} résultat(s)</span>
                        </h2>
                    </div>

                    @if(count($families) === 0)
                        <div class="p-8 text-center text-muted">
                            Aucune famille trouvée avec des cadeaux à remettre.
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($families as $family)
                                <li wire:click="selectFamily('{{ $family->id }}')" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer {{ $selectedFamilyId === $family->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <div class="flex justify-between items-center">
                                        <span class="detail-value">{{ $family->last_name }}</span>
                                        <span class="text-sm text-muted">{{ $family->first_name }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Desktop detail panel --}}
                <div class="hidden lg:block table-container">
                    <div class="p-4 border-b border-gray-200 dark:border-zinc-700">
                        <h2 class="section-title">Détails de la famille</h2>
                    </div>

                    @if($selectedFamily && $selectedChildren->count() > 0)
                        <div class="p-6 space-y-4">
                            <div class="text-center mb-4">
                                <div class="label-value">{{ $selectedFamily->last_name }}</div>
                                <div class="label-title">{{ $selectedChildren->count() }} enfant(s) à remettre</div>
                            </div>

                            <div class="space-y-3">
                                @foreach($selectedChildren as $child)
                                    <div class="slot-pill">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
                                            <div class="space-y-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="detail-value">{{ $child->first_name }}</span>
                                                    <span class="text-xs font-mono text-muted bg-gray-100 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $child->code }}</span>
                                                </div>
                                                <div class="text-sm text-muted">
                                                    Né(e) en {{ $child->birth_year }} — {{ $child->gift }}
                                                </div>
                                            </div>
                                            <button wire:click="markAsGiven('{{ $child->id }}')" class="btn-confirm shrink-0 text-sm py-1 px-3">
                                                🎁 Remis
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($selectedChildren->count() > 1)
                                <div class="pt-4 border-t border-gray-200 dark:border-zinc-700">
                                    <button wire:click="markAllAsGiven('{{ $selectedFamily->id }}')" wire:confirm="Confirmer la remise de tous les cadeaux de cette famille ?" class="w-full btn-primary !py-3">
                                        🎁 Tout remettre ({{ $selectedChildren->count() }} cadeaux)
                                    </button>
                                </div>
                            @endif
                        </div>
                    @elseif($selectedFamily)
                        <div class="p-8 text-center text-muted">
                            Aucun cadeau à remettre pour cette famille.
                        </div>
                    @else
                        <div class="p-8 text-center text-muted">
                            Sélectionnez une famille dans la liste pour voir les détails.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mobile detail modal --}}
            @if($selectedFamily && $showMobileDetail)
                <div class="lg:hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center" wire:click.self="closeMobileDetail">
                    <div class="fixed inset-0 bg-black/50" wire:click="closeMobileDetail"></div>
                    <div class="relative card w-full sm:max-w-md sm:rounded-lg rounded-t-2xl shadow-xl max-h-[90vh] overflow-y-auto">
                        <div class="p-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                            <h2 class="section-title">{{ $selectedFamily->last_name }}</h2>
                            <button wire:click="closeMobileDetail" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-4 space-y-3">
                            @if($selectedChildren->count() > 0)
                                @foreach($selectedChildren as $child)
                                    <div class="slot-pill">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="detail-value">{{ $child->first_name }}</span>
                                                <span class="text-xs font-mono text-muted bg-gray-100 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $child->code }}</span>
                                            </div>
                                            <div class="text-sm text-muted">
                                                Né(e) en {{ $child->birth_year }} — {{ $child->gift }}
                                            </div>
                                            <button wire:click="markAsGiven('{{ $child->id }}')" class="w-full btn-confirm text-sm py-1 px-3 mt-1">
                                                🎁 Remis
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                @if($selectedChildren->count() > 1)
                                    <div class="pt-3 border-t border-gray-200 dark:border-zinc-700">
                                        <button wire:click="markAllAsGiven('{{ $selectedFamily->id }}')" wire:confirm="Confirmer la remise de tous les cadeaux de cette famille ?" class="w-full btn-primary !py-3">
                                            🎁 Tout remettre ({{ $selectedChildren->count() }} cadeaux)
                                        </button>
                                    </div>
                                @endif
                            @else
                                <div class="p-4 text-center text-muted">
                                    Aucun cadeau à remettre pour cette famille.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @else
        <div class="card-sm text-center text-muted">
            Entrez un nom de famille pour rechercher les cadeaux à remettre.
        </div>
        @endif
    @endif
</div>
