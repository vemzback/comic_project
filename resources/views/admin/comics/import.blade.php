@extends('layouts.app')

@section('title', 'Import Comic Metadata | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Comic Management</p>
                <h1>Import Metadata</h1>
                <p>Search a trusted provider, preview the result, and import it as an unpublished draft.</p>
            </div>
            <a href="{{ route('admin.comics.index') }}" class="btn btn-secondary">Back to Comic Management</a>
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

        @if ($apiError)
            <div class="form-error-box" role="alert">{{ $apiError }}</div>
        @endif

        <form method="GET" action="{{ route('admin.comics.import.index') }}" class="admin-card admin-import-search">
            <div class="admin-field">
                <label for="provider" class="form-label">Metadata Provider</label>
                <select id="provider" name="provider" class="form-control">
                    @foreach ($providers as $key => $label)
                        <option value="{{ $key }}" @selected($providerKey === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-field admin-import-query">
                <label for="q" class="form-label">Comic or Manga Title</label>
                <input id="q" type="search" name="q" value="{{ $query }}" class="form-control" minlength="2" maxlength="120" placeholder="Example: One Piece or Batman" required>
            </div>
            <button type="submit" class="btn btn-primary">Search Provider</button>
        </form>

        <div class="admin-import-notice">
            <strong>Metadata only.</strong>
            Covers are copied to local storage after import. Comic pages are never downloaded by this feature.
        </div>

        @if ($query !== '')
            <section aria-labelledby="import-results-heading">
                <div class="admin-card-header admin-import-results-heading">
                    <div>
                        <h2 id="import-results-heading">Search Results</h2>
                        <p>{{ count($results) }} {{ count($results) === 1 ? 'result' : 'results' }} for “{{ $query }}”</p>
                    </div>
                </div>

                @if (empty($results) && ! $apiError)
                    <div class="admin-card admin-empty-state">
                        <h2>No matching titles</h2>
                        <p>Try another title or switch metadata providers.</p>
                    </div>
                @else
                    <div class="admin-import-grid">
                        @foreach ($results as $result)
                            @php($alreadyImported = in_array($result['external_id'], $importedIds, true))
                            <article class="admin-card admin-import-card">
                                <div class="admin-import-cover">
                                    @if ($result['cover_url'])
                                        <img src="{{ $result['cover_url'] }}" alt="{{ $result['title'] }} provider cover" loading="lazy" referrerpolicy="no-referrer">
                                    @else
                                        <div class="admin-import-cover-placeholder">No cover</div>
                                    @endif
                                </div>
                                <div class="admin-import-copy">
                                    <div>
                                        <span class="status-badge status-{{ $result['status'] }}">{{ ucfirst($result['status']) }}</span>
                                        <h3>{{ $result['title'] }}</h3>
                                        <p class="admin-import-byline">{{ $result['author'] ?: 'Creator unavailable' }}</p>
                                    </div>

                                    @if ($result['description'])
                                        <p class="admin-import-description">{{ Str::limit($result['description'], 220) }}</p>
                                    @endif

                                    @if (! empty($result['genres']))
                                        <p class="admin-import-genres">{{ implode(' · ', array_slice($result['genres'], 0, 5)) }}</p>
                                    @endif

                                    <div class="admin-import-actions">
                                        @if ($result['source_url'])
                                            <a href="{{ $result['source_url'] }}" target="_blank" rel="noreferrer noopener" class="btn btn-secondary">View Source</a>
                                        @endif

                                        <form method="POST" action="{{ route('admin.comics.import.store') }}">
                                            @csrf
                                            <input type="hidden" name="provider" value="{{ $result['provider'] }}">
                                            <input type="hidden" name="external_id" value="{{ $result['external_id'] }}">
                                            <button type="submit" class="btn btn-primary" @disabled($alreadyImported)>
                                                {{ $alreadyImported ? 'Already Imported' : 'Import as Draft' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
