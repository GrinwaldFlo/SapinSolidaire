# Sapin solidaire

## Présentation générale

Sapin solidaire est une application web destinée à gérer la distribution de cadeaux aux familles ayant peu de moyens.

Elle est composée de deux parties distinctes :

- **Interface familiale** : Les familles remplissent leur demande de cadeaux. L'interface doit être la plus simple possible et sera accessible à la racine `/`. Elle doit être optimisée pour les appareils mobiles.
- **Interface d'administration** : Accessible via `/admin`, elle permet aux membres du personnel de gérer les cadeaux et les demandes. Les utilisateurs administrateurs auront des rôles distincts avec des permissions différentes.

### Principes généraux

- Toutes les interfaces destinées aux familles ainsi que les e-mails doivent utiliser un langage très simple et accessible.
- Aucun bouton de connexion ou d'inscription ne doit apparaître sur la page d'accueil (destinée aux familles). Ces boutons seront exclusivement sur la page `/admin`.

## Configuration technique

### Stack technologique

- **PHP** : 8.2 ou supérieur
- **Base de données** : MariaDB
- **Gestion des e-mails** : Système d'envoi asynchrone (mise en queue)

### Framework et outils

- **Framework** : Laravel (version minimale). Le starter kit est fourni et peut être modifié selon les besoins.
- **Internationalisation** : Toutes les interfaces doivent être en français uniquement.
- **Code** : Tout le code source (variables, commentaires, etc.) doit être écrit en anglais.

## Workflow famille

### Processus de demande de cadeau

1. Le membre de la famille accède à la page d'accueil `/`
2. Le système valide qu'une saison est actuellement active selon les dates définies
3. L'utilisateur entre son adresse e-mail
4. Un e-mail est envoyé contenant un lien sécurisé au format `/cadeau/XXX/EMAIL`
   - `XXX` : token unique permettant de valider l'authenticité de l'adresse e-mail
   - `EMAIL` : l'adresse e-mail de la famille utilisée par la suite
5. La famille clique sur le lien reçu et accède au formulaire de demande. Le token est validé
6. **Vérification des conditions d'éligibilité** : Les conditions suivantes s'affichent une à une et doivent être acceptées
   - La famille ne peut pas demander des cadeaux plus de 3 années consécutives
   - La famille doit habiter dans le Nord Vaudois
7. Une fois les conditions acceptées, le formulaire principal s'affiche
   - L'adresse e-mail est affichée mais non modifiable
   - Si l'e-mail existe dans la base de données, les informations de base sont préremplies et restent modifiables
   - Seules les données des enfants pour l'année en cours sont affichées

### Formulaire d'information familiale

