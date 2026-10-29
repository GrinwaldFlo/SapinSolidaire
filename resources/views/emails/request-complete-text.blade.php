Chère famille {!! $familyName !!},

{!! $introText !!}
@if($childNames->isNotEmpty())

Enfant(s) concerné(s) :
@foreach($childNames as $childName)
- {!! $childName !!}
@endforeach
@endif

{!! $bodyText !!}

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
