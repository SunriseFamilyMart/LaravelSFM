<?php

namespace App\Http\Controllers\Sales\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\RolesAccess; // or your user model
use Illuminate\Http\JsonResponse;

class SalesAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        // Find user by email
        $user = RolesAccess::where('email', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid credentials.');
        }

        // Store user in session
        session([
            'sales_logged_in' => true,
            'sales_user_id' => $user->id,
            'sales_user_role' => $user->role,
            'sales_user_name' => $user->name,
        ]);

        return redirect()->route('sales.dashboard')->with('success', 'Login successful');
    }

}
