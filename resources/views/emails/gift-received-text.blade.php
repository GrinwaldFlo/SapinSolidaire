Chère famille {{ $familyName }},

Vous avez inscrit vos enfants au Sapin solidaire afin qu'ils reçoivent un cadeau de Noël.

Les cadeaux sont prêts !

@if($slotDate && $slotStartTime && $slotEndTime)
Merci de venir chercher vos cadeaux le {{ $slotDate }} :
{{ $slotStartTime }} - {{ $slotEndTime }}

Adresse de retrait :
{{ $pickupAddress }}
https://www.google.com/maps/search/?api=1&query={{ urlencode($pickupAddress) }}
@if($googleCalendarUrl)

Ajouter à mon agenda Google :
{{ $googleCalendarUrl }}
@endif
@endif

N'oubliez pas de prendre avec vous votre pièce d'identité et celles de vos enfants.
Pensez également à prendre un grand sac avec vous pour y glisser les cadeaux qui sont parfois volumineux.

Nous nous réjouissons de vous voir !

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
