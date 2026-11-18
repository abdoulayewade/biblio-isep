# 📚 BiblioISEP — Gestion de bibliothèque

Application web de gestion d'une bibliothèque : catalogue de livres, catégories, emprunts et retours, suivi des retards.

**Stack :** Laravel 12 · Breeze (authentification) · PostgreSQL · Blade + Tailwind CSS

---

## 1. Installation sur Windows (Laragon)

> Prérequis : **PHP 8.2 ou plus** (`php -v`), Composer, **PostgreSQL** démarré.
> L'extension PHP `pdo_pgsql` doit être activée (voir plus bas).
> Node.js n'est **pas** obligatoire : les fichiers CSS/JS compilés sont déjà dans `public/build`.

1. Décompresser le dossier `biblio-isep` dans `C:\laragon\www\`
2. **Créer la base PostgreSQL** `biblio_isep` (Laravel ne la crée pas tout seul avec PostgreSQL) :
   - **pgAdmin** : clic droit sur *Databases* → *Create* → *Database...* → nom : `biblio_isep`
   - ou en ligne de commande : `psql -U postgres -c "CREATE DATABASE biblio_isep;"`
3. Ouvrir le terminal puis :

```bash
cd C:\laragon\www\biblio-isep
composer install
copy .env.example .env
```

4. Dans `.env`, mettre **votre mot de passe PostgreSQL** : `DB_PASSWORD=...`
5. Puis :

```bash
php artisan key:generate
php artisan migrate
```

### Activer PostgreSQL dans PHP (si erreur `could not find driver`)

Ouvrir le `php.ini` utilisé (`php --ini` l'indique) et retirer le `;` devant ces deux lignes :

```ini
extension=pdo_pgsql
extension=pgsql
```

Puis fermer et rouvrir le terminal. Vérifier avec `php -m` : `pdo_pgsql` doit apparaître.

6. **Créer votre compte administrateur** (vous tapez vos propres informations) :

```bash
php artisan app:creer-admin
```

7. Lancer `php artisan serve` et ouvrir **http://127.0.0.1:8000**

### Premier démarrage : l'application est vide, c'est normal

Toutes les données se saisissent depuis l'interface, dans cet ordre :

1. Connectez-vous avec votre compte admin
2. **Catégories** → créez vos catégories (un livre doit appartenir à une catégorie)
3. **Catalogue** → « Ajouter un livre »
4. **Lecteurs** → inscrivez des lecteurs (ou ils s'inscrivent eux-mêmes via « Inscription »)
5. **Emprunts** → « Nouvel emprunt », puis « Enregistrer le retour » quand le livre revient

> Un compte créé via la page d'inscription est **toujours lecteur**. Pour passer un compte existant administrateur : `php artisan app:creer-admin` puis saisir son e-mail.

### Si vous modifiez les vues (classes Tailwind)

```bash
npm install
npm run build
```

### Lancer les tests automatiques

```bash
php artisan test
```

> Les tests utilisent une base **temporaire en mémoire** (SQLite) : ils ne touchent jamais à vos données PostgreSQL.

---

## 2. Fonctionnalités

| Lecteur | Administrateur |
|---|---|
| Inscription / connexion | Tout ce que fait le lecteur |
| Catalogue + recherche (titre, auteur, ISBN) + filtre par catégorie | CRUD des catégories |
| Voir la disponibilité de chaque livre | CRUD des livres |
| **Mes emprunts** (en cours, en retard, historique) | Enregistrer un emprunt / un retour |
| Tableau de bord personnel | Inscrire des lecteurs, voir leurs emprunts |
| | Liste des emprunts filtrable (en cours / en retard / rendus) |
| | Tableau de bord (statistiques + retards) |

### Règles métier

- Durée d'un prêt : **14 jours** (`Emprunt::DUREE_JOURS`)
- Maximum **3 emprunts en cours** par lecteur (`Emprunt::MAX_EN_COURS`)
- Impossible d'emprunter un livre dont tous les exemplaires sont prêtés
- Un lecteur ne peut pas emprunter deux fois le même livre en même temps
- Impossible de supprimer une catégorie qui contient des livres, ou un livre qui a un historique
- Impossible de réduire le nombre d'exemplaires sous le nombre actuellement prêtés

---

## 3. Base de données

```
┌────────────┐            ┌──────────────┐            ┌────────────┐            ┌──────────────┐
│   users    │ 1        N │   emprunts   │ N        1 │   livres   │ N        1 │  categories  │
│────────────│────────────│──────────────│────────────│────────────│────────────│──────────────│
│ id (PK)    │            │ id (PK)      │            │ id (PK)    │            │ id (PK)      │
│ name       │            │ user_id (FK) │            │ titre      │            │ nom (unique) │
│ email      │            │ livre_id(FK) │            │ auteur     │            └──────────────┘
│ password   │            │ date_emprunt │            │ isbn       │
│ role       │            │ date_retour_ │            │ quantite   │
└────────────┘            │   prevue     │            │ category_id│
                          │ date_retour_ │            │   (FK)     │
                          │   effective  │            └────────────┘
                          └──────────────┘
