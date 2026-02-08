# Envoi des confirmations de réception

Ceci est une mise à jour de la partie de l'envoie de la confiramtions aux parents

## Implémenation existante

**Accessible par** : Organisateur

Cette interface permet de notifier les enfants de la réception de leur cadeau.

**Affichage**
- Liste de tous les enfants avec le statut **Reçu**
- Date d'envoi du dernier e-mail de confirmation (si applicable)

**Action**
- Bouton permettant d'envoyer un e-mail à tous les enfants avec le statut **Reçu**
- La date et l'heure d'envoi sont enregistrées en base de données

**Contenu de l'email**
L'email doit contenir les informations suivantes:

- Prénom et nom de l'enfant
- Cadeau qu'il va recevoir
- Le code à 4 lettres

## Nouvelle implémentation

Page: `/admin/confirmations`

Il n'y a actuellement pas de gestion de créneaux pour que les familles aillent chercher leurs cadeaux. Il faut donc répartire les familles sur plusieurs jours dans certaines plage horaire.
Il est important d'inviter les familles, et pas les enfants. Une famille avec plusieurs enfants occupera un seul élement de créneau.

### Nouveaux paramètres de Saison

La table `Season` a les nouveaux paramètres suivants:

- LimiteFamilleParCréneau , permet de déterminer combien de famille il y a par créneau
- DuréeCréneauMinutes, durée d'un crénéeau
- Multiples entrées pour la récupération avec : Date/Heure de début, Date/Heure de Fin
- NomResp : Nom Prénom du responsable de sapin solidaire
- TelResp: Téléphone du responsable de sapin solidaire
- EmailResp : Email du responsable de sapin solidaire

### Nouvel email

```
Bonjour [nom],
Vous avez inscrit vos enfants au Sapin solidaire afin qu’ils reçoivent un cadeau de Noël.
Les cadeaux sont prêts !
Merci de venir chercher vos cadeaux [date] à la Maison de Paroisse d'Yverdon, rue Pestalozzi 6, entre [heure].
N’oubliez pas de prendre avec vous votre pièce d’identité et celles de vos enfants.
Pensez également à prendre un grand sac avec vous pour y glisser les cadeaux qui sont parfois volumineux.
Nous nous réjouissons de vous voir !
Au nom du comité de Sapin Solidaire
{NomResp}
Téléphone : {TelResp}
{EmailResp}
```

### Nouveau comportement

Dans la page `/admin/confirmations`

- Appliquer automatiquement un créneau aux familles qui n'en n'ont pas
- En tout temps, afficher la liste des familles avec le nombre d'enfant, la date de récéption, l'heure de début et de fin de créneau.
- Ajouter un bouton pour recalculer tous le créneaux (avec confirmation).
- Ajouter un bouton qui permet de prévisualiser l'email qui va être envoyé.
- Mettre un avertissement s'il n'y a pas assez de créneaux
