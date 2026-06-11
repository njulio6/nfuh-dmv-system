<?php

namespace App\Http\Middleware;

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

        $member = \App\Models\Member::where('email', $user->email)->first();
        $isAdmin = false;
        
        if (!$member) {
            $isAdmin = true; // Users with no member record are treated as global admins
        } else {
            $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi'];
            $isAdmin = $member->roles()->whereIn('name', $adminRoles)->exists();
        }

        if (!$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