8. La famille complète les informations de base : Nom, Prénom, Adresse, Code postal, Ville, NPA, Téléphone
9. La famille indique le nombre d'enfants qui doivent recevoir un cadeau
10. Pour chaque enfant, les informations suivantes sont demandées :
    - Année de naissance
    - Taille (en cm)
    - Prénom
    - Cadeau souhaité (liste prédéfinie avec autocomplétion, ou saisie libre)
    - Pointure (si des chaussures sont demandées, ce champ s'affiche automatiquement)

### Validation et sauvegarde

11. Une fois tous les champs complétés, la famille peut soumettre sa demande
12. Le système effectue une validation complète :
    - Vérification que le numéro de téléphone est valide et au format suisse
    - Validation et géolocalisation de l'adresse via API externe
    - Vérification que tous les champs obligatoires sont remplis
13. Les données de la famille sont sauvegardées en base de données
14. Les données de chaque enfant sont sauvegardées avec :
    - Lien vers la famille et à la saison en cours
    - **Limitation** : Une seule demande par famille par saison
    - **Modification** : Si la famille a déjà soumis une demande pour la saison, elle peut modifier ses choix
    - **Statut initial** : "À valider" (pour les informations familiales et chaque enfant)
    - **Code unique** : Un code de 4 lettres majuscules est généré pour chaque demande d'enfant

## Workflow personnel (staff)

### Principes généraux

- La gestion des utilisateurs personnel est basée sur le système d'authentification fourni par Laravel Starter Kit
- Toutes les données affichées sont liées à la saison en cours, sauf indication contraire
- Par défaut, toutes les pages n'affichent que les données de la saison active

### Gestion des rôles et permissions

**Rôles disponibles**
- **Visiteur** : Aucune permission (rôle par défaut pour les nouvelles inscriptions)
- **Validateur** : Peut valider les demandes familiales et les demandes d'enfants
- **Organisateur** : Peut imprimer les étiquettes, valider les cadeaux reçus et gérer les listes
- **Accueil** : Peut enregistrer la remise des cadeaux
- **Admin** : Accès complet, gestion des saisons et des rôles

### Inscription du personnel

- Les membres du personnel se rendent sur `/admin` pour s'inscrire
- Les nouveaux utilisateurs reçoivent le rôle **Visiteur** par défaut (aucune permission)
- Un **Admin** doit modifier manuellement le rôle des nouveaux utilisateurs
- **Exception** : Le tout premier compte créé devient automatiquement **Admin**

### Gestion des saisons

**Accessible par** : Admin

L'interface permet de créer et modifier les saisons.

**Champs d'une saison**
- Nom (obligatoire)
- Date de début (obligatoire)
- Date de fin (obligatoire)
- Texte d'introduction (affiché aux familles)
- Date limite de modification (après laquelle les familles ne peuvent plus modifier leur demande)
- Date depuis laquelle le cadeau pour être cherché
- Adresse où venir chercher le cadeau

**Règles**
- Les dates des saisons ne doivent pas se chevaucher
- Une seule saison peut être active à la fois

### Validation des demandes familiales

**Accessible par** : Validateur

**Page : `/admin/validation`**

L'interface affiche :
- Un résumé du nombre de familles et d'enfants en attente de validation
- La prochaine famille avec tous ses enfants associés à valider
- La date du dernier changement de statut est enregistrée pour chaque action
- Une fois l'action effectuée, affiche la prochaine famille à valider

**Actions disponibles pour la famille et chaque enfant**

#### Valider
- Change le statut à **Validé**

#### Refuser (correction demandée)
1. L'interface demande un commentaire expliquant le motif du refus
2. Le statut change à **Refusé** en base de données
3. Un e-mail est envoyé à la famille l'invitant à corriger les informations avec un lien pour remodifier

#### Refuser définitivement
1. L'interface demande un commentaire expliquant le motif du refus
2. Le statut change à **Refusé définitivement** en base de données
3. Un e-mail est envoyé à la famille pour l'informer du refus définitif

### Génération des étiquettes

**Accessible par** : Organisateur

L'interface génère un PDF multi-page contenant les étiquettes de cadeaux.

**Format d'impression**
- Format A4
- 8 étiquettes par page (disposition 2×4)
- Les enfants sont triés par date de validation

**Contenu de chaque étiquette**
- Prénom de l'enfant
- Cadeau demandé
- Taille
- Code unique à 4 lettres majuscules
- Âge de l'enfant
- Pointure (si applicable)

**Changement de statut**
- Une fois le PDF généré, le statut de l'enfant change de **Validé** à **Imprimé**

### Validation de la réception des cadeaux

**Accessible par** : Accueil

L'interface permet de confirmer qu'un cadeau a été reçu.

**Affichage**
- Liste des enfants avec le statut **Imprimé**
- Affiche le prénom et le code unique à 4 lettres
- Clic possible sur chaque enfant pour voir tous les détails du cadeau et ses informations
- **Important** : Les données personnelles de la famille ne sont pas affichées

**Action**
- Un bouton permet de passer le statut de **Imprimé** à **Reçu**

### Remise des cadeaux

**Accessible par** : Accueil

Cette interface permet d'enregistrer la remise effective des cadeaux aux enfants.

**Affichage**
- Liste des enfants avec le statut **Reçu**
- Tri alphabétique par prénom
- Affichage : Prénom, Âge, Nom de famille

**Action**
- Clic sur une ligne pour afficher le détail de l'enfant
- Clic pour valider la réception du cadeau
- Le statut change de **Reçu** à **Donné**

### Suivi des enfants

**Accessible par** : Organisateur

L'interface affiche une liste complète de tous les enfants pour surveiller leurs statuts.

**Fonctionnalités**
- Par défaut, affiche la saison en cours
- Permet de sélectionner une saison précédente
- Filtrage par statut pour afficher un workflow spécifique
- Bouton **Modifier** pour chaque ligne permettant de corriger les informations

### Envoi des confirmations de réception

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

### Gestion des familles

**Accessible par** : Organisateur

L'interface permet de lister et gérer toutes les familles.

**Fonctionnalités**
- Filtrage par saison
- Affichage du nombre d'enfants demandés par la famille pour chaque saison
- Permettre la modification des données