```

- **categories → livres (1-N)** : une catégorie contient plusieurs livres.
- **users ↔ livres (N-N)** via la table **emprunts**, qui porte ses propres données (les dates).
- Le **statut** d'un emprunt et le **stock disponible** ne sont pas stockés, ils sont **calculés** :
  - en cours : `date_retour_effective IS NULL`
  - en retard : en cours **et** `date_retour_prevue < aujourd'hui`
  - disponible : `quantite - nombre d'emprunts en cours`

  → Aucune donnée dupliquée qui pourrait se désynchroniser.

| Clé étrangère | ON DELETE | Pourquoi |
|---|---|---|
| `livres.category_id` | RESTRICT | on ne perd pas des livres en supprimant une catégorie |
| `emprunts.livre_id` | RESTRICT | on garde l'historique des emprunts |
| `emprunts.user_id` | CASCADE | un compte supprimé emporte ses emprunts |

---

## 4. Où se trouve quoi ? (pour l'oral)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php      → tableau de bord (admin ou lecteur)
│   │   ├── LivreController.php          → catalogue + CRUD livres
│   │   ├── MesEmpruntsController.php    → "Mes emprunts" du lecteur
│   │   └── Admin/
│   │       ├── CategoryController.php   → CRUD catégories
│   │       ├── LecteurController.php    → liste + inscription des lecteurs
│   │       └── EmpruntController.php    → emprunts, RÈGLES MÉTIER, retours
│   └── Middleware/
│       └── EnsureUserIsAdmin.php        → bloque /admin aux non-admins (403)
├── Models/
│   ├── User.php       → hasMany(emprunts), isAdmin()
│   ├── Category.php   → hasMany(livres)
│   ├── Livre.php      → belongsTo(category), hasMany(emprunts), attribut "disponible"
│   └── Emprunt.php    → belongsTo(user, livre), scopes enCours/enRetard/rendus, "statut"
app/Console/Commands/CreerAdmin.php → commande "php artisan app:creer-admin"
bootstrap/app.php      → enregistrement du middleware 'admin'
routes/web.php         → toutes les routes (groupe auth + sous-groupe admin)
database/migrations/   → les 4 tables
database/seeders/      → vide (les données se saisissent dans l'application)
resources/views/       → vues Blade (livres/, emprunts/, admin/)
lang/fr/ + lang/fr.json → traductions françaises
tests/Feature/BibliothequeTest.php → tests des règles métier
```

### Questions probables du jury

**« Comment empêchez-vous un lecteur d'accéder aux pages admin ? »**
Les routes `/admin/*` passent par le middleware `admin` (`EnsureUserIsAdmin`), qui renvoie une erreur 403 si `role !== 'admin'`. Masquer les boutons dans la vue ne suffit pas : la vraie protection est côté serveur.

**« Un utilisateur peut-il s'inscrire en tant qu'admin ? »**
Non. `role` n'est pas dans `$fillable` du modèle `User` (protection contre le *mass assignment*), et la colonne vaut `lecteur` par défaut en base. Un test automatique le vérifie.

**« Comment est créé le premier administrateur ? »**
Avec la commande `php artisan app:creer-admin`. Seule une personne qui a accès au serveur peut l'exécuter : c'est plus sûr qu'un compte admin écrit en dur dans le code, ou que « le premier inscrit devient admin ».

**« Pourquoi une transaction avec `lockForUpdate()` dans l'emprunt ? »**
Si deux bibliothécaires prêtent le dernier exemplaire au même instant, sans verrou les deux verraient « 1 disponible » et le livre serait prêté deux fois. Le verrou fait passer les requêtes l'une après l'autre.

**« Pourquoi `whereLike()` et pas `where('titre', 'like', ...)` ? »**
Sous PostgreSQL, `LIKE` est sensible à la casse : « clean » ne trouverait pas « Clean Code ». `whereLike()` génère `ILIKE` (insensible à la casse) sous PostgreSQL et `LIKE` sous MySQL : le même code fonctionne sur les deux.

**« Qu'est-ce que le problème N+1 ? »**
Afficher 10 livres puis faire une requête par livre pour compter ses emprunts = 11 requêtes. Avec `withCount('empruntsEnCours')` et `with('category')`, on ne fait que 3 requêtes au total.

---

## 5. Web services (API REST)

L'application expose une API JSON sous le préfixe **`/api`**, sécurisée par **jetons Laravel Sanctum**.
Elle réutilise exactement les mêmes modèles et règles métier que l'interface web
(`App\Services\EmpruntService` est partagé par les deux).

> Après installation : `php artisan migrate` (crée la table `personal_access_tokens`).

### Authentification

1. `POST /api/login` avec `{"email": "...", "password": "..."}` → la réponse contient un `token`
2. Envoyer ce jeton dans chaque requête : `Authorization: Bearer <token>`
3. `POST /api/logout` révoque le jeton

Toutes les erreurs sont en JSON : `401` (pas de jeton), `403` (pas admin), `404`, `409` (conflit métier :
suppression impossible, livre déjà rendu), `422` (validation ou règle d'emprunt non respectée).

### Endpoints

| Méthode | URL | Accès | Description |
|---|---|---|---|
| POST | `/api/register` | public | Inscription (toujours **lecteur**) → jeton |
| POST | `/api/login` | public | Connexion → jeton (6 essais / minute) |
| POST | `/api/logout` | connecté | Révoque le jeton courant |
| GET | `/api/me` | connecté | Utilisateur connecté |
| GET | `/api/dashboard` | connecté | Statistiques (globales si admin, personnelles sinon) |
| GET | `/api/livres` | connecté | Catalogue paginé — `?q=`, `?categorie=`, `?disponible=1`, `?per_page=` |
| GET | `/api/livres/{id}` | connecté | Détail d'un livre (avec stock disponible) |
| GET | `/api/categories` | connecté | Liste des catégories (avec nombre de livres) |
| GET | `/api/categories/{id}` | connecté | Détail d'une catégorie |
| GET | `/api/mes-emprunts` | connecté | Mes emprunts — `?statut=en_cours\|en_retard\|rendus` |
| POST | `/api/admin/categories` | admin | Créer une catégorie `{nom}` |
| PUT/PATCH | `/api/admin/categories/{id}` | admin | Modifier une catégorie |
| DELETE | `/api/admin/categories/{id}` | admin | Supprimer (409 si elle contient des livres) |
| POST | `/api/admin/livres` | admin | Créer un livre `{titre, auteur, isbn, quantite, category_id}` |
| PUT/PATCH | `/api/admin/livres/{id}` | admin | Modifier (PATCH = champs partiels) |
| DELETE | `/api/admin/livres/{id}` | admin | Supprimer (409 s'il a un historique) |
| GET | `/api/admin/lecteurs` | admin | Lecteurs paginés — `?q=` |
| POST | `/api/admin/lecteurs` | admin | Inscrire un lecteur `{name, email, password, password_confirmation}` |
| GET | `/api/admin/lecteurs/{id}` | admin | Fiche lecteur + historique d'emprunts |
| GET | `/api/admin/emprunts` | admin | Emprunts — `?statut=`, `?q=`, `?user_id=` |
| POST | `/api/admin/emprunts` | admin | Nouvel emprunt `{user_id, livre_id}` (règles métier → 422) |
| GET | `/api/admin/emprunts/{id}` | admin | Détail d'un emprunt |
| PATCH | `/api/admin/emprunts/{id}/retour` | admin | Enregistrer le retour |

### Tester avec Postman

Une collection prête à l'emploi est fournie : **`postman/BiblioISEP.postman_collection.json`** (34 requêtes).

1. Lancer le serveur : `php artisan serve` (adapter la variable `base_url` si le port n'est pas `8080`)
2. Postman › **Import** › choisir le fichier
3. Cliquer sur la collection › onglet **Variables** › renseigner `admin_email` et `admin_password` › **Save**
4. Exécuter **1. Authentification › Connexion (admin)** : le jeton est enregistré automatiquement
5. Lancer les autres requêtes une par une, ou tout d'un coup : clic droit › **Run collection**

Les requêtes « Créer » mémorisent les identifiants créés (catégorie, livre, lecteur, emprunt) pour les
requêtes suivantes. Chaque requête vérifie son code de retour (onglet *Test Results*), y compris les cas
d'erreur volontaires : 401 sans jeton, 403 pour un lecteur sur une route admin, 422 pour un emprunt en double,
409 pour une suppression interdite.

### Exemple (curl)

```bash
curl -X POST http://127.0.0.1:8000/api/login -H "Accept: application/json" -d "email=admin@isep.sn" -d "password=motdepasse"
curl http://127.0.0.1:8000/api/livres?q=clean -H "Authorization: Bearer 1|xxxxxxxx"
```

### Où est le code ?

```
routes/api.php                         → toutes les routes de l'API
app/Http/Controllers/Api/              → contrôleurs JSON (Auth, Livre, Category, Emprunt, Lecteur, ...)
app/Http/Resources/                    → format JSON renvoyé (LivreResource, EmpruntResource, ...)
app/Services/EmpruntService.php        → règles métier d'emprunt (partagées web + API)
bootstrap/app.php                      → erreurs en JSON sur /api/*
tests/Feature/ApiTest.php              → tests de l'API
postman/                               → collection Postman pour tester l'API à la main
```
