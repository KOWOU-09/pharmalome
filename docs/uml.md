# Conception UML

## Cas d'utilisation

- Client : inscription, connexion, navigation, panier, commande, paiement, suivi.
- Pharmacien : validation d'ordonnance, préparation, livraison, gestion du stock.
- Admin : gestion des utilisateurs, supervision des opérations.

## Diagramme de classes (conceptuel)

- User
- Category
- Medicament
- Commande
- LigneCommande
- Ordonnance
- Paiement
- Notification

## Flux principal

1. Le client ajoute un article au panier dans la gateway.
2. La gateway valide la session utilisateur.
3. Un appel interne envoie la commande au service order.
4. Order vérifie les médicaments dans catalog.
5. Si besoin, le pharmacien valide l'ordonnance.
6. Le stock est ajusté uniquement au moment de la validation.
7. Le client reçoit une notification et peut suivre sa commande.
