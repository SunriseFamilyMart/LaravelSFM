<?php

namespace App\Http\Controllers\Inventory\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\CentralLogics\Helpers;

class LoginController extends Controller
{
    // Show the login form
    public function showLoginForm()
    {
        $logoName = Helpers::get_business_settings('logo');
        $logo = Helpers::onErrorImage($logoName, asset('storage/app/public/restaurant') . '/' . $logoName, asset('public/assets/admin/img/160x160/Zone99.png'), 'restaurant/');

        return view('inventory.auth.login', compact('logo')); // create this Blade
    }

    // Handle login request
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('inventory')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('inventory.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->withInput($request->only('email'));
    }

    // Logout inventory
    public function logout(Request $request)
    {
        Auth::guard('inventory')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inventory.auth.login');
    }
}
