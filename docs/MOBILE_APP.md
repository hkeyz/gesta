# Gesta Pilot — architecture mobile

## Périmètre et isolation

L'application mobile est une couche de pilotage en lecture seule. Elle utilise les
données et les modèles Laravel existants, sans migration et sans modification des
contrôleurs, services ou modèles historiques.

Le backend mobile est isolé dans :

- `app/MobileApi/V1/` pour les contrôleurs, requêtes, middleware et services ;
- `routes/mobile_api.php` pour les routes versionnées ;
- une seule ligne d'inclusion ajoutée à `routes/api.php`.

Le client Flutter est entièrement contenu dans `gestapp/`.

## Flux général

1. Flutter envoie le nom d'utilisateur et le mot de passe à
   `POST /api/mobile/v1/auth/login`.
2. Laravel vérifie le compte, l'entreprise et crée un jeton personnel Passport.
3. Flutter conserve le jeton dans le stockage sécurisé du téléphone.
4. `GET /bootstrap` fournit l'utilisateur, l'entreprise, la devise, les sites
   autorisés, les fonctionnalités et les intervalles de rafraîchissement.
5. Le tableau de bord interroge l'API toutes les 15 secondes et le fil d'activité
   toutes les 10 secondes. Un glissement vers le bas force l'actualisation.
6. Toutes les requêtes sont limitées à l'entreprise et aux établissements
   autorisés de l'utilisateur connecté.

Cette stratégie offre un suivi quasi temps réel sans WebSocket, sans file
d'attente supplémentaire et sans changement de base de données.

## Endpoints API v1

Base : `/api/mobile/v1`

| Méthode | Route | Utilité |
|---|---|---|
| `POST` | `/auth/login` | Connexion et création du jeton Passport |
| `GET` | `/auth/me` | Vérification de la session |
| `POST` | `/auth/logout` | Révocation du jeton courant |
| `GET` | `/bootstrap` | Contexte entreprise, devise, sites et droits |
| `GET` | `/dashboard` | Indicateurs, tendances, alertes, tops et opérations |
| `GET` | `/activities` | Fil d'activité incrémental et curseur |
| `GET` | `/transactions` | Liste générique filtrable |
| `GET` | `/transactions/{id}` | Détail, articles et paiements |
| `GET` | `/sales` | Ventes |
| `GET` | `/purchases` | Achats |
| `GET` | `/expenses` | Dépenses |
| `GET` | `/inventory/summary` | Valorisation et alertes du stock |
| `GET` | `/inventory/categories` | Arbre des catégories de produits |
| `GET` | `/inventory/products` | Produits et quantités |
| `GET` | `/inventory/products/{variation}` | Détail, stock par site et ventes sur 30 jours |
| `GET` | `/inventory/low-stock` | Produits sous le seuil |
| `GET` | `/contacts` | Clients et fournisseurs |
| `GET` | `/contacts/{id}` | Fiche, statistiques et opérations du contact |
| `GET` | `/cash-registers/open` | Caisses ouvertes et mouvements |
| `GET` | `/cash-registers/{id}` | Totaux, moyens de paiement, mouvements et ventes |

Toutes les routes sauf `/auth/login` exigent :

```http
Accept: application/json
Authorization: Bearer <jeton>
```

Exemple :

```http
GET /api/mobile/v1/dashboard?range=today&location_id=2
```

Les périodes reconnues sont `today`, `yesterday`, `week`, `month`, `year`,
`last_7_days`, `last_30_days` et `custom`. Pour `custom`, fournir `from` et `to`.

Les listes acceptent notamment `page`, `per_page`, `search`, `location_id`,
`from`, `to`, `status` et `payment_status`. Les produits ajoutent
`category_id` et `stock_status`. Le fil d'activité utilise `since`/`after_id`
pour les nouveautés et `before`/`before_id` pour charger l'historique.

## Contrat de réponse

Succès :

```json
{
  "success": true,
  "data": {},
  "meta": {
    "generated_at": "2026-07-31T14:30:00+02:00",
    "refresh_after_seconds": 15
  }
}
```

Erreur :

```json
{
  "success": false,
  "error": {
    "code": "invalid_credentials",
    "message": "The username or password is incorrect.",
    "details": {}
  }
}
```

## Sécurité

- Chaque requête dérive `business_id` du jeton ; le client ne peut pas choisir
  une autre entreprise.
- Les établissements sont filtrés avec `access_all_locations` ou les permissions
  `location.{id}`.
- Les permissions Laravel existantes sont respectées.
- `sell.view_own` et `view_own_purchase` limitent les lignes à leur créateur.
- Un compte, une entreprise ou un accès de connexion désactivé est refusé à
  chaque requête.
- Aucun mot de passe n'est stocké par Flutter ; seul le jeton est gardé dans le
  Keychain/Keystore via `flutter_secure_storage`.
- Aucune réponse métier n'est conservée localement. Seuls le jeton Passport et
  l'adresse du serveur sont stockés dans le Keychain/Keystore du téléphone.
- Les préférences de notifications restent en mémoire et reviennent à leurs
  valeurs par défaut au prochain démarrage.
- En production, utiliser exclusivement HTTPS.

## Mise en service backend

L'installation Laravel existante crée normalement déjà les clés et le client
Passport. Vérifier :

```powershell
php artisan route:list --path=api/mobile/v1
php artisan passport:keys
```

Si aucun client d'accès personnel Passport n'existe, le créer une seule fois :

```powershell
php artisan passport:client --personal --name="Gesta Mobile"
```

Aucune migration mobile n'est requise. Après déploiement de routes mises en
cache :

```powershell
php artisan route:clear
php artisan config:clear
```

`APP_URL` doit contenir l'URL publique correcte pour que les images et logos
retournés par l'API soient accessibles au téléphone.

## Vérifications

Backend :

```powershell
php artisan test --filter=MobileApi
```

Flutter :

```powershell
cd gestapp
flutter pub get
flutter analyze
flutter test
flutter build apk --debug
```

Le backend n'étant pas installé dans cette copie de travail (`vendor`, `.env` et
`storage` absents), les tests Laravel doivent être lancés dans l'installation
opérationnelle ou après restauration de ses dépendances et de sa configuration.

## Réseau et reconnexion

Les données métier ne sont pas disponibles hors connexion et ne sont jamais
persistées sur le téléphone. Un bandeau distingue l'absence d'Internet d'un
serveur indisponible. La reconnexion invalide automatiquement les listes et
relance leur chargement depuis le tenant authentifié.

## Notifications

L'application génère des notifications système locales pour :

- une nouvelle vente dépassant le seuil configuré ;
- une augmentation du nombre de produits sous le seuil ;
- une caisse ouverte trop longtemps ou présentant un solde négatif.

Les seuils sont modifiables dans l'écran Compte. Ces alertes reposent sur la
surveillance active de l'application. Les notifications distantes lorsque
l'application est complètement arrêtée exigent un projet Firebase/APNs, les
certificats correspondants et un mécanisme serveur de conservation des jetons.
Ils ne peuvent pas être activés sans ces éléments externes.

## Identité visuelle

Le logo source se trouve dans `gestapp/assets/branding/app_icon.png`. Les icônes
Android/iOS sont générées avec `flutter_launcher_icons` et le splash natif avec
`flutter_native_splash`. Le symbole associe le G de Gesta, une impulsion
d'activité, une progression et un mini graphique, dans la palette navy/teal/ambre.
