# PharmaLomé — MedExpress TOGO

## 1. Titre, description courte et liens

PharmaLomé (MedExpress TOGO) est une plateforme web Laravel de pharmacie en ligne. Une gateway présente le site et orchestre trois services métier indépendants pour les comptes, le catalogue et les commandes.

| Ressource | Lien |
|---|---|
| Plateforme hébergée | À renseigner : aucune URL de production n'est configurée dans le dépôt. |
| Dépôt source | À renseigner : aucun remote Git n'est configuré dans le checkout actuel. |

## 2. Contexte du brief et objectifs

Le projet répond au besoin d'une pharmacie en ligne au Togo où les clients peuvent découvrir le catalogue, constituer un panier et transmettre une ordonnance lorsque nécessaire. Les fonctions professionnelles sont séparées des fonctions publiques et contrôlées par rôle.

Objectifs :

- permettre l'inscription, la connexion, la modification du profil, le changement et la réinitialisation du mot de passe des clients;
- réserver la création des comptes administrateur, pharmacien et livreur à l'administrateur;
- vérifier les informations professionnelles d'un pharmacien avant de lui ouvrir les outils métier;
- relier les espaces à des données gérées par des services et bases distincts;
- fournir des fonctions de gestion du catalogue, des commandes, des ordonnances et des missions de livraison;
- documenter les limites actuelles, en particulier la décrémentation du stock et le paiement.

## 3. Fonctionnalités par rôle

| Rôle | Fonctionnalités actuellement présentes |
|---|---|
| Visiteur | Consulter l'accueil, les statistiques publiques, le catalogue et les fiches médicaments; utiliser la recherche et les filtres; consulter À propos et Contact; s'inscrire, se connecter ou demander un lien de réinitialisation. |
| Client | Modifier son profil et son mot de passe, se déconnecter, gérer le panier dans le navigateur, passer une commande avec retrait ou livraison, joindre un fichier d'ordonnance, consulter ses commandes. |
| Pharmacien | Renseigner qualification et numéro d'ordre; après vérification par l'administrateur, consulter son tableau de bord, les ordonnances et leurs fichiers, valider ou refuser une ordonnance avec motif, consulter les stocks et alertes, réapprovisionner. |
| Administrateur | Consulter les indicateurs, lister/rechercher/filtrer les utilisateurs, créer des comptes professionnels, renvoyer une invitation, modifier rôle et activation, vérifier un pharmacien, gérer catégories et médicaments, consulter et mettre à jour les commandes, affecter ou retirer un livreur. |
| Livreur | Consulter ses missions affectées et terminer une mission de livraison. L'accès est réservé à un compte livreur actif. |

L'inscription publique affecte uniquement le rôle `client`. Le rôle administrateur, pharmacien ou livreur est créé par un administrateur (le premier administrateur par une commande console). Les routes pharmacien sont aussi accessibles au rôle `admin`; le rôle pharmacien doit avoir `pharmacien_verifie=true`.

## 4. Architecture micro-services

### Schéma d'ensemble

```text
Navigateur
    | HTTP
    v
Gateway :8080 -- REST / X-Internal-Key --> user-service :8001  --> user_db
    |                 |------------------> catalog-service :8002 -> catalog_db
    |                 `------------------> order-service :8003  --> order_db
    `-- session web        (réseau Docker Compose privé)
```

Les quatre vues UML détaillées sont présentées dans la section 6. Le navigateur accède à la gateway; celle-ci communique avec les services métier par HTTP REST sur le réseau Docker Compose.

### Services et ports

| Service | Port publié en local | Responsabilité | Base |
|---|---:|---|---|
| `gateway` | 8080 | Pages Blade, session web, orchestration, authentification web et appels interservices. | Aucune base métier définie par Compose; session/cache fichier dans la configuration Compose. |
| `user-service` | 8001 | Comptes, rôles, jetons Sanctum, profils, invitations et réinitialisation du mot de passe. | PostgreSQL `user_db`. |
| `catalog-service` | 8002 | Catégories, médicaments, promotions, contrôle de disponibilité, stock et alertes. | PostgreSQL `catalog_db`. |
| `order-service` | 8003 | Commandes, lignes, ordonnances, paiements simulés, notifications et missions livreur. | PostgreSQL `order_db`. |
| `postgres` | 5432 | Conteneur PostgreSQL commun hébergeant les trois bases métier. | Volume Docker `postgres_data`. |

