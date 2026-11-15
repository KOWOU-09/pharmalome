# Architecture PharmaLomé

## Vue d'ensemble

La plateforme PharmaLomé suit une architecture de type micro-services avec une unique gateway web. La gateway expose les vues Blade et le front, tout en délégant les données métier aux services internes.

## Services

### Gateway
- Port : 8080
- Rôle : interface utilisateur, authentification session, orchestration des appels HTTP.
- Données : aucune base de données locale ; sessions et cache stockés en fichier.

### User service
- Port : 8001
- Base : user_db
- Rôle : création de comptes, login, utilisation de Sanctum, rôles et gestion administrative.

### Catalog service
- Port : 8002
- Base : catalog_db
- Rôle : catégories, produits, prix, disponibilité, stock et alertes.

### Order service
- Port : 8003
- Base : order_db
- Rôle : commandes, ordonnances, paiement, notification, statut de livraison.

## Sécurité

Chaque service protège ses routes internes via un middleware de clé interne. Le header requis est :

- X-Internal-Key: <valeur> 

La gateway ajoute aussi les métadonnées utilisateur dans les requêtes internes :

- X-User-Id
- X-User-Role

## Données

Aucune clé étrangère SQL n'est utilisée entre micro-services. Les échanges reposent sur des identifiants métier comme user_id, medicament_id et pharmacien_id.
