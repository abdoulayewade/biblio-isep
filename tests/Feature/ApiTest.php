<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function lecteur(): User
    {
        return User::factory()->create(['role' => 'lecteur']);
    }

    private function livre(int $quantite = 2): Livre
    {
        $categorie = Category::firstOrCreate(['nom' => 'Informatique']);

        return Livre::create([
            'titre' => 'Livre '.fake()->unique()->word(),
            'auteur' => 'Auteur',
            'isbn' => fake()->unique()->isbn13(),
            'quantite' => $quantite,
            'category_id' => $categorie->id,
        ]);
    }

    // --- Authentification ---

    public function test_inscription_renvoie_un_jeton_et_cree_un_lecteur(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa',
            'email' => 'awa@test.sn',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // tentative de mass assignment
        ])
            ->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.role', 'lecteur');
    }

    public function test_connexion_puis_acces_avec_le_jeton(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_connexion_refusee_avec_un_mauvais_mot_de_passe(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'faux'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_deconnexion_revoque_le_jeton(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sans_jeton_on_recoit_401_en_json_meme_sans_en_tete_accept(): void
    {
        $this->get('/api/livres')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    // --- Accès ---

    public function test_un_lecteur_ne_peut_pas_appeler_les_routes_admin(): void
    {
        Sanctum::actingAs($this->lecteur());

        $this->getJson('/api/admin/emprunts')->assertForbidden();
        $this->postJson('/api/admin/categories', ['nom' => 'X'])->assertForbidden();
    }

    // --- Catalogue ---

    public function test_catalogue_avec_recherche_et_disponibilite(): void
    {
        $livre = $this->livre(quantite: 1);
        $this->livre();
        Emprunt::create([
            'user_id' => $this->lecteur()->id,
            'livre_id' => $livre->id,
            'date_emprunt' => today(),
            'date_retour_prevue' => today()->addDays(14),
        ]);

        Sanctum::actingAs($this->lecteur());

        $this->getJson('/api/livres?q='.urlencode($livre->titre))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.disponible', 0)
            ->assertJsonPath('data.0.category.nom', 'Informatique');

        // ?disponible=1 exclut le livre dont l'unique exemplaire est prêté
        $this->getJson('/api/livres?disponible=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['titre' => $livre->titre]);
    }

    // --- Admin : CRUD ---

    public function test_admin_crud_categorie_et_livre(): void
    {
        Sanctum::actingAs($this->admin());

        $categorieId = $this->postJson('/api/admin/categories', ['nom' => 'Roman'])
            ->assertCreated()
            ->json('data.id');

        $livreId = $this->postJson('/api/admin/livres', [
            'titre' => 'Une si longue lettre',
            'auteur' => 'Mariama Bâ',
            'isbn' => '9782842611927',
            'quantite' => 3,
            'category_id' => $categorieId,
        ])->assertCreated()->assertJsonPath('data.disponible', 3)->json('data.id');

        $this->patchJson("/api/admin/livres/{$livreId}", ['quantite' => 5])
            ->assertOk()
            ->assertJsonPath('data.quantite', 5);

        // catégorie non vide : suppression refusée
        $this->deleteJson("/api/admin/categories/{$categorieId}")->assertStatus(409);

        $this->deleteJson("/api/admin/livres/{$livreId}")->assertOk();
        $this->deleteJson("/api/admin/categories/{$categorieId}")->assertOk();
    }

    // --- Admin : emprunts et règles métier ---

    public function test_admin_enregistre_un_emprunt_puis_le_retour(): void
    {
        Sanctum::actingAs($this->admin());
        $lecteur = $this->lecteur();
        $livre = $this->livre();

        $empruntId = $this->postJson('/api/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $livre->id])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'en_cours')
            ->assertJsonPath('data.date_retour_prevue', today()->addDays(Emprunt::DUREE_JOURS)->toDateString())
            ->json('data.id');

        $this->patchJson("/api/admin/emprunts/{$empruntId}/retour")
            ->assertOk()
            ->assertJsonPath('data.statut', 'rendu');

        $this->patchJson("/api/admin/emprunts/{$empruntId}/retour")->assertStatus(409);
    }

    public function test_regle_livre_indisponible(): void
    {
        Sanctum::actingAs($this->admin());
        $livre = $this->livre(quantite: 1);

        $this->postJson('/api/admin/emprunts', ['user_id' => $this->lecteur()->id, 'livre_id' => $livre->id])->assertCreated();

        $this->postJson('/api/admin/emprunts', ['user_id' => $this->lecteur()->id, 'livre_id' => $livre->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('livre_id');
    }

    public function test_regle_maximum_emprunts_en_cours(): void
    {
        Sanctum::actingAs($this->admin());
        $lecteur = $this->lecteur();

        for ($i = 0; $i < Emprunt::MAX_EN_COURS; $i++) {
            $this->postJson('/api/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $this->livre()->id])->assertCreated();
        }

        $this->postJson('/api/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $this->livre()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    // --- Lecteur ---

    public function test_un_lecteur_voit_uniquement_ses_emprunts(): void
    {
        $moi = $this->lecteur();
        $autre = $this->lecteur();
        $livre = $this->livre();

        foreach ([$moi, $autre] as $user) {
            Emprunt::create([
                'user_id' => $user->id,
                'livre_id' => $livre->id,
                'date_emprunt' => today()->subDays(20),
                'date_retour_prevue' => today()->subDays(6),
            ]);
        }

        Sanctum::actingAs($moi);

        $this->getJson('/api/mes-emprunts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.statut', 'en_retard');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.en_retard', 1);
    }
}
