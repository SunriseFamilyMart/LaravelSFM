<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RolesAccess;
use Illuminate\Support\Facades\Hash;

class RolesAccessController extends Controller
{
    public function index()
    {
        $roles = RolesAccess::latest()->get();
        return view('roles_access.index', compact('roles'));
    }

    public function create()
    {
        return view('roles_access.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'email' => 'required|email|unique:roles_access,email',
            'password' => 'required|min:6',
        ]);

        RolesAccess::create([
            'name' => $request->name,
            'role' => $request->role,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.roles-access.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(RolesAccess $roles_access)
    {
        return view('roles_access.show', compact('roles_access'));
    }

    public function edit(RolesAccess $roles_access)
    {
        return view('roles_access.edit', compact('roles_access'));
    }

    public function update(Request $request, RolesAccess $roles_access)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'email' => 'required|email|unique:roles_access,email,' . $roles_access->id,
        ]);

        $roles_access->name = $request->name;
        $roles_access->role = $request->role;
        $roles_access->email = $request->email;

        if ($request->password) {
            $request->validate(['password' => 'min:6']);
            $roles_access->password = Hash::make($request->password);
        }

        $roles_access->save();

        return redirect()->route('admin.roles-access.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(RolesAccess $roles_access)
    {
        $roles_access->delete();

        return redirect()->route('admin.roles-access.index')
            ->with('success', 'Role deleted successfully.');
    }
}
