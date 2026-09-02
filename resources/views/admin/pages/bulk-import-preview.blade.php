@extends('layouts.app')

@section('title', 'Review Bulk Import | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Pages</p>
                <h1>Review Page Order</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong> / Chapter: <strong>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong></p>
            </div>
            <div class="bulk-preview-summary" aria-label="Import summary">
                <strong>{{ count($manifest['pages']) }}</strong>
                <span>{{ count($manifest['pages']) === 1 ? 'page detected' : 'pages detected' }}</span>
            </div>
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

        <section class="admin-card bulk-preview-section" aria-labelledby="page-order-heading">
            <div class="admin-card-header">
                <h2 id="page-order-heading">Page Preview</h2>
                <p>Pages are naturally sorted by filename. Confirm that this order matches the intended reading order.</p>
            </div>

            <div class="bulk-preview-grid">
                @foreach ($manifest['pages'] as $page)
                    <article class="bulk-preview-card">
                        <div class="bulk-preview-image-wrap">
                            <img
                                src="{{ route('admin.comics.chapters.pages.bulk.image', [$comic, $chapter, $manifest['token'], $page['position']]) }}"
                                alt="Preview of page {{ $page['position'] }}"
                                loading="lazy"
                            >
                            <span class="bulk-preview-number">Page {{ $page['position'] }}</span>
                        </div>
                        <div class="bulk-preview-meta">
                            <strong title="{{ $page['original_name'] }}">{{ $page['original_name'] }}</strong>
                            <span>{{ $page['width'] }} × {{ $page['height'] }} px · {{ number_format($page['size'] / 1024) }} KB</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="bulk-preview-actions">
            <form method="POST" action="{{ route('admin.comics.chapters.pages.bulk.store', [$comic, $chapter, $manifest['token']]) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Confirm and Import {{ count($manifest['pages']) }} Pages</button>
            </form>

            <form method="POST" action="{{ route('admin.comics.chapters.pages.bulk.destroy', [$comic, $chapter, $manifest['token']]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Discard and Upload Again</button>
            </form>

            <p>This preview expires in 60 minutes. Nothing has been added to the chapter yet.</p>
        </div>
    </div>
@endsection
