<?php

namespace App\Http\Middleware;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        if ($user->is_platform_admin) {
            return $next($request);
        }

        $routeProperty = $request->route('property');
        $propertyId = $routeProperty instanceof Property ? $routeProperty->id : filter_var($routeProperty, FILTER_VALIDATE_INT);
        abort_unless(is_int($propertyId) && $propertyId > 0, 404);

        $hasAccess = DB::table('property_user')
            ->join('properties', 'properties.id', '=', 'property_user.property_id')
            ->where('property_user.user_id', $user->id)
            ->where('property_user.property_id', $propertyId)
            ->where('property_user.role', $role)
            ->where('properties.is_active', true)
            ->exists();

        abort_unless($hasAccess, 404);

        return $next($request);
    }
}
