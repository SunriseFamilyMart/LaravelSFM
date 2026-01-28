<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Store token auth for Store self app.
 * Header: Authorization: Bearer <token> OR raw token (same as existing Sales APIs).
 */
class StoreAuthToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization') ?: $request->header('X-Store-Token');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Missing token.'], 401);
        }

        // Support both:
        // - Authorization: Bearer <token>
        // - X-Store-Token: <token>
        $token = str_replace('Bearer ', '', $token);
        $token = trim($token);
$store = Store::where('auth_token', $token)->first();
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid token.'], 401);
        }

        // Prevent access if admin disabled login later
        // NOTE: can_login is stored as tinyint(1) and must be treated as boolean.
        $approved = ($store->approval_status ?? 'approved') === 'approved';
        $canLogin = (bool) ($store->can_login ?? true);
        if (!$canLogin || !$approved) {
            return response()->json(['success' => false, 'message' => 'Account not active. Contact admin.'], 403);
        }

        // Attach for controllers
        $request->attributes->set('auth_store', $store);

        return $next($request);
    }
}
