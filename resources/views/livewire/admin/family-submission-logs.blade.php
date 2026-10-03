<div class="space-y-6">
    <h1 class="section-title">Journal des demandes familles</h1>

    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Date</th>
                    <th class="table-header">Email</th>
                    <th class="table-header">Action</th>
                    <th class="table-header">Famille</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($submissionLogs as $log)
                    <tr>
                        <td class="table-cell-muted">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="table-cell">{{ $log->email }}</td>
                        <td class="table-cell">
                            @if($log->action_type === \App\Models\FamilySubmissionLog::ACTION_CREATED)
                                Nouvelle demande
                            @elseif($log->action_type === \App\Models\FamilySubmissionLog::ACTION_UPDATED)
                                Modification demande existante
                            @else
                                {{ $log->action_type }}
                            @endif
                        </td>
                        <td class="table-cell-muted">
                            @if($log->family)
                                {{ $log->family->full_name !== '' ? $log->family->full_name : $log->family->email }}
                            @else
                                Famille supprimée
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="table-cell-muted text-center">Aucune demande famille journalisée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $submissionLogs->links() }}
    </div>
</div>
