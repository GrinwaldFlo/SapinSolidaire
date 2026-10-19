<div class="space-y-6">
    <h1 class="section-title">Journal des e-mails envoyés</h1>

    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="table-header">Date</th>
                    <th class="table-header">Destinataire</th>
                    <th class="table-header">Objet / But</th>
                    <th class="table-header">Envoyé par</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($mailLogs as $log)
                    <tr>
                        <td class="table-cell-muted" data-local-datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="table-cell">{{ $log->recipient_email }}</td>
                        <td class="table-cell">{{ $log->purpose }}</td>
                        <td class="table-cell-muted">{{ $log->sender?->name ?? $log->sent_by_label ?? 'Système' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="table-cell-muted text-center">Aucun e-mail journalisé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $mailLogs->links() }}
    </div>
</div>
