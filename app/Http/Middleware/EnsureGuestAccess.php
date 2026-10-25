<?php

namespace App\Http\Middleware;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestAccess
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        $property = $request->route('property');
        if (! $property instanceof Property) {
            $property = Property::query()->find(filter_var($property, FILTER_VALIDATE_INT));
        }
        abort_unless($property instanceof Property && $property->is_active, 404);

        if ($user->is_platform_admin) {
            return $next($request);
        }

        $hasOwnerRole = $user->hasGuestOwnerAccess();

        $allowedRoles = match ($action) {
            'view' => ['receptionist', 'supervisor'],
            'activity', 'report' => ['supervisor'],
            'create', 'update', 'deactivate' => ['receptionist', 'supervisor'],
            'validate' => ['supervisor'],
            'approve' => [],
            default => [],
        };

        if ($hasOwnerRole && in_array($action, ['view', 'activity', 'report', 'create', 'update', 'deactivate', 'validate', 'approve'], true)) {
            return $next($request);
        }

        $hasPropertyRole = $allowedRoles !== [] && DB::table('property_user')
            ->where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->whereIn('role', $allowedRoles)
            ->exists();

        abort_unless($hasPropertyRole, 404);

        return $next($request);
    }
}
