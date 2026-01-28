<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SectionPassword;
use Illuminate\Support\Facades\Hash;

class SectionPasswordController extends Controller
{
    public function showForm($section)
    {
        return view('admin.section-password', compact('section'));
    }

    public function verify(Request $request, $section)
    {
        $request->validate(['password' => 'required']);

        $record = SectionPassword::where('section_key', $section)->first();

        if (!$record || !Hash::check($request->password, $record->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        // Mark section as verified
        session(["section_verified_{$section}" => true]);

        // Redirect back to intended section
        return redirect()->intended();
    }
}

