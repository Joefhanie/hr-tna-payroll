<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TemporaryAssignment;
use Symfony\Component\HttpFoundation\Response;

class RevertExpiredRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for expired temporary roles and revert them
        if (Auth::check()) {
            $user = Auth::user();

            // Find expired temporary roles for this user
            $expiredRoles = TemporaryAssignment::where('user_id', $user->id)
                ->expired()
                ->get();

            foreach ($expiredRoles as $expired) {
                // Revert to original role
                $user->update(['role' => $expired->original_role]);

                // Mark as inactive
                $expired->update(['is_active' => false]);
            }

            // Check if there's an active temporary assignment
            $activeRole = TemporaryAssignment::where('user_id', $user->id)
                ->active()
                ->latest()
                ->first();

            if ($activeRole && $user->role != $activeRole->temporary_role) {
                $user->update(['role' => $activeRole->temporary_role]);
            }
        }

        return $next($request);
    }
}
