# Sapin solidaire

Ceci est une application web qui permet de gèrer la distribution de cadeaux aux familles aillant peu de moyens.
Elle est constituée de deux parties distinctes:

- Les familles remplissent leur besoin de cadeau. L'interface doit être le plus simple possible. Sera à la racine /
- Les staff qui gèrent les cadeaux. Elle sera sous /admin. Il y a aura des roles différents en fonction des staff.

L'interface pour les familles doit être optimisées pour les smartphones.

## Outils utilisés

- PHP 8.2
- MariaDB
- Envoie d'email

## Framework

Laravel (la version la plus simple)


## Workflow pour les familles

1. Le membre de la famille arrive sur la page principale `/`
1. Valide qu'une saison est active en fonction des dates de la saison.
1. Il devra entrer son email
1. Un email est envoyé à son email avec un lien sous `/cadeau/XXX/EMAIL`. `XXX` est un token qui permet de valider que l'email est bien correct. `EMAIL` est l'email de la famille utilsé pour la suite.
1. La famille clique sur le lien et arrive sur la bonne page. Le token est validé.
1. En grand sur la page, la famille doit accéder un par un les conditions suivantes:
	1. La famille ne peut pas demander des cadeaux plus de 3 années conséqutives
	1. La famille doit habiter dans le nord vaudois.
1. Une fois les conditions accéptée. Un formulaire est affiché pour demander toutes les informations nécessaires. L'email est affichée mais non modifiable. 
	1. Si l'email est trouvée dans la base de données, les informations de bases sont déjà remplies et modifiable.
	1. Les données des enfants sont affichées uniquement pour l'année en cours.
1. La famille remplis les informations de base: Nom, Prénom, Adresse, Code postal, Ville, NPA, Téléphone
1. La famille indique combient d'enfant doivent recevoir un cadeau
1. Pour chaque enfant, il faut demander: années de naissance, taille cm, prénom, le cadeau voulu
	1. Le champs cadeau est libre, mais une liste pré-existante permet de compléter le champ
	1. Si des chaussures sont demandée, un champ pointure apparait
1. Une fois tous les champs remplis, la famille peut valider sont entrée
1. Une validation est faite pour être sûr que tout est bon
	1. Le numéro de téléphone est Suisse et juste
	1. Recherche et validation de l'adresse via API
	1. Tous les champs ont bien été remplis
1. Les données de la familles sont sauvées en DB
1. Les données des enfants sont sauvée en DB, elles doivent être liées à la familles ainsi qu'à la saison en cours. Il ne peux y avoir qu'une demande par famille par saison. Si la famille a déjà remplis pour la saison, ils peuvent modifier leur choix. Pour les informations de famille ainsi que pour chaque enfant, il y a un status qui est mis sur **A Valider** lors de chaque modification. Un code à 4 lettres majuscules unique est créé pour chaque demande d'enfant.

## Workflow pour les staff

Laravel Starter kit propose une interface pour gérer les logins et le dashboard. Il sera utilisé.

### Inscription

Les staff iront sur la page /admin. S'ils n'ont pas encore de compte, il peuvent s'inscrire. Ils auront le role **Visiteur**, qui n'a aucun droit. Un **Admin** devra lui changer de role.
S'il n'y a aucun compte, le tout prermier compte sera **Admin**.

### Nouvelle saison

L'**Admin** peut créer ou modifier les saisons.
La saison a comme champ: Nom, Date de début et de fin, text d'introduction, date depuis laquelle les familles ne peuvent plus modifier leur demande. Les dates de chaque saison ne doivent pas se chevaucher.

### Validation des familles

Les staff avec le role **Validateur** doivent valider manuellement chaque famille et chaque enfant.

Sous la page /admin/validation on verra le nombre de famille / enfant à valider. La date de changement de status est sauvée lors de l'action de changement de status.
Elle affiche une famille avec tous les enfants. Il y a un bouton **Valider**, **Refuser** ou **Refuser définitivement** pour la famille ainsi que pour chaque enfant.
Si l'on presse sur **Valider**, le status est changé pour **Validé**. 
Si l'on press sur **Refuser**:
1. La page va demander un commentaire de la raison du refus.
1. Le status est changé en base de donnée
1. Un mail est envoyé à la famille pour demander à corriger les informations avec un lien.

Si l'on press sur **Refuser définitivement**:
1. La page va demander un commentaire de la raison du refus.
1. Le status est changé en base de donnée
1. Un mail est envoyé à la famille pour informer du refus.

### Génération d'étiquette

Une interface pour le role **Organisateur** permet d'imprimer les étiquettes. On va générer un PDF multi-page.
Sur un format A4, on aimerait 8 étiquettes par page 2x4.
Pour chaque étiquette on va trouver chaqune enfant qui a été validé. Trié par ordre de date de validation.
Une fois le PDF généré, le status de l'enfant passe de **Validé** à **Imprimé**

Sur l'étiquette il doit y avoir les champs: Prénom de l'enfant, cadeau voulu, taille, le code unique à 4 lettres majuscule,l'age de l'enfant, la pointure si besoin.

### Validation des cadeaux

Il faut pouvoir valider que le cadeau demandé pour l'enfant est bien reçu.
Une interface pour le role **Organisateur** permet de le valider.

Il y a la liste des enfants avec le status **Imprimé** avec le prénom, le code à 4 lettres. Il est possible de cliquer dessus, on verra tous le details du cadeau et les informations de l'enfant. (Mais pas les données personnelles de la famille).
Il y a un bouton pour faire passer le status de la demande de **Imprimé** à **Reçu**.

### Donner le cadeau

Un staff avec le role **Accueil** aura accès à une page qui permet de valider que l'enfant à reçu son cadeau.
Il aura une liste avec les enfants qui ont le status **Reçu**. On affiche par ordre alphabetique du prénom, les prénoms de l'enfant, l'age et le nom de famille.
On pourra cliquer sur la ligne et valider que l'enfant a reçu le cadeau. Le status va passer au status **Donné**.
