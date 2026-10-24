<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoopbackRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)
                && ! $request->headers->has('X-Forwarded-For')
                && ! $request->headers->has('Forwarded'),
            404,
        );

        return $next($request);
    }
}
