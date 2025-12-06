@extends('layouts.admin.app')

@section('title', translate('Create Stores'))

@section('content')
    <div class="container-fluid py-3">
        <h2>Add Store</h2>

        <form action="{{ route('admin.stores.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('stores.partials.form')

            <button type="submit" class="btn btn-success mt-3">Save</button>
            <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
@endsection
