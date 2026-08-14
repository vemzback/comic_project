@extends('layouts.app')

@section('title', 'Genres | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>Genres</h1>
            <p>Explore comics by genre.</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if ($genres->isEmpty())
                <p class="empty-state">No genres available yet.</p>
            @else
                <div class="genre-grid">
                    @foreach ($genres as $genre)
                        <a href="{{ route('genres') }}" class="genre-card">
                            <span class="genre-name">{{ $genre->name }}</span>
                            <span class="genre-count">{{ $genre->comics_count }} comic(s)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
