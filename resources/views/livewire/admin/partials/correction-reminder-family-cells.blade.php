{{-- Family name and contact cells of a correction reminder line ($giftRequest with its family loaded; $showStatus: family status badge, default true) --}}
@php($family = $giftRequest->family)
<td class="table-cell">
    <div class="detail-value">{{ trim((string) $family->full_name) !== '' ? $family->full_name : '—' }}</div>
    @if($showStatus ?? true)
        @if($giftRequest->status === \App\Models\GiftRequest::STATUS_REJECTED)
            <span class="badge--warning">Famille refusée</span>
        @elseif($giftRequest->status === \App\Models\GiftRequest::STATUS_PENDING)
            <span class="badge--pending">Famille à valider</span>
        @endif
    @endif
</td>
<td class="table-cell-muted">
    <div class="contact-list">
        <div><a href="mailto:{{ $family->email }}" class="link">{{ $family->email }}</a></div>
        @if($family->phone)
            <div><a href="tel:{{ $family->tel_phone }}" class="link">{{ $family->formatted_phone }}</a></div>
        @endif
        @if($family->full_address)
            <div class="cell-subtext">{{ $family->full_address }}</div>
        @endif
    </div>
</td>
