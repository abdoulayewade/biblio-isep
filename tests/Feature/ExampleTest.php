<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La page d'accueil redirige vers le tableau de bord.
     */
    public function test_la_page_d_accueil_redirige_vers_le_tableau_de_bord(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }
}
