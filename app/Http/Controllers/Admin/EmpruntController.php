<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'livre_id' => ['required', 'exists:livres,id'],
        ]);

        // Transaction + verrou : si deux admins prêtent le dernier exemplaire au même moment,
        // le second attend que le premier ait fini, puis voit le stock à jour.
        DB::transaction(function () use ($donnees) {
            $lecteur = User::findOrFail($donnees['user_id']);
            $livre = Livre::lockForUpdate()->findOrFail($donnees['livre_id']);

            // Règle 1 : le livre doit être disponible
            if ($livre->disponible <= 0) {
                throw ValidationException::withMessages([
                    'livre_id' => "« {$livre->titre} » n'est plus disponible (tous les exemplaires sont prêtés).",
                ]);
            }

            // Règle 2 : maximum MAX_EN_COURS emprunts en cours par lecteur
            if ($lecteur->empruntsEnCours()->count() >= Emprunt::MAX_EN_COURS) {
                throw ValidationException::withMessages([
                    'user_id' => "{$lecteur->name} a déjà ".Emprunt::MAX_EN_COURS.' emprunts en cours (maximum atteint).',
                ]);
            }

            // Règle 3 : pas deux fois le même livre en même temps
            if ($lecteur->empruntsEnCours()->where('livre_id', $livre->id)->exists()) {
                throw ValidationException::withMessages([
                    'livre_id' => "{$lecteur->name} a déjà ce livre en cours d'emprunt.",
                ]);
            }

            Emprunt::create([
                'user_id' => $lecteur->id,
                'livre_id' => $livre->id,
                'date_emprunt' => today(),
                'date_retour_prevue' => today()->addDays(Emprunt::DUREE_JOURS),
            ]);
        });

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
