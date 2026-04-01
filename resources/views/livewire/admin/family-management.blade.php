<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des familles</h1>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher par nom ou email..." class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Téléphone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Demandes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($families as $family)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $family->first_name }} {{ $family->last_name }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $family->email }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                            @if($family->phone)
                                <a href="tel:{{ $family->tel_phone }}" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.01z"/></svg>
                                    {{ $family->formatted_phone }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @foreach($family->giftRequests as $request)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200 mr-1 mb-1">
                                    {{ $request->season->name }} ({{ $request->children->count() }} enfant(s))
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aucune famille trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse($families as $family)
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 space-y-2">
                <div class="font-semibold text-gray-900 dark:text-white text-base">
                    {{ $family->first_name }} {{ $family->last_name }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300 break-all">
                    {{ $family->email }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    @if($family->phone)
                        <a href="tel:{{ $family->tel_phone }}" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">
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
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200">
                                {{ $request->season->name }} ({{ $request->children->count() }} enfant(s))
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 text-center text-gray-500 dark:text-gray-400">
                Aucune famille trouvée
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $families->links() }}
    </div>
</div>
