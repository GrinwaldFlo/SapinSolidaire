@if($organizerEmail)
    <p class="text-muted mt-4 text-sm">
        Pour toute question, veuillez contacter l'organisateur à l'adresse :
        <a href="mailto:{{ $organizerEmail }}" class="link">{{ $organizerEmail }}</a>
    </p>
@endif
