<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SectionPasswordMiddleware
{
    public function handle(Request $request, Closure $next, $section)
    {
        // Already verified?
        if (Session::get('section_access_' . $section)) {
            return $next($request);
        }

        // If not verified, redirect to password entry form
        return redirect()->route('admin.section.password', ['section' => $section]);
    }
}
