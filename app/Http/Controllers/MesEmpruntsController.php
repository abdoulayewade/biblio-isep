<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MesEmpruntsController extends Controller
{
    /**
     * Historique des emprunts de l'utilisateur connecté (en cours + rendus).
     */
    public function __invoke(Request $request): View
    {
        $emprunts = $request->user()
            ->emprunts()
            ->with('livre.category')
            ->orderByRaw('date_retour_effective IS NOT NULL') // les emprunts en cours d'abord
            ->latest('date_emprunt')
            ->paginate(10);

        return view('emprunts.mes-emprunts', compact('emprunts'));
    }
}