### Communication et authentification

- La gateway appelle les services avec HTTP REST et des URLs configurées (`USER_SERVICE_URL`, `CATALOG_SERVICE_URL`, `ORDER_SERVICE_URL`). Dans Compose, les hôtes sont `user-service`, `catalog-service` et `order-service`.
- Les routes internes du catalogue et du service commandes vérifient l'en-tête `X-Internal-Key`. Les routes administratives des utilisateurs demandent également un jeton Sanctum et cette clé.
- `user-service` émet les jetons personnels Sanctum. La gateway conserve le jeton et le profil dans sa session, puis vérifie les pages protégées auprès de `GET /api/me` avec le Bearer token.
- Les associations entre les bases passent par des identifiants (`user_id`, `livreur_id`, `pharmacien_id`, `medicament_id`); aucune clé étrangère SQL ne relie deux services.

La séparation des domaines rend chaque service maître de ses données et de ses migrations. Elle évite un schéma métier monolithique, mais introduit des appels réseau et ne fournit pas de transaction atomique globale entre catalogue et commandes.

## 5. Technologies

| Technologie | Version déclarée ou configurée | Usage |
|---|---|---|
| PHP | `^8.3` dans les manifestes Composer; image Docker `php:8.4-apache` | Exécution Laravel et Apache. |
| Laravel Framework | `^13.17` dans les quatre manifestes Composer | Routage, contrôleurs, migrations, vues et ORM. |
| Laravel Sanctum | `^4.4` | Jetons d'accès API du service utilisateurs. |
| PostgreSQL | Image `postgres:16-alpine` | Persistance relationnelle des trois services métier. |
| Docker Compose | Fichier `docker-compose.yml` | Construction et orchestration locale. |
| Apache | Image officielle PHP-Apache | Serveur web des applications conteneurisées. |
| PHPUnit | `^12.5.12` dans les manifestes Composer | Tests unitaires et fonctionnels Laravel. |
| Redis | Aucun conteneur Redis dans Compose | Certaines configurations Laravel exposent des variables Redis, mais Compose utilise le cache/fichier et ne démarre pas Redis. |

Les versions résolues doivent être confirmées dans chaque `composer.lock`; les contraintes ci-dessus sont celles des manifestes.

## 6. Conception UML

Les quatre sources PlantUML sont autonomes (sans `!include`). Les images PNG référencées sont des emplacements réservés : elles seront ajoutées après export des fichiers `.puml`.

### Cas d'utilisation

