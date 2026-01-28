@extends('layouts.admin.app')
@section('title', 'Roles Access Management')


@section('content')
    <div class="container py-3">
        <h2>Roles Access</h2>

        <a href="{{ route('admin.roles-access.create') }}" class="btn btn-primary mb-3">Add New Role</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($roles as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->name }}</td>
                        <td>{{ $r->role }}</td>
                        <td>{{ $r->email }}</td>
                        <td>
                            <a href="{{ route('admin.roles-access.show', $r->id) }}" class="btn btn-info btn-sm">View</a>
                            <a href="{{ route('admin.roles-access.edit', $r->id) }}" class="btn btn-warning btn-sm">Edit</a>

                            <form action="{{ route('admin.roles-access.destroy', $r->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button onclick="return confirm('Delete this role?')" class="btn btn-danger btn-sm">
                                    Delete
                                </button>
                            </form>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
