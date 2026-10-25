Chère famille {!! $familyName !!},

{!! $introText !!}
@if($childNames->isNotEmpty())

Enfant(s) concerné(s) :
@foreach($childNames as $childName)
- {!! $childName !!}
@endforeach
@endif

{!! $bodyText !!}

{!! $signatureText !!}
@if($responsibleName)
{!! $responsibleName !!}
@endif
@if($responsiblePhone)
Téléphone : {!! $responsiblePhone !!}
@endif
@if($responsibleEmail)
{!! $responsibleEmail !!}
@endif

{!! $footerText !!}
