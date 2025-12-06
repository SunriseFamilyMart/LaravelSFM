@extends('layouts.admin.app')
@section('title', 'Edit Role Access')

@section('content')
    <div class="container py-3">
        <h2>Edit Role</h2>

        <form action="{{ route('admin.roles-access.update', $roles_access->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" value="{{ $roles_access->name }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Role</label>
                <input type="text" name="role" value="{{ $roles_access->role }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" value="{{ $roles_access->email }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>New Password (optional)</label>
                <input type="password" name="password" class="form-control">
            </div>

            <button class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection
