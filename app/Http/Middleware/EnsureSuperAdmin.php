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
            return redirect()
                ->route('admin.dashboard', ['tab' => 'users'])
                ->with('error', 'Only a super administrator can add user balance.');
        }

        return $next($request);
    }
}
