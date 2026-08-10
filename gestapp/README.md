# Gesta Pilot

Application Flutter responsive de suivi d'activité pour le dirigeant. Elle
affiche les ventes, bénéfices, dépenses, encaissements, achats, catalogue
produits, contacts, alertes de stock, caisses et opérations, avec actualisation
automatique.

## Technologies

- Flutter / Material 3
- Riverpod pour l'état, les filtres et le rafraîchissement
- Dio pour l'API
- `flutter_secure_storage` pour le jeton Passport
- Mise en page adaptative : navigation basse sur téléphone, rail sur tablette
  et grand écran
- Reconnexion automatique sans persistance des données métier
- Notifications système configurables
- Pagination infinie, recherche et filtres avancés

## Démarrage

```powershell
flutter pub get
flutter run
```

L'écran de connexion permet de modifier l'adresse du serveur. La valeur par
défaut de production est :

```text
https://gesta.diakasoft.com/public/api/mobile/v1
```

Le serveur doit être joignable depuis le téléphone et utiliser un certificat
HTTPS valide.

Pour définir l'URL au moment de la compilation :

```powershell
flutter run --dart-define=GESTA_API_URL=https://gesta.exemple.com/api/mobile/v1
```

Une version de production doit utiliser HTTPS. Le trafic HTTP non chiffré n'est
autorisé que dans la variante Android `debug`.

## Organisation

```text
lib/
├── core/                  configuration, réseau, stockage et thème
├── features/auth/         session et écran de connexion
├── features/management/   données, providers et écrans de pilotage
└── features/shell/        navigation responsive
```

Le tableau de bord se rafraîchit toutes les 15 secondes et l'activité toutes les
10 secondes. Les intervalles viennent de `/bootstrap`, ce qui permet de les faire
évoluer côté API.

Le catalogue, les contacts, les opérations et l'historique d'activité chargent
les pages suivantes automatiquement à l'approche du bas de la liste.

Les réponses métier ne sont jamais conservées sur le téléphone. Sans réseau,
elles sont indiquées comme indisponibles jusqu'à la reconnexion. Seuls le jeton
Passport et l'adresse du serveur sont conservés dans le stockage sécurisé du
système. Les réglages de notifications restent uniquement en mémoire pendant la
session courante.

## Icône et splash

Après modification de `assets/branding/app_icon.png`, régénérer les actifs :

```powershell
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```

## Notifications

Les alertes locales fonctionnent pendant la surveillance active et portent sur
les ventes importantes, le stock faible et les caisses à vérifier. Les seuils se
règlent dans l'écran Compte. Firebase/APNs reste nécessaire pour recevoir une
notification distante lorsque l'application est complètement arrêtée.

La documentation du backend se trouve dans `../docs/MOBILE_APP.md`.
