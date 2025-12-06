<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalesAuth
{
    public function handle(Request $request, Closure $next)
    {
        // If not logged in, redirect to login page
        if (!session('sales_logged_in')) {
            return redirect()->route('sales.login')->with('error', 'Please login first.');
        }

        return $next($request);
    }
}
