<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        // 🔐 Token protection
        if ($request->query('token') !== env('LOG_VIEW_TOKEN')) {
            abort(403, 'Unauthorized');
        }

        // 📄 Log file path
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return response('Log file not found', 404);
        }

        // ⛔ Prevent huge file crash (last 1 MB only)
        $content = File::get($logFile);
        $maxSize = 1024 * 1024;

        if (strlen($content) > $maxSize) {
            $content = substr($content, -$maxSize);
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
