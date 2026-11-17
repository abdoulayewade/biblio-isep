<<<<<<< HEAD
# biblio-isep
=======
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
>>>>>>> dc9b535 (bibilio-isep)
