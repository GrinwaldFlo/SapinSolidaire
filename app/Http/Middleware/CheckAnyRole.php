<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnyRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        if ($request->user()->hasRole(Role::VISITOR) && ! $request->user()->hasAnyRole([Role::ADMIN, Role::VALIDATOR, Role::ORGANIZER, Role::RECEPTION])) {
            return redirect()->route('dashboard');
        }

        abort(403, 'Accès non autorisé.');
    }
}
