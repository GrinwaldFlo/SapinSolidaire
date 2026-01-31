<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des utilisateurs</h1>

    @if(session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rôles</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @foreach($users as $user)
                    <tr>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @if($editingUserId === $user->id)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($roles as $role)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="rounded border-gray-300 dark:border-zinc-600">
                                            <span class="ml-1 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($role->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 text-sm">Aucun rôle</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($editingUserId === $user->id)
                                <button wire:click="saveRoles" class="text-green-600 hover:text-green-800 mr-2">Enregistrer</button>
                                <button wire:click="cancelEdit" class="text-gray-600 hover:text-gray-800">Annuler</button>
                            @else
                                <button wire:click="editRoles({{ $user->id }})" class="text-blue-600 hover:text-blue-800">Modifier les rôles</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
