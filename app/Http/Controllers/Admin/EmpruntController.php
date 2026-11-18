<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use App\Services\EmpruntService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpruntController extends Controller
{
    /**
     * Liste de tous les emprunts, filtrable par statut (?statut=en_cours|en_retard|rendus).
     */
    public function index(Request $request): View
    {
        $statut = $request->query('statut');

        $emprunts = Emprunt::query()
            ->with(['user', 'livre'])
            ->when($statut === 'en_cours', fn ($q) => $q->enCours())
            ->when($statut === 'en_retard', fn ($q) => $q->enRetard())
            ->when($statut === 'rendus', fn ($q) => $q->rendus())
            ->when($request->query('q'), function ($q, $terme) {
                $q->where(function ($q) use ($terme) {
                    $q->whereHas('user', fn ($u) => $u->whereLike('name', "%{$terme}%"))
                      ->orWhereHas('livre', fn ($l) => $l->whereLike('titre', "%{$terme}%"));
                });
            })
            ->latest('date_emprunt')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.emprunts.index', compact('emprunts', 'statut'));
    }

    public function create(Request $request): View
    {
        return view('admin.emprunts.create', [
            'lecteurs' => User::where('role', 'lecteur')
                ->withCount('empruntsEnCours')
                ->orderBy('name')
                ->get(),
            'livres' => Livre::withCount('empruntsEnCours')->orderBy('titre')->get(),
            'livreSelectionne' => $request->query('livre_id'),
        ]);
    }

    /**
     * Enregistre un emprunt en appliquant les règles métier.
     */
    public function store(Request $request, EmpruntService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'livre_id' => ['required', 'exists:livres,id'],
        ]);

        // Les règles métier (stock, max 3, doublon) sont dans App\Services\EmpruntService
        $service->emprunter($donnees['user_id'], $donnees['livre_id']);

        return redirect()->route('admin.emprunts.index')->with('success', 'Emprunt enregistré. Retour prévu dans '.Emprunt::DUREE_JOURS.' jours.');
    }

    /**
     * Enregistre le retour d'un livre.
     */
    public function retour(Emprunt $emprunt): RedirectResponse
    {
        if ($emprunt->date_retour_effective) {
            return back()->with('error', 'Ce livre a déjà été rendu.');
        }

        $emprunt->update(['date_retour_effective' => today()]);

        return back()->with('success', "Retour de « {$emprunt->livre->titre} » enregistré.");
    }
}
