Chère famille {!! $familyName !!},

{!! $introText !!}

Les cadeaux sont prêts !

@if($slotDate && $slotStartTime && $slotEndTime)
Merci de venir chercher vos cadeaux le {!! $slotDate !!} :
{!! $slotStartTime !!} - {!! $slotEndTime !!}

Adresse de retrait :
{!! $pickupAddress !!}
https://www.google.com/maps/search/?api=1&query={!! urlencode($pickupAddress) !!}
@if($googleCalendarUrl)

Ajouter à mon agenda Google :
{!! $googleCalendarUrl !!}
@endif
@endif

{!! $remindersText !!}

{!! $closingText !!}

@if($responsibleName)
{!! $responsibleName !!}
@endif
@if($responsiblePhone)
{!! $responsiblePhone !!}
@endif
@if($responsibleEmail)
{!! $responsibleEmail !!}
@endif

{!! $footerText !!}