![Diagramme des cas d'utilisation](docs/05-uml/images/cas-utilisation.png)

[Source PlantUML — cas-utilisation.puml](docs/05-uml/cas-utilisation.puml)

### Classes

![Diagramme de classes](docs/05-uml/images/classes.png)

[Source PlantUML — classes.puml](docs/05-uml/classes.puml)

### Séquence de commande avec ordonnance

![Diagramme de séquence de commande](docs/05-uml/images/sequence-commande.png)

[Source PlantUML — sequence-commande.puml](docs/05-uml/sequence-commande.puml)

### Activité de commande

![Diagramme d'activité de commande](docs/05-uml/images/activite-commande.png)

[Source PlantUML — activite-commande.puml](docs/05-uml/activite-commande.puml)

Les statuts et appels montrés correspondent au code actuel. En particulier, le diagramme de séquence indique que l'endpoint de baisse de stock existe, mais n'est pas appelé par la création ou la validation d'une commande.

## 7. Base de données et normalisation

Les trois bases métier sont séparées. Les tableaux décrivent les tables fonctionnelles définies par les migrations; Laravel ajoute aussi des tables techniques (migrations, cache, jobs, jetons, sessions et réinitialisation du mot de passe selon le service). Des migrations scaffold créent également des tables `users` dans d'autres services; elles ne remplacent pas `user-service` comme propriétaire fonctionnel des comptes.

| Base / table | Clé et dépendances fonctionnelles principales | 1FN | 2FN | 3FN |
|---|---|---|---|---|
| `user_db.users` | PK `id`, contrainte unique `email`; `id → name, nom, prenom, email, password, telephone, adresse, role, actif, qualification, numero_ordre, pharmacien_verifie`. | Oui : colonnes scalaires. | Oui : clé primaire simple. | `name` est un nom d'affichage maintenu à partir de `nom` et `prenom`; sa duplication applicative doit être synchronisée. Le rôle est un texte, sans table séparée. |
| `catalog_db.categories` | PK `id`, unique `libelle`; `id → libelle, timestamps`. | Oui. | Oui : clé simple. | Oui : aucun attribut non-clé ne détermine un autre attribut. |
| `catalog_db.medicaments` | PK `id`; `id → categorie_id, nom, dosage, laboratoire, description, prix, quantite_stock, seuil_alerte, date_peremption, ordonnance_requise, photo, promotion, prix_promotion`. FK locale `categorie_id`. | Oui : valeurs unitaires. | Oui : clé simple. | Oui : les données de catégorie sont référencées, pas recopiées dans le produit. |
| `order_db.commandes` | PK `id`; `id → user_id, livreur_id, statut, mode_reception, adresse_livraison, sous_total, frais_livraison, total, timestamps`. `user_id` et `livreur_id` sont des identifiants interservices. | Oui. | Oui : clé simple. | Oui pour les dépendances du schéma; les totaux stockés sont une dénormalisation assumée. |
| `order_db.ligne_commandes` | PK `id`; `id → commande_id, medicament_id, nom_medicament, prix_unitaire, quantite`. FK locale `commande_id`; `medicament_id` référence le catalogue par identifiant. | Oui. | Oui : PK simple. | Le nom et le prix recopiés gardent l'instantané commercial de la commande; cette copie est assumée. |
| `order_db.ordonnances` | PK `id`; `id → commande_id, fichier, statut, motif_refus, pharmacien_id, validee_le`. FK locale `commande_id`; `pharmacien_id` est interservice. | Oui. | Oui : clé simple. | Oui au niveau des colonnes; le pharmacien est identifié sans FK vers `user_db`. |
| `order_db.paiements` | PK `id`; `id → commande_id, mode, montant, statut, reference, timestamps`. FK locale `commande_id`. | Oui. | Oui : clé simple. | Oui selon le schéma; l'intégration de paiement est simulée. |
| `order_db.notifications` | PK `id`; `id → user_id, message, lu, timestamps`. `user_id` référence le service utilisateurs par identifiant. | Oui. | Oui : clé simple. | Oui pour les champs enregistrés; aucune FK inter-base. |

**Formes normales.** Pour ces tables, une clé primaire simple rend la 2FN satisfaite en l'absence de dépendance partielle sur une clé composée. La 3FN est respectée lorsqu'aucun attribut non-clé ne détermine un autre attribut non-clé; les exceptions de stockage ci-dessous sont explicites.

**Trois dénormalisations / choix assumés :**

1. `ligne_commandes.nom_medicament` et `prix_unitaire` conservent une copie du nom et du prix au moment de la commande.
2. `commandes.sous_total`, `frais_livraison` et `total` sont stockés au lieu d'être recalculés à chaque lecture.
3. Le rôle est stocké dans une simple colonne `users.role`, plutôt que dans un modèle de rôles et une relation.

**Limite de péremption :** `medicaments` contient une seule `date_peremption`; aucune table de lots ne permet de gérer plusieurs dates et quantités par produit.

## 8. Structure du dépôt

```text
.
├── .env.example                 # Variables racine nécessaires à Docker Compose
├── .gitignore                   # Exclut .env, vendor, node_modules et storage
├── docker-compose.yml            # Gateway, 3 services métier, PostgreSQL
├── docker/
│   ├── Dockerfile.gateway        # Image PHP-Apache de la gateway
│   ├── Dockerfile.service        # Image commune des services
│   └── postgres/init/            # Création des trois bases sur volume neuf
├── gateway/                      # Site Blade, authentification web, orchestration
├── user-service/                 # Comptes, rôles, tokens et invitations
├── catalog-service/              # Catégories, médicaments et stock
├── order-service/                # Commandes, ordonnances et livraison
└── docs/
    ├── 04-fiche-technique.md     # Fiche des statistiques publiques
    ├── 05-uml/
    │   ├── *.puml                # Quatre diagrammes PlantUML
    │   └── images/.gitkeep       # Emplacement réservé aux exports PNG
    ├── 06-guide-utilisation.md   # Guide d'utilisation
    ├── architecture.md           # Notes d'architecture
    └── usage.md                  # Parcours d'utilisation
```

Chaque service Laravel contient ses contrôleurs et modèles dans `app/`, ses routes dans `routes/`, ses migrations dans `database/migrations/`, ses tests dans `tests/`, son `composer.json` et son `.env.example`.

## 9. Prérequis et installation locale

### Prérequis

- Windows 10/11 et Docker Desktop avec le moteur démarré, ou une installation compatible Docker Compose.
- Git.
- Ports locaux libres : 8080, 8001, 8002, 8003 et 5432.

### Cloner le projet et créer les configurations locales

Dans PowerShell, remplacer `<URL_DU_DEPOT>` par l'URL réelle du dépôt :

```powershell
git clone <URL_DU_DEPOT>
Set-Location .\pharmalome
```

Créer chaque `.env` seulement s'il n'existe pas, afin de préserver les configurations déjà présentes :

```powershell
if (-not (Test-Path .\.env)) { Copy-Item .env.example .env }
if (-not (Test-Path .\gateway\.env)) { Copy-Item .\gateway\.env.example .\gateway\.env }
if (-not (Test-Path .\user-service\.env)) { Copy-Item .\user-service\.env.example .\user-service\.env }
if (-not (Test-Path .\catalog-service\.env)) { Copy-Item .\catalog-service\.env.example .\catalog-service\.env }
if (-not (Test-Path .\order-service\.env)) { Copy-Item .\order-service\.env.example .\order-service\.env }
```

Compléter le `.env` racine : `POSTGRES_PASSWORD`, `INTERNAL_KEY`, `APP_KEY_GATEWAY`, `APP_KEY_USER_SERVICE`, `APP_KEY_CATALOG_SERVICE` et `APP_KEY_ORDER_SERVICE`. Les clés Laravel doivent être distinctes et au format `base64:` + Base64 de 32 octets aléatoires. Exemple de génération d'une valeur dans PowerShell :

```powershell
$bytes = [Security.Cryptography.RandomNumberGenerator]::GetBytes(32)
'base64:' + [Convert]::ToBase64String($bytes)
```

Générer une valeur distincte pour chaque clé Laravel et une valeur forte pour le secret interne et le mot de passe PostgreSQL. Ne pas inscrire les secrets dans Git. Configurer les paramètres SMTP de `gateway/.env` et `user-service/.env` si l'envoi réel des messages est nécessaire; le transport par défaut `log` écrit dans les journaux.

### Construire et initialiser

```powershell
docker compose up -d --build
docker compose ps
```

Au premier démarrage d'un volume PostgreSQL neuf, `docker/postgres/init/01-create-dbs.sql` crée les bases `user_db`, `catalog_db` et `order_db`. Appliquer les migrations :

```powershell
docker compose exec user-service php artisan migrate --force
docker compose exec catalog-service php artisan migrate --force
docker compose exec order-service php artisan migrate --force
```

Créer le premier administrateur (saisie interactive et mot de passe masqué) :

```powershell
docker compose exec user-service php artisan users:create-admin
```

| Composant local | Adresse |
|---|---|
| Site et gateway | http://localhost:8080 |
| Santé gateway | http://localhost:8080/api/health |
| user-service | http://localhost:8001 |
| catalog-service | http://localhost:8002 |
| order-service | http://localhost:8003 |
| PostgreSQL | `localhost:5432` |

Le script d'initialisation SQL ne s'exécute que pour un volume de données neuf. Si un volume PostgreSQL existe déjà, son mot de passe reste celui enregistré lors de son initialisation; le changer dans l'environnement sans mettre à jour le volume peut rompre la connexion. Les ports publiés des services sont destinés au développement local, pas à une exposition directe en production.

## 10. Variables d'environnement

| Variable | Fichier / source | Fonction |
|---|---|---|
| `POSTGRES_PASSWORD` | `.env` racine | Mot de passe PostgreSQL injecté dans la base et les services. |
| `INTERNAL_KEY` | `.env` racine, propagé par Compose | Secret partagé vérifié par le middleware `X-Internal-Key`. |
| `APP_KEY_GATEWAY` | `.env` racine | Clé Laravel de la gateway. |
| `APP_KEY_USER_SERVICE` | `.env` racine | Clé Laravel de `user-service`. |
| `APP_KEY_CATALOG_SERVICE` | `.env` racine | Clé Laravel de `catalog-service`. |
| `APP_KEY_ORDER_SERVICE` | `.env` racine | Clé Laravel de `order-service`. |
| `CONTACT_EMAIL` | `.env` racine / gateway | Destinataire du formulaire de contact; défaut `contact@medexpress.tg`. |
| `USER_SERVICE_URL` | `gateway/.env` | URL du service utilisateurs; Compose la remplace par `http://user-service`. |
| `CATALOG_SERVICE_URL` | `gateway/.env` | URL du catalogue; Compose la remplace par `http://catalog-service`. |
| `ORDER_SERVICE_URL` | `gateway/.env` | URL des commandes; Compose la remplace par `http://order-service`. |
| `GATEWAY_URL` | `user-service/.env` | URL de gateway utilisée par les liens d'invitation et de récupération. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `.env` du service / environnement Compose | Configuration de connexion SQL; Compose configure les trois DB métier sur PostgreSQL. |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | `.env` / Compose | Environnement et URL Laravel; `APP_DEBUG` doit être désactivé en production. |
| `SESSION_DRIVER`, `CACHE_STORE` | `.env` / Compose | Mécanismes de session/cache; la gateway utilise `file` dans Compose. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | `.env` gateway et user-service | Transport des contacts, invitations et réinitialisations; `log` n'envoie pas de courrier. |

Les fichiers `.env` locaux contiennent les secrets et ne sont pas versionnés. Les fichiers `.env.example` sont des modèles sans valeurs secrètes.

## 11. Comptes de test

Aucun mot de passe prédéfini fiable n'est livré ou documenté. Le seeder de démonstration génère des mots de passe aléatoires; ne pas l'utiliser comme source d'identifiants de connexion connus.

| Rôle | Création / accès |
|---|---|
| Client | Créer un compte avec le formulaire public **Inscription**; mot de passe d'au moins 8 caractères et confirmation. |
| Administrateur initial | Exécuter `docker compose exec user-service php artisan users:create-admin`; cette commande échoue si un admin existe déjà. |
| Pharmacien | Compte créé et invité par l'administrateur; compléter les informations professionnelles puis attendre la vérification admin. |
| Livreur | Compte créé et invité par l'administrateur; n'accède qu'aux missions affectées à son identifiant. |

Ne pas publier d'adresse ou de mot de passe de compte réel dans ce README.

## 12. Guide d'utilisation

### Inscription et commande avec ordonnance

1. Ouvrir le site, créer un compte client avec nom, prénom, email, téléphone et mot de passe (8 caractères minimum).
2. Après l'inscription, la gateway ouvre une session et affiche l'espace compte.
3. Rechercher un médicament, consulter sa fiche et l'ajouter au panier; le panier est conservé côté navigateur.
4. Se connecter si nécessaire et ouvrir le checkout. Choisir `retrait` ou `livraison`; saisir une adresse pour la livraison.
5. Si un produit exige une ordonnance, joindre un fichier JPG/JPEG/PNG/PDF de 4 Mo maximum.
6. La gateway demande au catalogue de vérifier les identifiants, les quantités disponibles, le nom, le prix courant et le besoin d'ordonnance. Elle utilise ensuite les informations retournées pour envoyer la commande à `order-service`.
7. Consulter **Mes commandes** pour voir les commandes et leur statut.

### Validation par le pharmacien

1. L'administrateur crée un compte pharmacien depuis la gestion des utilisateurs et envoie l'invitation.
2. Le pharmacien renseigne sa qualification et son numéro d'ordre dans son profil.
3. L'administrateur vérifie les informations avec l'action `pharmacien_verifie`; les modifications ultérieures des informations professionnelles retirent cette validation.
4. Le pharmacien vérifié consulte les ordonnances et le document associé.
5. La validation place l'ordonnance et la commande au statut `validee`; le refus avec un motif d'au moins cinq caractères place les deux au statut `refusee`.

### Stock, livraison et retrait

1. L'administrateur crée une catégorie puis les médicaments, avec prix, quantité, seuil d'alerte, date de péremption et exigence d'ordonnance.
2. Le pharmacien consulte le stock et les alertes, puis peut réapprovisionner une quantité positive.
3. Le checkout vérifie la disponibilité, mais ne réserve ni ne décrémente le stock.
4. Pour une livraison, l'administrateur peut affecter un livreur seulement lorsque la commande est `en_livraison`; le compte livreur termine ensuite la mission et le service écrit `livree`.
5. Pour un retrait, `retiree` est un statut reconnu par les statistiques, mais aucune action spécifique de confirmation de retrait n'est présente; le statut peut être modifié via la mise à jour générique de commande.

Les statuts commande sont stockés dans une chaîne. L'interface d'administration accepte `en_attente`, `ordonnance_a_valider`, `validee`, `en_preparation`, `prete`, `en_livraison`, `livree`, `retiree`, `refusee` et `annulee`. Le service commandes ne contraint pas les transitions entre ces valeurs.

## 13. API

Les chemins sont préfixés par `/api`. Le tableau répertorie les routes déclarées dans les `routes/api.php`; les routes web de la gateway utilisent également une session et des middlewares de rôle.

| Service | Endpoints | Accès / utilité |
|---|---|---|
| Gateway | `GET /health`, `GET /me`, `GET /stats-publiques` | État technique, réponse `/me` non authentifiée de cette route API, statistiques publiques. |
| Users | `POST /register`, `POST /login`, `POST /password/email`, `POST /password/reset`, `POST /logout` | Inscription client, login, reset et logout; les routes sensibles sont limitées en fréquence; logout exige Sanctum. |
| Users | `GET /me`, `PATCH /me`, `PATCH /me/password` | Profil et mot de passe; jeton Sanctum requis. |
| Users | `GET /users`, `POST /users`, `GET /users/{user}`, `PATCH /users/{user}`, `DELETE /users/{user}`, `POST /users/{user}/invitation` | Gestion et invitations; Sanctum + `X-Internal-Key`, avec contrôle admin dans le contrôleur. |
| Users | `GET /health`, `GET /public/stats` | Santé (route protégée) et nombre de clients actifs (clé interne). |
| Catalog | `GET /categories`, `POST /categories`, `PUT /categories/{category}`, `DELETE /categories/{category}` | Lire et gérer les catégories; clé interne requise. |
| Catalog | `GET /medicaments`, `GET /medicaments/{medicament}`, `POST /medicaments`, `PUT /medicaments/{medicament}`, `DELETE /medicaments/{medicament}` | Consulter et gérer le catalogue; clé interne requise. |
| Catalog | `POST /medicaments/verifier`, `POST /medicaments/{medicament}/stock/diminuer`, `POST /medicaments/{medicament}/stock/reapprovisionner`, `GET /alertes` | Vérification du panier, décrément, réapprovisionnement et alertes; clé interne requise. |
| Catalog | `GET /health`, `GET /public/stats` | Santé et statistiques du catalogue; clé interne requise. |
| Orders | `GET /commandes`, `POST /commandes`, `GET /commandes/{commande}`, `PATCH /commandes/{commande}/statut`, `PATCH /commandes/{commande}/livreur`, `PATCH /commandes/{commande}/annuler` | Consulter, créer et mettre à jour les commandes; clé interne requise. |
| Orders | `GET /livreur/commandes`, `PATCH /livreur/commandes/{commande}/statut` | Missions et clôture de mission; Bearer token contrôlé auprès de `user-service` et clé interne. |
| Orders | `GET /ordonnances`, `GET /ordonnances/{ordonnance}`, `GET /ordonnances/{ordonnance}/fichier`, `PATCH /ordonnances/{ordonnance}/valider`, `PATCH /ordonnances/{ordonnance}/refuser` | Consultation et traitement des ordonnances; clé interne requise. |
| Orders | `POST /commandes/{commande}/paiement`, `GET /statistiques`, `GET /notifications`, `PATCH /notifications/lu` | Paiement simulé, statistiques et notifications; clé interne requise. |
| Orders | `GET /health`, `GET /public/stats` | Santé et statistiques des commandes; clé interne requise. |

La gateway expose aussi des routes web telles que `/catalogue`, `/checkout`, `/commandes`, `/compte`, `/pharmacien`, `/livreur` et `/admin`. Elles sont définies dans `gateway/routes/web.php`, et protégées selon le parcours par session, rôle et vérification pharmacien.

## 14. Tests

Chaque service a un jeu de tests Laravel indépendant. Lancer une commande à la fois depuis la racine, avec Docker Compose démarré :

```powershell
docker compose exec gateway php artisan test
```

```powershell
docker compose exec user-service php artisan test
```

```powershell
docker compose exec catalog-service php artisan test
```

```powershell
docker compose exec order-service php artisan test
```

Ces tests de service ne remplacent pas un test end-to-end du parcours complet avec base de test et services disponibles.

## 15. Déploiement sur un hébergeur gratuit

Aucun hébergeur ni URL de production n'est configuré dans le dépôt. Vérifier les quotas et la prise en charge de Docker, PHP 8.4, PostgreSQL 16, des quatre conteneurs et du stockage persistant avant de choisir une offre.

1. Créer un environnement de production et une persistance PostgreSQL; les services utilisent `user_db`, `catalog_db` et `order_db`.
2. Construire/publier les images et configurer leurs URLs internes sur un réseau privé.
3. Définir les secrets depuis le gestionnaire de secrets de la plateforme : mot de passe DB, clé interne et quatre clés Laravel distinctes.
4. Définir `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, les URLs de service, `GATEWAY_URL`, le transport SMTP et `CONTACT_EMAIL`.
5. N'exposer publiquement que la gateway avec HTTPS; ne pas publier les ports PostgreSQL et des services métier.
6. Exécuter les migrations après disponibilité des bases, puis créer l'administrateur via un accès console contrôlé.
7. Configurer sauvegardes, journaux, alertes et stockage persistant privé des fichiers d'ordonnance.

Les offres gratuites peuvent mettre les conteneurs en veille (démarrage à froid), limiter CPU, mémoire ou connexions, et fournir un disque éphémère. Un disque éphémère ne convient pas aux fichiers d'ordonnance ni à une base durable. Compose utilise des volumes locaux, à adapter aux mécanismes de stockage de l'hébergeur.

## 16. Sécurité

- `.gitignore` exclut `.env`, les répertoires `vendor`, `node_modules` et `storage`; les `.env.example` sans secrets peuvent être versionnés.
- Ne jamais committer de mot de passe, clé `APP_KEY`, clé interne, jeton, fichier `.env`, dump réel ou fichier d'ordonnance.
- Le premier compte administrateur est créé par une commande interactive; aucun mot de passe initial n'est fourni par défaut.
- L'inscription publique fixe le rôle `client`; les rôles privilégiés sont attribués par l'administration.
- Le middleware de rôle exige un pharmacien vérifié pour accéder aux outils pharmacien.
- L'en-tête partagé `X-Internal-Key` est sensible. Le réseau des services doit rester privé et la clé doit être injectée depuis un gestionnaire de secrets en production.
- La gateway vérifie le token Sanctum côté serveur sur les pages protégées; les routes de session ne doivent pas être exposées sans HTTPS en production.
- Désactiver `APP_DEBUG`, activer TLS, limiter les ports ouverts, configurer SMTP, protéger les ordonnances et sauvegarder les données.
- Les routes Laravel qui ont un middleware de limitation (`throttle`) incluent notamment l'inscription, la connexion, le contact, le checkout et les demandes de reset.

## 17. Limites et évolutions possibles

- La route de vérification du catalogue contrôle le stock, mais le checkout ne réserve pas et n'appelle pas la route de décrément; plusieurs commandes concurrentes peuvent donc dépasser la quantité disponible.
- La validation de l'ordonnance n'appelle pas non plus le décrément de stock.
- Paiement sans prestataire externe : le service enregistre un paiement et une référence simulés.
- Pas de gestion des lots; une seule date de péremption existe par médicament.
- Les états de commande sont des chaînes; la gateway admin filtre les valeurs proposées, mais le service commandes ne contraint pas les transitions.
- Le panier est conservé côté navigateur; aucune transaction distribuée ne coordonne les services.
- Aucun dépôt distant, hébergeur, URL publique ni identifiant de test avec mot de passe connu n'est défini.
- Évolutions possibles : réservation/décrément idempotent et transactionnel, gestion de lots, intégration Mobile Money, machine d'état explicite, stockage objet privé, journalisation centralisée et tests d'intégration end-to-end.

## 18. Auteur, date et licence

| Élément | Information |
|---|---|
| Auteur | À renseigner par le propriétaire du projet. |
| Date | 14 novembre 2026. |
| Licence | Les manifestes Composer déclarent MIT, mais aucun fichier `LICENSE` racine n'est présent. Ajouter le texte de licence et confirmer les droits avant toute distribution officielle. |
