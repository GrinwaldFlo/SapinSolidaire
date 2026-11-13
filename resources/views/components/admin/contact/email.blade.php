{{-- Email of a family as a mailto link with its icon --}}
@props(['email'])
<a href="mailto:{{ $email }}" {{ $attributes->merge(['class' => 'link contact-link break-all']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
    {{ $email }}
</a>
