<?php

namespace App\Http\Middleware;

use App\Support\MemberResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $officerRoles = ['admin', 'Treasurer', 'Financial Secretary', 'Loan Committee', 'General Secretary'];
        $isAdmin = $user->hasAnyRole($officerRoles);
        
        if (!$isAdmin) {
            $member = MemberResolver::fromUser($user);
            if ($member) {
                $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi', 'Loan Committee', 'General Secretary'];
                $isAdmin = $member->roles()->whereIn('name', $adminRoles)->exists();
            }
        }

        if (!$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
