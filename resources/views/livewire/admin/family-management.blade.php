<div class="space-y-6">
    <h1 class="section-title">Gestion des familles</h1>

    <div class="card-sm">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher par nom, email, téléphone, adresse ou prénom d'enfant..." class="field-input" autocomplete="off" data-bwignore="true" data-1p-ignore data-lpignore="true">
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th wire:click="sort('first_name')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white select-none">
                        Prénom @if($sortBy === 'first_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th wire:click="sort('last_name')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white select-none">
                        Nom @if($sortBy === 'last_name') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th wire:click="sort('email')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white select-none">
                        Email @if($sortBy === 'email') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th wire:click="sort('phone')" class="table-header cursor-pointer hover:text-gray-700 dark:hover:text-white select-none">
                        Téléphone @if($sortBy === 'phone') <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span> @else <span class="opacity-30">▲</span> @endif
                    </th>
                    <th class="table-header">Adresse</th>
                    <th class="table-header">Demandes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($families as $family)
                    <tr>
                        <td class="table-cell">{{ $family->first_name }}</td>
                        <td class="table-cell">{{ $family->last_name }}</td>
                        <td class="table-cell-muted">
                            <a href="mailto:{{ $family->email }}" class="inline-flex items-center gap-1 link">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                {{ $family->email }}
                            </a>
                        </td>
                        <td class="table-cell-muted">
                            @if($family->phone)
                                <a href="tel:{{ $family->tel_phone }}" class="inline-flex items-center gap-1 link">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.01z"/></svg>
                                    {{ $family->formatted_phone }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="table-cell-muted text-sm">
                            @if($family->full_address)
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($family->full_address) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 link">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                    {{ $family->full_address }}
                                </a>
                            @else
                                -
                            @endif
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="table-empty">Aucune famille trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse($families as $family)
            <div class="card-sm space-y-2">
                <div class="font-semibold detail-value text-base">
                    {{ $family->first_name }} {{ $family->last_name }}
                </div>
                <div class="text-sm text-muted break-all">
                    <a href="mailto:{{ $family->email }}" class="inline-flex items-center gap-1 link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        {{ $family->email }}
                    </a>
                </div>
                <div class="text-sm text-muted">
                    @if($family->phone)
                        <a href="tel:{{ $family->tel_phone }}" class="inline-flex items-center gap-1 link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.01z"/></svg>
                            {{ $family->formatted_phone }}
                        </a>
                    @else
                        -
                    @endif
                </div>
                @if($family->full_address)
                    <div class="text-sm text-muted">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($family->full_address) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            {{ $family->full_address }}
                        </a>
                    </div>
                @endif
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
