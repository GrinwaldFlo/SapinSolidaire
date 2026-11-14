<div class="page-stack">
    <h1 class="section-title">Relances des corrections</h1>

    @if(session()->has('message'))
        <div class="notice-success">{{ session('message') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="notice-error">{{ session('error') }}</div>
    @endif

    @if(! $activeSeason)
        <div class="notice-warning">Aucune saison n'est actuellement active.</div>
    @else
        <div class="notice-info">
            <p class="notice-text">Familles et enfants au statut « Refusé » : une correction est attendue de la famille.
Le rappel renvoie le même message de demande de correction avec un nouveau lien valable 48h (un seul e-mail par famille, regroupant les lignes cochées).</p>
        </div>

        @if($this->sendBlockedMessage)
            <div class="notice-warning">
                <p class="notice-title">Les familles ne peuvent plus corriger leur demande, aucun rappel ne peut être envoyé.</p>
                <p class="notice-text">@linkify($this->sendBlockedMessage)</p>
            </div>
        @endif

        <div class="selection-bar">
            <span class="text-muted">{{ $selectedCount }} ligne(s) sélectionnée(s)</span>
            <button
                type="button"
                class="btn-confirm"
                wire:click="sendReminders"
                wire:confirm="Renvoyer la demande de correction aux familles sélectionnées ?"
                wire:loading.attr="disabled"
                @disabled($selectedCount === 0 || ! $this->can_send)
            >
                Envoyer le rappel
            </button>
        </div>

        {{-- Families --}}
        <section class="page-stack">
            <h2 class="section-title">Familles refusées ({{ $requests->count() }})</h2>

            <div class="table-container">
                <table class="table-full">
                    <thead class="table-head">
                        <tr>
                            <th class="table-header-checkbox">
                                <input type="checkbox" class="checkbox-input" title="Tout cocher"
                                    wire:click="toggleAll('{{ \App\Livewire\Admin\CorrectionReminders::LIST_REQUESTS }}')"
                                    @checked($allRequestsSelected) @disabled($requests->isEmpty())>
                            </th>
                            <th class="table-header">Famille</th>
                            <th class="table-header">Contact</th>
                            <th class="table-header">Enfants</th>
                            <th class="table-header">Commentaire</th>
                            <th class="table-header">Refusée le</th>
                            <th class="table-header">Dernière modif. famille</th>
                        </tr>
                    </thead>
                    <tbody class="table-divider">
                        @forelse($requests as $request)
                            <tr wire:key="reminder-request-{{ $request->id }}">
                                <td class="table-cell-checkbox">
                                    <input type="checkbox" class="checkbox-input" value="{{ $request->id }}" wire:model.live="selectedRequestIds"
                                        @disabled(blank($request->rejection_comment)) @if(blank($request->rejection_comment)) title="Aucun commentaire à renvoyer" @endif>
                                </td>
                                @include('livewire.admin.partials.correction-reminder-family-cells', ['giftRequest' => $request, 'showStatus' => false])
                                <td class="table-cell-muted">
                                    <div class="contact-list">
                                        @foreach($request->children as $child)
                                            <div>
                                                {{ $child->first_name }} ({{ $child->formatted_age }})
                                                <span class="{{ $child->status_badge_class }}">{{ $child->status_label }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="table-cell-comment">@linkify($request->rejection_comment)</td>
                                <td class="table-cell-muted">{{ $request->status_changed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="table-cell-muted">{{ $request->last_family_edit_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">Aucune famille en attente de correction</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Children --}}
        <section class="page-stack">
            <h2 class="section-title">Enfants refusés ({{ $children->count() }})</h2>

            <div class="table-container">
                <table class="table-full">
                    <thead class="table-head">
                        <tr>
                            <th class="table-header-checkbox">
                                <input type="checkbox" class="checkbox-input" title="Tout cocher"
                                    wire:click="toggleAll('{{ \App\Livewire\Admin\CorrectionReminders::LIST_CHILDREN }}')"
                                    @checked($allChildrenSelected) @disabled($children->isEmpty())>
                            </th>
                            <th class="table-header">Enfant</th>
                            <th class="table-header">Cadeau</th>
                            <th class="table-header">Famille</th>
                            <th class="table-header">Contact</th>
                            <th class="table-header">Commentaire</th>
                            <th class="table-header">Refusé le</th>
                            <th class="table-header">Dernière modif. famille</th>
                        </tr>
                    </thead>
                    <tbody class="table-divider">
                        @forelse($children as $child)
                            <tr wire:key="reminder-child-{{ $child->id }}">
                                <td class="table-cell-checkbox">
                                    <input type="checkbox" class="checkbox-input" value="{{ $child->id }}" wire:model.live="selectedChildIds"
                                        @disabled(blank($child->rejection_comment)) @if(blank($child->rejection_comment)) title="Aucun commentaire à renvoyer" @endif>
                                </td>
                                <td class="table-cell">
                                    <div class="detail-value">{{ $child->first_name }}</div>
                                    <div class="cell-subtext">{{ $child->formatted_age }} · {{ $child->gender_label }}</div>
                                </td>
                                <td class="table-cell-muted">
                                    {{ $child->gift ?: '—' }}
                                    @if($child->sizes_summary)
                                        <div class="gift-sizes">{{ $child->sizes_summary }}</div>
                                    @endif
                                </td>
                                @include('livewire.admin.partials.correction-reminder-family-cells', ['giftRequest' => $child->giftRequest])
                                <td class="table-cell-comment">@linkify($child->rejection_comment)</td>
                                <td class="table-cell-muted">{{ $child->status_changed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="table-cell-muted">{{ $child->giftRequest->last_family_edit_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-empty">Aucun enfant en attente de correction</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
