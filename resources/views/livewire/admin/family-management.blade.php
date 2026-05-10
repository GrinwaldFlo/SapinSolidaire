<div class="space-y-6">
    <h1 class="section-title">Gestion des familles</h1>

    <div class="card-sm">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher par nom ou email..." class="field-input">
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Nom</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Téléphone</th>
                    <th class="table-header">Demandes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($families as $family)
                    <tr>
                        <td class="table-cell">{{ $family->first_name }} {{ $family->last_name }}</td>
                        <td class="table-cell-muted">{{ $family->email }}</td>
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
                        <td class="table-cell">
                            @foreach($family->giftRequests as $request)
                                <span class="badge--neutral inline-flex items-center gap-1 mr-1 mb-1">
                                    {{ $request->season->name }} ({{ $request->children->count() }} enfant(s))
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="table-empty">Aucune famille trouvée</td>
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
                    {{ $family->email }}
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
                @if($family->giftRequests->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach($family->giftRequests as $request)
                            <span class="badge--neutral inline-flex items-center gap-1">
                                {{ $request->season->name }} ({{ $request->children->count() }} enfant(s))
                            </span>
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
