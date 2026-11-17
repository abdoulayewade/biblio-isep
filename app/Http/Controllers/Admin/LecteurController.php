<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Gestion des lecteurs par l'administrateur (inscription "au guichet").
 */
class LecteurController extends Controller
{
    public function index(Request $request): View
    {
        $lecteurs = User::where('role', 'lecteur')
            ->withCount(['empruntsEnCours', 'emprunts'])
            ->when($request->query('q'), function ($q, $terme) {
                $q->where(fn ($q) => $q->whereLike('name', "%{$terme}%")
                                       ->orWhereLike('email', "%{$terme}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.lecteurs.index', compact('lecteurs'));
    }

    public function create(): View
    {
        return view('admin.lecteurs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // role = 'lecteur' par défaut (valeur par défaut de la colonne)
        User::create($donnees);

        return redirect()->route('admin.lecteurs.index')->with('success', "Lecteur « {$donnees['name']} » inscrit.");
    }
}
