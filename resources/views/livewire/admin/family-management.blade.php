<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des familles</h1>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Rechercher par nom ou email..." class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white">
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
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
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $family->phone ?? '-' }}</td>
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

    <div class="mt-4">
        {{ $families->links() }}
    </div>
</div>
