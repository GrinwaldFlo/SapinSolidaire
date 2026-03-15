<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Suivi des enfants</h1>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <div class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Saison</label>
                <select wire:model.live="selectedSeasonId" class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    <option value="">-- Sélectionner --</option>
                    @foreach($seasons as $season)
                        <option value="{{ $season->id }}">{{ $season->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Code, prénom, nom, cadeau…" class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white" />
            </div>
        </div>
    </div>

    @if($selectedSeasonId)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th wire:click="sort('code')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Code
                            @if($sortBy === 'code') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('first_name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Prénom
                            @if($sortBy === 'first_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('gender')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Genre
                            @if($sortBy === 'gender') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('birth_year')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Âge
                            @if($sortBy === 'birth_year') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('gift')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Cadeau
                            @if($sortBy === 'gift') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('family_name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Famille
                            @if($sortBy === 'family_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                        <th wire:click="sort('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase cursor-pointer hover:text-gray-700 dark:hover:text-white">
                            Statut
                            @if($sortBy === 'status') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($children as $child)
                        <tr>
                            <td class="px-6 py-4 font-mono font-bold text-gray-900 dark:text-white">{{ $child->code ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                {{ $child->first_name }}
                                @if($child->anonymous)
                                    <span class="ml-1 text-xs text-orange-600 dark:text-orange-400">(A)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                @if($child->gender !== 'unspecified') {{ $child->gender_label }} @else - @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $child->age }} ans</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $child->gift }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $child->giftRequest->family->last_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @switch($child->status)
                                        @case('pending') bg-yellow-100 text-yellow-800 @break
                                        @case('validated') bg-blue-100 text-blue-800 @break
                                        @case('rejected') bg-orange-100 text-orange-800 @break
                                        @case('rejected_final') bg-red-100 text-red-800 @break
                                        @case('printed') bg-purple-100 text-purple-800 @break
                                        @case('received') bg-cyan-100 text-cyan-800 @break
                                        @case('given') bg-green-100 text-green-800 @break
                                    @endswitch
                                ">
                                    {{ $child->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aucun enfant trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $children->links() }}
        </div>
    @else
        <div class="bg-gray-50 dark:bg-zinc-700 rounded-lg p-8 text-center text-gray-500 dark:text-gray-400">
            Sélectionnez une saison pour voir les enfants.
        </div>
    @endif
</div>
