@extends('layouts.admin.app')
@section('title', 'Add Role Access')

@section('content')
    <div class="container py-3">
        <h2>Add Role Access</h2>

        <form action="{{ route('admin.roles-access.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Role</label>
                <input type="text" name="role" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-success">Save</button>
        </form>
    </div>
@endsection
