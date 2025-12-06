<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('inventory')->check()) {
            return redirect()->route('inventory.auth.login')
                ->with('error', 'Please login to access the inventory dashboard.');
        }

        return $next($request);
    }
}
