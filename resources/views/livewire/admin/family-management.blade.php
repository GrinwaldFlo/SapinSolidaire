<div class="space-y-6">
    <h1 class="section-title">Gestion des familles</h1>

    <div class="card-sm">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="field-label">Statut</label>
                <select wire:model.live="statusFilter" class="field-input">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[280px]">
                <label class="field-label">Recherche</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher par nom, email, téléphone, adresse ou prénom d'enfant..." class="field-input" autocomplete="off" data-bwignore="true" data-1p-ignore data-lpignore="true">
            </div>
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th wire:click="sort('first_name')" class="table-header--sortable">
                        Prénom @if($sortBy === 'first_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th wire:click="sort('last_name')" class="table-header--sortable">
                        Nom @if($sortBy === 'last_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th class="table-header">Contact</th>
                    <th class="table-header">Demandes</th>
                    <th class="table-header">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($families as $family)
                    <tr wire:key="family-row-{{ $family->id }}">
                        <td class="table-cell">{{ $family->first_name }}</td>
                        <td class="table-cell">{{ $family->last_name }}</td>
                        <td class="table-cell-muted text-sm">
                            <x-admin.family-contact :family="$family" />
                            </div>
                        </td>
                        <td class="table-cell">
                            @foreach($family->giftRequests as $request)
                                <div class="mb-2">
                                    <span class="badge--neutral inline-flex items-center gap-1 mb-1">
                                        {{ $request->season->name }}
                                    </span>
                                    <ul class="ml-1 space-y-0.5">
                                        @foreach($request->children as $child)
                                            <li class="text-sm flex items-center gap-1 text-muted">
                                                @if($child->gender === 'boy')
                                                    <span class="text-blue-500" title="Garçon">♂</span>
                                                @elseif($child->gender === 'girl')
                                                    <span class="text-pink-500" title="Fille">♀</span>
                                                @else
                                                    <span class="text-gray-400" title="Non précisé">○</span>
                                                @endif
                                                <span>{{ $child->first_name }}</span>
                                                <span class="text-xs">({{ $child->birth_year }})</span>
                                                @if($child->gift)
                                                    <span class="text-xs text-muted">— {{ $child->gift }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </td>
                        <td class="table-cell">
                            @include('livewire.admin.partials.family-actions', ['family' => $family])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="table-empty">Aucune famille trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse($families as $family)
            <div wire:key="family-card-{{ $family->id }}" class="card-sm space-y-2">
                <div class="font-semibold detail-value text-base">
                    {{ $family->first_name }} {{ $family->last_name }}
                </div>
                <x-admin.family-contact :family="$family" class="text-sm text-muted" />
                @if($family->giftRequests->isNotEmpty())
                    <div class="space-y-2">
                        @foreach($family->giftRequests as $request)
                            <div>
                                <span class="badge--neutral inline-flex items-center gap-1 mb-1">
                                    {{ $request->season->name }}
                                </span>
                                <ul class="ml-1 space-y-0.5">
                                    @foreach($request->children as $child)
                                        <li class="text-sm flex items-center gap-1 text-muted">
                                            @if($child->gender === 'boy')
                                                <span class="text-blue-500" title="Garçon">♂</span>
                                            @elseif($child->gender === 'girl')
                                                <span class="text-pink-500" title="Fille">♀</span>
                                            @else
                                                <span class="text-gray-400" title="Non précisé">○</span>
                                            @endif
                                            <span>{{ $child->first_name }}</span>
                                            <span class="text-xs">({{ $child->birth_year }})</span>
                                            @if($child->gift)
                                                <span class="text-xs text-muted">— {{ $child->gift }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="space-y-2 pt-1">
                    @include('livewire.admin.partials.family-actions', ['family' => $family])
                </div>
            </div>
        @empty
            <div class="card-sm text-center text-muted">
                Aucune famille trouvée
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $families->links() }}
    </div>
</div>
