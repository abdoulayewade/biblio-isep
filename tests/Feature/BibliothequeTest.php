<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibliothequeTest extends TestCase
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

    private function emprunter(User $user, Livre $livre): Emprunt
    {
        return Emprunt::create([
            'user_id' => $user->id,
            'livre_id' => $livre->id,
            'date_emprunt' => today(),
            'date_retour_prevue' => today()->addDays(Emprunt::DUREE_JOURS),
        ]);
    }

    // --- Sécurité / accès ---

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/livres')->assertRedirect('/login');
    }

    public function test_un_lecteur_ne_peut_pas_acceder_a_l_admin(): void
    {
        $this->actingAs($this->lecteur())
            ->get('/admin/emprunts')
            ->assertForbidden();
    }

    public function test_un_lecteur_voit_le_catalogue(): void
    {
        $livre = $this->livre();

        $this->actingAs($this->lecteur())
            ->get('/livres')
            ->assertOk()
            ->assertSee($livre->titre);
    }

    public function test_l_inscription_cree_toujours_un_lecteur_meme_si_on_envoie_role_admin(): void
    {
        $this->post('/register', [
            'name' => 'Pirate',
            'email' => 'pirate@test.sn',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // tentative de mass assignment
        ]);

        $this->assertSame('lecteur', User::where('email', 'pirate@test.sn')->first()->fresh()->role);
    }

    // --- Règles métier des emprunts ---

    public function test_l_admin_peut_enregistrer_un_emprunt(): void
    {
        $lecteur = $this->lecteur();
        $livre = $this->livre();

        $this->actingAs($this->admin())
            ->post('/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $livre->id])
            ->assertRedirect('/admin/emprunts');

        $emprunt = Emprunt::first();
        $this->assertSame($lecteur->id, $emprunt->user_id);
        $this->assertTrue($emprunt->date_retour_prevue->isSameDay(today()->addDays(Emprunt::DUREE_JOURS)));
        $this->assertSame(1, $livre->fresh()->disponible);
    }

    public function test_impossible_d_emprunter_un_livre_indisponible(): void
    {
        $livre = $this->livre(quantite: 1);
        $this->emprunter($this->lecteur(), $livre);

        $this->actingAs($this->admin())
            ->post('/admin/emprunts', ['user_id' => $this->lecteur()->id, 'livre_id' => $livre->id])
            ->assertSessionHasErrors('livre_id');

        $this->assertSame(1, Emprunt::count());
    }

    public function test_un_lecteur_ne_peut_pas_depasser_le_maximum_d_emprunts(): void
    {
        $lecteur = $this->lecteur();
        for ($i = 0; $i < Emprunt::MAX_EN_COURS; $i++) {
            $this->emprunter($lecteur, $this->livre());
        }

        $this->actingAs($this->admin())
            ->post('/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $this->livre()->id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(Emprunt::MAX_EN_COURS, Emprunt::count());
    }

    public function test_un_lecteur_ne_peut_pas_emprunter_deux_fois_le_meme_livre(): void
    {
        $lecteur = $this->lecteur();
        $livre = $this->livre(quantite: 5);
        $this->emprunter($lecteur, $livre);

        $this->actingAs($this->admin())
            ->post('/admin/emprunts', ['user_id' => $lecteur->id, 'livre_id' => $livre->id])
            ->assertSessionHasErrors('livre_id');
    }

    public function test_le_retour_libere_un_exemplaire(): void
    {
        $livre = $this->livre(quantite: 1);
        $emprunt = $this->emprunter($this->lecteur(), $livre);
        $this->assertSame(0, $livre->fresh()->disponible);

        $this->actingAs($this->admin())
            ->patch("/admin/emprunts/{$emprunt->id}/retour")
            ->assertRedirect();

        $this->assertNotNull($emprunt->fresh()->date_retour_effective);
        $this->assertSame(1, $livre->fresh()->disponible);
    }

    public function test_un_emprunt_depasse_est_en_retard(): void
    {
        $emprunt = $this->emprunter($this->lecteur(), $this->livre());
        $emprunt->update(['date_retour_prevue' => today()->subDay()]);

        $this->assertSame('en_retard', $emprunt->fresh()->statut);
        $this->assertSame(1, Emprunt::enRetard()->count());
    }

    public function test_la_recherche_ignore_les_majuscules(): void
    {
        $livre = $this->livre();
        $livre->update(['titre' => 'Clean Code']);

        $this->actingAs($this->lecteur())
            ->get('/livres?q=clean')
            ->assertOk()
            ->assertSee('Clean Code');
    }

    // --- Intégrité des données ---

    public function test_impossible_de_supprimer_une_categorie_qui_contient_des_livres(): void
    {
        $livre = $this->livre();

        $this->actingAs($this->admin())
            ->delete("/admin/categories/{$livre->category_id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $livre->category_id]);
    }

    public function test_impossible_de_baisser_la_quantite_sous_les_exemplaires_pretes(): void
    {
        $livre = $this->livre(quantite: 2);
        $this->emprunter($this->lecteur(), $livre);
        $this->emprunter($this->lecteur(), $livre);

        $this->actingAs($this->admin())
            ->put("/admin/livres/{$livre->id}", [
                'titre' => $livre->titre,
                'auteur' => $livre->auteur,
                'isbn' => $livre->isbn,
                'quantite' => 1,
                'category_id' => $livre->category_id,
            ])
            ->assertSessionHasErrors('quantite');
    }
}
