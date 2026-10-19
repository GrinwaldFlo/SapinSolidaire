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
@php
    $reasonKey = \App\Livewire\Admin\FamilyManagement::exclusionReasonKey($family->id);
@endphp
<div x-data="{ action: '' }" class="family-actions">
    <div class="badge-row">
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
    @if(!empty($predefinedRejectionMessages))
        <select
            x-show="action === 'exclude'"
            x-on:change="if ($event.target.value !== '') { $wire.insertExclusionTemplate('{{ $family->id }}', $event.target.value); } $event.target.value = '';"
            class="field-input"
        >
            <option value="">Insérer un message prédéfini</option>
            @foreach($predefinedRejectionMessages as $index => $template)
                <option value="{{ $index }}">{{ \Illuminate\Support\Str::limit($template, 60) }}</option>
            @endforeach
        </select>
    @endif
    <textarea
        x-show="action === 'exclude'"
        wire:model="{{ $reasonKey }}"
        class="field-input"
        rows="2"
        placeholder="Motif de l'exclusion (obligatoire)"
    ></textarea>
    @error($reasonKey)
        <p class="field-error">{{ $message }}</p>
    @enderror
    <button
        type="button"
        x-on:click="
            if (action === 'exclude' && !confirm('Exclure définitivement cette famille ?')) { return; }
            $wire.applyAction('{{ $family->id }}', action).then((applied) => { if (applied) { action = ''; } });
        "
        class="btn-gray btn-sm"
        x-bind:disabled="!action"
    >
        Appliquer
    </button>
</div>
