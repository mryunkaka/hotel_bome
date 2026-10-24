<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $session = $request->session();
        $versionKey = 'auth_version_'.$user->getAuthIdentifier();
        $guardSessionKey = Auth::guard()->getName();

        if (! $user->canAuthenticate()
            || ($session->has($guardSessionKey)
                && (int) $session->get($versionKey, -1) !== (int) $user->auth_version)) {
            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login');
        }

        if (! $session->has($guardSessionKey)) {
            $session->put($versionKey, $user->auth_version);
        }

        return $next($request);
    }
}
