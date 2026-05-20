<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $permissions = explode(',', $permission);
        $hasAny = false;
        foreach ($permissions as $p) {
            if ($user->hasPermission(trim($p))) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            abort(403, 'Unauthorized action. You do not have the required permission: ' . $permission);
        }

        return $next($request);
    }
}
