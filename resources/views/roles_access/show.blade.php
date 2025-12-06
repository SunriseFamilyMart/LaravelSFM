@extends('layouts.admin.app')
@section('title', 'Role Details')

@section('content')
    <div class="container py-3">
        <h2>Role Details</h2>

        <p><strong>Name:</strong> {{ $roles_access->name }}</p>
        <p><strong>Role:</strong> {{ $roles_access->role }}</p>
        <p><strong>Email:</strong> {{ $roles_access->email }}</p>

        <a href="{{ route('admin.roles-access.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection
