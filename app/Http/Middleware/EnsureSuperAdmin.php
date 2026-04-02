<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('admin_role') !== 'super_admin') {
            $tab = $request->input('tab');
            $allowed = ['settings', 'users', 'transactions', 'partner-payouts'];
            if (! is_string($tab) || ! in_array($tab, $allowed, true)) {
                $tab = 'settings';
            }

            return redirect()
                ->route('admin.dashboard', ['tab' => $tab])
                ->with('error', 'Only a super administrator can perform this action.');
        }

        return $next($request);
    }
}
