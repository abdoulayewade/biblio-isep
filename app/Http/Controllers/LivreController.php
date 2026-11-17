<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Livre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LivreController extends Controller
{
    /**
     * Catalogue : visible par tous les utilisateurs connectés.
     */
    public function index(Request $request): View
    {
        $livres = Livre::query()
            ->with('category')
            ->withCount('empruntsEnCours') // 1 seule requête pour le stock disponible (évite N+1)
            ->recherche($request->query('q'))
            ->when($request->query('categorie'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('titre')
            ->paginate(10)
            ->withQueryString(); // garde ?q=...&categorie=... dans les liens de pagination

        return view('livres.index', [
            'livres' => $livres,
            'categories' => Category::orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.livres.create', [
            'livre' => new Livre(),
            'categories' => Category::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Livre::create($this->valider($request));

        return redirect()->route('livres.index')->with('success', 'Livre ajouté avec succès.');
    }

    public function edit(Livre $livre): View
    {
        return view('admin.livres.edit', [
            'livre' => $livre,
            'categories' => Category::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Livre $livre): RedirectResponse
    {
        $donnees = $this->valider($request, $livre);

        // On ne peut pas descendre la quantité sous le nombre d'exemplaires actuellement prêtés
        $enCours = $livre->empruntsEnCours()->count();
        if ($donnees['quantite'] < $enCours) {
            return back()->withInput()->withErrors([
                'quantite' => "Impossible : {$enCours} exemplaire(s) sont actuellement empruntés.",
            ]);
        }

        $livre->update($donnees);

        return redirect()->route('livres.index')->with('success', 'Livre modifié avec succès.');
    }

    public function destroy(Livre $livre): RedirectResponse
    {
        // La clé étrangère (restrict) bloquerait de toute façon : on affiche un message clair
        if ($livre->emprunts()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$livre->titre} » : il possède un historique d'emprunts.");
        }

        $livre->delete();

        return redirect()->route('livres.index')->with('success', 'Livre supprimé.');
    }

    private function valider(Request $request, ?Livre $livre = null): array
    {
        return $request->validate([
            'titre' => ['required', 'string', 'max:200'],
            'auteur' => ['required', 'string', 'max:150'],
            // unique, sauf pour le livre qu'on est en train de modifier
            'isbn' => ['required', 'string', 'max:20', Rule::unique('livres', 'isbn')->ignore($livre?->id)],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);
    }
}
