{{-- Family name and contact cells of a correction reminder line ($giftRequest with its family loaded; $showStatus: family status badge, default true) --}}
@php($family = $giftRequest->family)
<td class="table-cell">
    <div class="detail-value">{{ trim((string) $family->full_name) !== '' ? $family->full_name : '—' }}</div>
    @if($showStatus ?? true)
        <span class="{{ $giftRequest->status_badge_class }}">Famille : {{ $giftRequest->status_label }}</span>
    @endif
</td>
<td class="table-cell-muted">
    <x-admin.family-contact :family="$family" />
</td>
