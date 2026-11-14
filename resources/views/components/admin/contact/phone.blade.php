{{-- Phone of a family as a tel link with its icon ($tel: tel_phone, $formatted: formatted_phone) --}}
@props(['tel', 'formatted'])
<a href="tel:{{ $tel }}" {{ $attributes->merge(['class' => 'link contact-link']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.01z"/></svg>
    {{ $formatted }}
</a>
