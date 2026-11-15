# Guide d'utilisation

## Consulter les chiffres clés

La section « PharmaLomé en chiffres » de la page d'accueil présente les clients actifs inscrits, les médicaments au catalogue, les commandes livrées ou retirées et les ordonnances validées. Les données sont lues dans les bases de données des services concernés et actualisées automatiquement environ toutes les 30 secondes. Laissez la page ouverte au premier plan pour que le rafraîchissement continue.

Si un service est temporairement indisponible, la page conserve les dernières valeurs connues. Si aucune valeur n'a encore été reçue, le compteur indique « Indisponible » plutôt qu'un chiffre fictif.

## Ajouter des comptes de démonstration

Le seeder optionnel `DemoUsersSeeder` crée des clients de test actifs, avec des adresses de la forme `client123@demo.pharmalome.tg`. Il est désactivé par défaut (`SEED_DEMO_USERS=0`) et insère les lignes par lots. Ces comptes servent uniquement aux démonstrations et ne sont pas des clients réels.

Depuis la racine du projet, en définissant le nombre souhaité :

```powershell
docker compose exec -e SEED_DEMO_USERS=2500 user-service php artisan db:seed --class=DemoUsersSeeder
```

Le seeder utilise des insertions idempotentes pour ne pas créer une seconde fois les mêmes adresses lors d'une réexécution. Les valeurs des compteurs restent toujours calculées depuis la base de données.

## Parcours principaux

### Client

1. Créer un compte.
2. Parcourir le catalogue.
3. Ajouter des produits au panier.
4. Choisir le mode de réception.
5. Ajouter l'ordonnance nécessaire si elle est requise.
6. Valider le paiement.
7. Suivre le statut de la commande.

### Pharmacien

1. Consulter les ordonnances à valider.
2. Vérifier leur conformité.
3. Valider ou refuser chaque ordonnance avec un motif.
4. Préparer les commandes et mettre à jour leur statut.
5. Gérer les alertes de stock et de péremption.

### Administrateur

1. Gérer les utilisateurs.
2. Surveiller les statuts des commandes.
3. Consulter les statistiques globales.
