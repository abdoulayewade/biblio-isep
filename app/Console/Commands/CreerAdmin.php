<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Crée le premier compte administrateur, avec VOS informations.
 * Utilisation : php artisan app:creer-admin
 *
 * Pourquoi une commande ? L'inscription web crée toujours un "lecteur" (sécurité).
 * Il faut donc un moyen sûr de créer le premier admin : seul quelqu'un qui a accès
 * au serveur (au terminal) peut le faire.
 */
class CreerAdmin extends Command
{
    protected $signature = 'app:creer-admin';

    protected $description = 'Créer un compte administrateur (ou promouvoir un compte existant)';

    public function handle(): int
    {
        $email = $this->ask('E-mail de l\'administrateur');

        // Si le compte existe déjà (ex. inscrit via le site), on le promeut admin
        $existant = User::where('email', $email)->first();
        if ($existant) {
            if ($this->confirm("Le compte « {$existant->name} » existe déjà. Le passer administrateur ?", true)) {
                $existant->role = 'admin';
                $existant->save();
                $this->info("✔ {$existant->name} est maintenant administrateur.");
            }

            return self::SUCCESS;
        }

        $donnees = [
            'name' => $this->ask('Nom complet'),
            'email' => $email,
            'password' => $this->secret('Mot de passe (8 caractères minimum)'),
        ];
        $donnees['password_confirmation'] = $this->secret('Confirmer le mot de passe');

        $validateur = Validator::make($donnees, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        // 'role' n'est pas dans $fillable : on l'affecte explicitement
        $admin = new User([
            'name' => $donnees['name'],
            'email' => $donnees['email'],
            'password' => $donnees['password'], // haché automatiquement (cast 'hashed')
        ]);
        $admin->role = 'admin';
        $admin->save();

        $this->info("✔ Administrateur « {$admin->name} » créé. Vous pouvez vous connecter.");

        return self::SUCCESS;
    }
}
