<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGatewayRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->session()->get('pharmalome_user');

        if (! is_array($user)) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login')->with('status', 'Connectez-vous pour accéder à cette page.');
        }

        if (! in_array($user['role'] ?? null, $roles, true)) {
            return redirect()->route('home')->withErrors([
                'access' => 'Votre compte ne dispose pas des droits nécessaires pour cette page.',
            ]);
        }

        if (($user['role'] ?? null) === 'pharmacien' && ($user['pharmacien_verifie'] ?? false) !== true) {
            return redirect()->route('compte')->withErrors([
                'access' => 'Votre compte pharmacien doit être vérifié par l’administration avant cet accès.',
            ]);
        }

        return $next($request);
    }
}
