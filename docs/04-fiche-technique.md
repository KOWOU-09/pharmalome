# Fiche technique — statistiques publiques

## Architecture

La page d'accueil de la gateway affiche quatre compteurs provenant des bases de données des microservices :

| Indicateur | Source | Règle de comptage |
|---|---|---|
| Utilisateurs inscrits | `user-service` | Comptes dont le rôle est `client` et dont `actif` vaut `true` |
| Médicaments au catalogue | `catalog-service` | Lignes de la table `medicaments` |
| Commandes livrées ou retirées | `order-service` | Commandes au statut `livree` ou `retiree` |
| Ordonnances traitées | `order-service` | Ordonnances au statut `validee` |

Chaque service expose `GET /api/public/stats`. Ces routes ne renvoient que des compteurs et exigent l'en-tête `X-Internal-Key`, vérifié par le middleware `internal.key`.

La gateway expose `GET /api/stats-publiques` sans authentification. Elle contacte les trois services simultanément avec `Http::pool`, utilise un délai maximal de trois secondes par requête et limite cet endpoint à 300 requêtes par minute et par adresse IP.

## Cache et disponibilité

La réponse agrégée est gardée en cache pendant 30 secondes avec le cache Laravel configuré par `CACHE_STORE` (fichier dans Docker par défaut, Redis possible avec la configuration Laravel). Les dernières valeurs reçues sont conservées jusqu'à 30 jours pour servir de repli lorsqu'un service est indisponible. Les compteurs sans valeur connue restent `null` au lieu d'afficher une donnée inventée.

La gateway renvoie un ETag calculé sur les compteurs. Un client peut transmettre `If-None-Match` et recevoir `304 Not Modified` si rien n'a changé.

Les URL des services sont configurables avec `USER_SERVICE_URL`, `CATALOG_SERVICE_URL` et `ORDER_SERVICE_URL`. La clé partagée se configure avec `INTERNAL_KEY`.

## Interface

La page d'accueil reçoit les valeurs dans son rendu Blade initial, puis `public/js/accueil-stats.js` les rafraîchit toutes les 30 secondes avec une variation aléatoire. Les requêtes s'arrêtent quand l'onglet est masqué et leur fréquence ralentit progressivement après une erreur. Les animations respectent le réglage `prefers-reduced-motion`.

Les nombres inférieurs à 1 000 sont affichés tels quels. À partir de 1 000, ils sont arrondis à la centaine inférieure et précédés de `+` (par exemple, 2 347 devient `+2 300`).
