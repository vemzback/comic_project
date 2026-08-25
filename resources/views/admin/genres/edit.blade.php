@extends('layouts.app')

@section('title', 'Edit Genre | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Genre Management</p>
                <h1>Edit Genre</h1>
                <p>Update {{ $genre->name }} without changing its comic relationships.</p>
            </div>
            <a href="{{ route('admin.genres.index') }}" class="btn btn-secondary">Back to Genre Management</a>
        </div>

        @if ($errors->any())
            <div class="form-error-box" role="alert">
                <ul class="form-error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.genres.update', $genre) }}" class="admin-card admin-form">
            @csrf
            @method('PUT')
            <fieldset class="admin-form-section">
                <legend>Genre Information</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $genre->name) }}" class="form-control" maxlength="255" required>
                        @error('name') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                    <div class="admin-field admin-field-wide">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control">{{ old('description', $genre->description) }}</textarea>
                        @error('description') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>
            <div class="admin-form-actions">
                <a href="{{ route('admin.genres.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Genre</button>
            </div>
        </form>
    </div>
@endsection