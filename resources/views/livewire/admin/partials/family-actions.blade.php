@php
    $actionRequest = $activeSeason ? $family->giftRequests->firstWhere('season_id', $activeSeason->id) : null;
    $isFinalRequest = $actionRequest?->status === \App\Models\GiftRequest::STATUS_REJECTED_FINAL;
    $isExcluded = $family->isExcluded();
    $statusBadgeClass = match ($actionRequest?->status) {
        \App\Models\GiftRequest::STATUS_VALIDATED => 'badge--validated',
        \App\Models\GiftRequest::STATUS_REJECTED, \App\Models\GiftRequest::STATUS_REJECTED_FINAL => 'badge--rejected',
        default => 'badge--pending',
    };
@endphp
<div x-data="{ action: '', reason: '' }" class="space-y-2">
    <div class="flex flex-wrap gap-1">
        @if($isExcluded)
            <span class="badge--rejected" title="{{ $family->exclusion_reason }}">{{ $statuses[\App\Models\GiftRequest::STATUS_REJECTED_FINAL] }}</span>
        @elseif($actionRequest)
            <span class="{{ $statusBadgeClass }}">{{ $statuses[$actionRequest->status] ?? $actionRequest->status }}</span>
        @else
            <span class="badge--neutral">Aucune demande cette saison</span>
        @endif
    </div>
    <select x-model="action" class="field-input">
        <option value="">Choisir une action</option>
        @if($actionRequest)
            <option value="reset_pending">Remettre en attente</option>
        @elseif($isExcluded)
            <option value="reset_pending">Lever l'exclusion</option>
        @endif
        @if(! $isExcluded || ($actionRequest && ! $isFinalRequest))
            <option value="exclude">Exclure la famille</option>
        @endif
    </select>
    @if(!empty($rejectionTemplates))
        <select
            x-show="action === 'exclude'"
            x-on:change="if ($event.target.value !== '') { reason = reason.trim() === '' ? $event.target.value : reason.trim() + '\n\n' + $event.target.value; } $event.target.value = '';"
            class="field-input"
        >
            <option value="">Insérer un message prédéfini</option>
            @foreach($rejectionTemplates as $template)
                <option value="{{ $template }}">{{ \Illuminate\Support\Str::limit($template, 60) }}</option>
            @endforeach
        </select>
    @endif
    <textarea
        x-show="action === 'exclude'"
        x-model="reason"
        class="field-input"
        rows="2"
        placeholder="Motif de l'exclusion (obligatoire)"
    ></textarea>
    <button
        type="button"
        x-on:click="
            if (action === 'exclude') {
                if (!reason.trim()) { return; }
                if (!confirm('Exclure définitivement cette famille ?')) { return; }
            }
            $wire.applyAction('{{ $family->id }}', action, reason);
            action = ''; reason = '';
        "
        class="btn-gray text-sm"
        x-bind:disabled="!action || (action === 'exclude' && !reason.trim())"
    >
        Appliquer
    </button>
</div>
