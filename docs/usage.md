# Guide d'utilisation

## Pour un client

1. Créer un compte depuis **S'inscrire** avec prénom, nom, e-mail, téléphone et mot de passe (8 caractères minimum).
2. Le compte client est créé par le service utilisateurs, puis la session est ouverte automatiquement.
3. Se connecter ensuite avec l'adresse e-mail et le mot de passe choisis; le jeton du service reste côté serveur dans la session.
4. Modifier les coordonnées depuis **Mon compte**; utiliser **Mot de passe oublié** pour recevoir un lien à usage unique.
5. Parcourir le catalogue géré par l’administration, rechercher et filtrer les produits par catégorie, ordonnance, promotion ou prix.
6. Ajouter les produits disponibles au panier; les quantités et le total restent enregistrés dans le navigateur.
7. Être connecté pour accéder à la validation et à l'historique des commandes.
8. Se déconnecter depuis le bouton de la barre de navigation.

La connexion et l'inscription nécessitent que le service utilisateurs soit démarré et joignable par la gateway (`USER_SERVICE_URL`). Les rôles pharmacien et administrateur sont attribués séparément; ils ne peuvent pas être choisis sur le formulaire public.
Avec la gateway lancée localement, démarrez PostgreSQL et le service utilisateurs avec `docker compose up -d postgres user-service`, exécutez les migrations, puis créez l’administrateur initial avec `docker compose exec user-service php artisan users:create-admin`. Configurez `GATEWAY_URL` pour que les invitations ouvrent la bonne adresse. Pour démarrer également la gateway Docker, arrêtez le serveur local et lancez `docker compose up --build -d`.

Les services Docker exécutent le code depuis leurs images et conservent leurs données dans PostgreSQL et les volumes de stockage, plutôt que de lire le code sur le lecteur Windows à chaque requête. Après une modification du code, reconstruisez et redémarrez les services concernés avec `docker compose up --build -d`. Les fichiers `.env` de chaque service sont chargés par Compose et ne sont pas inclus dans les images.

La barre de navigation souligne la page active. Le catalogue et le panier sont interactifs; les commandes sont enregistrées par le service dédié.

Les pages **À propos** et **Contact** sont accessibles depuis le pied de page et le menu de l’accueil. Le formulaire de contact envoie les messages à `CONTACT_EMAIL` (par défaut `contact@medexpress.tg`) avec l’adresse du visiteur en réponse. Configurez un transport SMTP dans `gateway/.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`) pour recevoir réellement les messages. Avec `MAIL_MAILER=log`, ils sont écrits dans les journaux de la gateway. Après une modification de la configuration Docker, redémarrez la gateway.

## Pour un pharmacien

1. L’administrateur crée le compte professionnel et renseigne les informations du pharmacien.
2. Le pharmacien définit son mot de passe depuis le lien d’invitation, puis vérifie ses informations de qualification et son numéro d’ordre dans **Mon compte**.
3. Attendre la validation de ces informations par un administrateur; les fonctions métier restent fermées jusque-là.
4. Consulter la liste des ordonnances à valider.
5. Vérifier la conformité.
6. Valider ou refuser avec motif.
7. Préparer la commande et mettre à jour le statut.
8. Gérer les alertes de stock et de péremption.

## Pour un livreur

1. L’administrateur crée le compte livreur et envoie un lien d’invitation.
2. Consulter uniquement les commandes de livraison affectées à son compte depuis **Mes missions**.
3. Marquer une mission comme livrée; le statut de la commande et la notification du client sont mis à jour.

## Pour un administrateur

1. Se connecter avec le compte initial créé par la commande `users:create-admin`.
2. Ouvrir **Produits et catégories** pour créer une catégorie, puis ajouter ou modifier les produits, leur stock, leur prix et leur statut d’ordonnance.
3. Pour une promotion, cocher **Mettre en promotion** et saisir un prix inférieur au prix normal. La boutique et le panier utilisent le prix promotionnel configuré.
4. Consulter/rechercher les comptes, gérer leur rôle et leur activation.
5. Créer les comptes professionnels et renvoyer une invitation si nécessaire.
6. Vérifier les informations professionnelles avant d’autoriser un pharmacien.
7. Surveiller les commandes et affecter les commandes en livraison à un livreur actif.
8. Consulter les statistiques globales.

Les catégories et produits affichés dans l’accueil, le catalogue, les fiches produit et le panier proviennent du service catalogue et de sa base de données; les anciennes données de démonstration codées en dur ne sont plus affichées. Après une première installation, appliquer aussi la migration du catalogue avec `docker compose exec catalog-service php artisan migrate --force`.

## Données de démonstration

Le seeder optionnel `DemoUsersSeeder` ajoute des clients de test dont les adresses sont au format `client123@demo.pharmalome.tg`. Il est désactivé par défaut (`SEED_DEMO_USERS=0`). Pour en créer 2 500 dans Docker, exécuter depuis la racine du projet :

```powershell
docker compose exec -e SEED_DEMO_USERS=2500 user-service php artisan db:seed --class=DemoUsersSeeder
```

Ces comptes sont des données de test, pas des clients réels. Les chiffres clés de la page d'accueil sont calculés depuis les bases des services.
