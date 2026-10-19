Chère famille {{ $familyName }},

Vous avez inscrit vos enfants au Sapin solidaire afin qu'ils reçoivent un cadeau de Noël.
@if($childNames->isNotEmpty())

Enfant(s) concerné(s) :
@foreach($childNames as $childName)
- {{ $childName }}
@endforeach
@endif

Votre demande est à présent complète et ne peut plus être modifiée. Vous recevrez prochainement un message pour venir chercher les cadeaux souhaités{{ $pickupDateText !== '' ? ' '.$pickupDateText : '' }} à l'heure qui vous sera indiquée.

Au nom du comité de Sapin Solidaire
@if($responsibleName)
{{ $responsibleName }}
@endif
@if($responsiblePhone)
Téléphone : {{ $responsiblePhone }}
@endif
@if($responsibleEmail)
{{ $responsibleEmail }}
@endif

Cet e-mail a été envoyé automatiquement. Vous pouvez y répondre directement si vous avez des questions.
