<div class="space-y-6">
    <h1 class="section-title">Journal des actions admin</h1>

    <div class="card-sm">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="field-label">Type d'action</label>
                <select wire:model.live="actionTypeFilter" class="field-input">
                    <option value="">Toutes les actions</option>
                    @foreach($actionTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[220px]">
                <label class="field-label">Utilisateur</label>
                <input
                    wire:model.live.debounce.300ms="userSearch"
                    type="text"
                    placeholder="Nom utilisateur…"
                    class="field-input"
                    autocomplete="off"
                    data-bwignore="true"
                    data-1p-ignore
                    data-lpignore="true"
                />
            </div>

            <div>
                <label class="field-label">Du</label>
                <input wire:model.live="dateFrom" type="date" class="field-input" />
            </div>

            <div>
                <label class="field-label">Au</label>
                <input wire:model.live="dateTo" type="date" class="field-input" />
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Date</th>
                    <th class="table-header">Utilisateur</th>
                    <th class="table-header">Action</th>
                    <th class="table-header">Détail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($actionLogs as $log)
                    <tr>
                        <td class="table-cell-muted" data-local-datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="table-cell">{{ $log->user?->name ?? $log->user_label ?? 'Utilisateur supprimé' }}</td>
                        <td class="table-cell">
                            @if($log->action_type === \App\Models\AdminActionLog::ACTION_FAMILY_VALIDATED)
                                Validation famille
                            @elseif($log->action_type === \App\Models\AdminActionLog::ACTION_CHILD_VALIDATED)
                                Validation enfant
                            @elseif($log->action_type === \App\Models\AdminActionLog::ACTION_FAMILY_STATUS_RESET)
                                Réinitialisation statut famille
                            @elseif($log->action_type === \App\Models\AdminActionLog::ACTION_EMAIL_SENT)
                                Envoi e-mail
                            @else
                                {{ $log->action_type }}
                            @endif
                        </td>
                        <td class="table-cell-muted">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="table-cell-muted text-center">Aucune action admin journalisée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $actionLogs->links() }}
    </div>
</div>
