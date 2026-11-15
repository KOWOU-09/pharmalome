<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = env('INTERNAL_KEY', 'change-me-internal-key');
        $provided = $request->header('X-Internal-Key');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Clé interne invalide ou absente.',
            ], 403);
        }

        return $next($request);
    }
}
