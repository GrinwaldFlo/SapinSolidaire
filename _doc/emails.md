# Spécification des e-mails

Ce document décrit tous les e-mails envoyés par l'application **Sapin Solidaire**.

---

## Principes généraux

- **Langue** : Tous les e-mails sont rédigés en français avec un langage simple et accessible
- **Format** : HTML avec design convivial et accessible
- **Envoi** : Asynchrone via queue (base de données)
- **Adresse de réponse** : Configurable dans les paramètres du site (`reply_to_email`)
- **Limite de débit** : 1 e-mail par 5 secondes (configurable via `.env`)

---

## Liste des e-mails

### 1. Lien d'accès au formulaire

**Déclencheur** : La famille entre son adresse e-mail sur la page d'accueil

**Destinataire** : La famille

**Objet** : Votre demande de cadeau - Sapin Solidaire

**Contenu** :

```
Bonjour,

Vous avez demandé à accéder au formulaire de demande de cadeau.

Cliquez sur le lien ci-dessous pour continuer votre demande :

[Accéder au formulaire]

Ce lien est valable pendant 48 heures.

Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.

Cordialement,
L'équipe Sapin Solidaire
```

**Variables** :
- Lien : `/cadeau/{token}/{email}`

**Notes** :
- Le token expire après 48 heures
- Soumis à la limite de débit configurée

---

### 2. Demande de correction (refus temporaire)

**Déclencheur** : Un validateur refuse une demande familiale ou enfant avec demande de correction

**Destinataire** : La famille

**Objet** : Votre demande nécessite une correction - Sapin Solidaire

**Contenu** :

```
Bonjour,

Nous avons examiné votre demande de cadeau et nous avons besoin que vous apportiez une correction.

Motif :
{commentaire_du_validateur}

Cliquez sur le lien ci-dessous pour modifier votre demande :

[Modifier ma demande]

Si vous avez des questions, n'hésitez pas à nous contacter.

Cordialement,
L'équipe Sapin Solidaire
```

**Variables** :
- `{commentaire_du_validateur}` : Le motif de refus saisi par le validateur
- Lien : `/cadeau/{nouveau_token}/{email}`

**Notes** :
- Un nouveau token est généré pour permettre l'accès au formulaire
- Le statut de la demande/enfant est "Refusé"

---

### 3. Refus définitif

**Déclencheur** : Un validateur refuse définitivement une demande familiale ou enfant

**Destinataire** : La famille

**Objet** : Information concernant votre demande - Sapin Solidaire

**Contenu** :

```
Bonjour,

Nous avons examiné votre demande de cadeau et nous sommes dans le regret de vous informer que nous ne pouvons pas y donner suite.

Motif :
{commentaire_du_validateur}

Nous vous remercions de votre compréhension.

Si vous pensez qu'il s'agit d'une erreur, vous pouvez nous contacter.

Cordialement,
L'équipe Sapin Solidaire
```

**Variables** :
- `{commentaire_du_validateur}` : Le motif de refus définitif saisi par le validateur

**Notes** :
- Le statut de la demande/enfant est "Refusé définitivement"
- La famille ne peut plus modifier cette demande

---

### 4. Confirmation de réception du cadeau

**Déclencheur** : Un organisateur envoie les confirmations aux familles dont les cadeaux ont été reçus

**Destinataire** : La famille

**Objet** : Bonne nouvelle ! Le cadeau de {prénom_enfant} est arrivé - Sapin Solidaire

**Contenu** :

```
Bonjour,

Nous avons le plaisir de vous informer que le cadeau pour {prénom_enfant} est arrivé !

Détails :
- Prénom : {prénom_enfant}
- Cadeau : {cadeau_demandé}
- Code de retrait : {code_4_lettres}

Vous pouvez venir chercher le cadeau à partir du {date_retrait} à l'adresse suivante :

{adresse_retrait}

Merci de vous munir de ce code lors du retrait.

À bientôt !

Cordialement,
L'équipe Sapin Solidaire
```

**Variables** :
- `{prénom_enfant}` : Prénom de l'enfant
- `{cadeau_demandé}` : Le cadeau demandé pour l'enfant
- `{code_4_lettres}` : Code unique à 4 lettres majuscules
- `{date_retrait}` : Date à partir de laquelle le cadeau peut être retiré (depuis la saison)
- `{adresse_retrait}` : Adresse de retrait (depuis la saison)

**Notes** :
- Envoyé en lot pour tous les enfants avec statut "Reçu"
- La date d'envoi est enregistrée dans `children.confirmation_email_sent_at`
- Un e-mail est envoyé par enfant (une famille avec plusieurs enfants reçoit plusieurs e-mails)

---

## Récapitulatif

| E-mail | Déclencheur | Statut associé |
|--------|-------------|----------------|
| Lien d'accès | Saisie e-mail famille | - |
| Demande de correction | Refus par validateur | `rejected` |
| Refus définitif | Refus définitif par validateur | `rejected_final` |
| Confirmation réception | Envoi par organisateur | `received` |

---

## Configuration technique

### Variables d'environnement

```env
# Limite de débit pour l'envoi d'e-mails (en secondes)
MAIL_RATE_LIMIT_SECONDS=5

# Configuration SMTP standard Laravel
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

### Paramètres du site

| Paramètre | Description |
|-----------|-------------|
| `reply_to_email` | Adresse de réponse pour tous les e-mails |
| `site_name` | Nom affiché dans les e-mails |

---

## Notes de développement

- Utiliser les classes Laravel Mailable pour chaque type d'e-mail
- Implémenter `ShouldQueue` pour l'envoi asynchrone
- Les templates Blade doivent être dans `resources/views/emails/`
- Prévoir une version texte brut en plus de la version HTML
