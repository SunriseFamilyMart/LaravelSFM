@extends('layouts.admin.app')

@section('title', translate('Edit Stores'))

@section('content')
    <div class="container-fluid py-3">
        <h2>Edit Store</h2>

        <form action="{{ route('admin.stores.update', $store->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('stores.partials.form')

            <button type="submit" class="btn btn-primary mt-3">Update</button>
            <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
@endsection
