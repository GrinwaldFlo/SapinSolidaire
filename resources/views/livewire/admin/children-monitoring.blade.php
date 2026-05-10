<div class="space-y-6">
    {{-- Family details modal --}}
    @if($showFamilyModal && $selectedFamily)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeFamilyModal()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeFamilyModal"></div>
            <div class="modal-panel">
                <div class="modal-header">
                    <h2 class="section-title">Famille {{ $selectedFamily['last_name'] }}</h2>
                    <button wire:click="closeFamilyModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    {{-- Parent --}}
                    <div>
                        <h3 class="sub-label">Parent</h3>
                        <p class="detail-value">{{ $selectedFamily['first_name'] }} {{ $selectedFamily['last_name'] }}</p>
                    </div>

                    {{-- Email --}}
                    @if($selectedFamily['email'])
                        <div>
                            <h3 class="sub-label">Email</h3>
                            <a href="mailto:{{ $selectedFamily['email'] }}" class="link break-all">{{ $selectedFamily['email'] }}</a>
                        </div>
                    @endif

                    {{-- Phone --}}
                    @if($selectedFamily['phone'])
                        <div>
                            <h3 class="sub-label">Téléphone</h3>
                            <a href="tel:{{ $selectedFamily['tel_phone'] }}" class="link">{{ $selectedFamily['formatted_phone'] }}</a>
                        </div>
                    @endif

                    {{-- Address --}}
                    @if($selectedFamily['full_address'])
                        <div>
                            <h3 class="sub-label">Adresse</h3>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($selectedFamily['full_address']) }}" target="_blank" rel="noopener" class="link">{{ $selectedFamily['full_address'] }}</a>
                        </div>
                    @endif

                    {{-- Children --}}
                    @if(count($selectedFamily['children']) > 0)
                        <div>
                            <h3 class="sub-label mb-2">Enfants</h3>
                            <ul class="space-y-2">
                                @foreach($selectedFamily['children'] as $familyChild)
                                    <li class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-3">
                                        <p class="detail-value">{{ $familyChild['first_name'] }}</p>
                                        <p class="text-sm text-muted">
                                            {{ $familyChild['formatted_age'] }}
                                            @if($familyChild['gender_label']) — {{ $familyChild['gender_label'] }} @endif
                                            @if($familyChild['gift']) — {{ $familyChild['gift'] }} @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button wire:click="closeFamilyModal" class="w-full btn-secondary">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    @endif
    <h1 class="section-title">Suivi des enfants</h1>

    <div class="card-sm">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="field-label">Saison</label>
                <select wire:model.live="selectedSeasonId" class="field-input">
                    <option value="">-- Sélectionner --</option>
                    @foreach($seasons as $season)
                        <option value="{{ $season->id }}">{{ $season->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label">Statut</label>
                <select wire:model.live="statusFilter" class="field-input">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="field-label">Recherche</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Code, prénom, nom, cadeau…" class="field-input" />
            </div>

            @if($selectedSeasonId)
                <div class="ml-auto">
                    <button
                        wire:click="exportPdf"
                        wire:loading.attr="disabled"
                        wire:target="exportPdf"
                        class="btn-confirm"
                    >
                        <span wire:loading.remove wire:target="exportPdf">📄 Exporter PDF</span>
                        <span wire:loading wire:target="exportPdf">⏳ Export en cours…</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if($selectedSeasonId)
        {{-- Desktop table --}}
        <div class="hidden sm:block table-container">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th wire:click="sort('code')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Code
                            @if($sortBy === 'code') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('first_name')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Prénom
                            @if($sortBy === 'first_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('gender')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Genre
                            @if($sortBy === 'gender') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('birth_year')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Âge
                            @if($sortBy === 'birth_year') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('gift')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Cadeau
                            @if($sortBy === 'gift') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('family_name')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Famille
                            @if($sortBy === 'family_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('status')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Statut
                            @if($sortBy === 'status') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($children as $child)
                        <tr>
                            <td class="table-cell font-mono font-bold">{{ $child->code ?? '—' }}</td>
                            <td class="table-cell">
                                {{ $child->first_name }}
                                @if($child->anonymous)
                                    <span class="ml-1 text-xs text-orange-600 dark:text-orange-400">(A)</span>
                                @endif
                            </td>
                            <td class="table-cell-muted">
                                @if($child->gender !== 'unspecified') {{ $child->gender_label }} @else - @endif
                            </td>
                            <td class="table-cell-muted">{{ $child->formatted_age }}</td>
                            <td class="table-cell-muted">{{ $child->gift }}</td>
                            <td class="table-cell">
                                <button wire:click="showFamilyDetails('{{ $child->giftRequest->family->id }}')" class="link font-medium">
                                    {{ $child->giftRequest->family->last_name }}
                                </button>
                            </td>
                            <td class="table-cell">
                                <span class="
                                    @switch($child->status)
                                        @case('pending') badge--pending @break
                                        @case('validated') badge--info @break
                                        @case('rejected') badge--warning @break
                                        @case('rejected_final') badge--rejected @break
                                        @case('printed') badge--printed @break
                                        @case('received') badge--received @break
                                        @case('given') badge--given @break
                                    @endswitch
                                ">
                                    {{ $child->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">Aucun enfant trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-3">
            @forelse($children as $child)
                <div class="card-sm space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-bold detail-value">{{ $child->code ?? '—' }}</span>
                        <span class="
                            @switch($child->status)
                                @case('pending') badge--pending @break
                                @case('validated') badge--info @break
                                @case('rejected') badge--warning @break
                                @case('rejected_final') badge--rejected @break
                                @case('printed') badge--printed @break
                                @case('received') badge--received @break
                                @case('given') badge--given @break
                            @endswitch
                        ">
                            {{ $child->status_label }}
                        </span>
                    </div>
                    <div class="detail-value">
                        {{ $child->first_name }}
                        @if($child->anonymous)
                            <span class="ml-1 text-xs text-orange-600 dark:text-orange-400">(A)</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-muted">
                        <div><span class="detail-value text-sm">Genre :</span> @if($child->gender !== 'unspecified') {{ $child->gender_label }} @else - @endif</div>
                        <div><span class="detail-value text-sm">Âge :</span> {{ $child->formatted_age }}</div>
                        <div class="col-span-2"><span class="detail-value text-sm">Cadeau :</span> {{ $child->gift }}</div>
                        <div class="col-span-2">
                            <span class="detail-value text-sm">Famille :</span>
                            <button wire:click="showFamilyDetails('{{ $child->giftRequest->family->id }}')" class="link font-medium">
                                {{ $child->giftRequest->family->last_name }}
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card-sm text-muted text-center">
                        Aucun enfant trouvé
                    </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $children->links() }}
        </div>
    @else
        <div class="notice-info">
            Sélectionnez une saison pour voir les enfants.
        </div>
    @endif
</div>
