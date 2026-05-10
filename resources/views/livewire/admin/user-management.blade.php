<div class="space-y-6">
    <h1 class="section-title">Gestion des utilisateurs</h1>

    @if(session()->has('message'))
        <div class="notice-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead>
                <tr>
                    <th class="table-header">Nom</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Rôles</th>
                    <th class="table-header text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @foreach($users as $user)
                    <tr>
                        <td class="table-cell">{{ $user->name }}</td>
                        <td class="table-cell-muted">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @if($editingUserId === $user->id)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($roles as $role)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="rounded border-gray-300 dark:border-zinc-600">
                                            <span class="ml-1 detail-label">{{ ucfirst($role->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="badge--info">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span class="text-muted text-sm">Aucun rôle</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>
                        <td class="table-cell text-right">
                            @if($editingUserId === $user->id)
                                <button wire:click="saveRoles" class="btn-confirm mr-2">Enregistrer</button>
                                <button wire:click="cancelEdit" class="btn-secondary">Annuler</button>
                            @else
                                <button wire:click="editRoles('{{ $user->id }}')" class="link">Modifier les rôles</button>
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
