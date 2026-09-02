@extends('layouts.app')

@section('title', 'Reading History | zYx comic')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>My Reading History</h1>
            <p class="meta">Track your reading progress across comics</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if ($histories->isEmpty())
                <div class="empty-state">
                    <p>You haven't started reading any comics yet.</p>
                    <a href="{{ route('comics') }}" class="btn btn-primary">Browse Comics</a>
                </div>
            @else
                <div class="reading-history-list">
                    @foreach ($histories as $history)
                        <div class="history-item">
                            <div class="history-item-content">
                                <h3>
                                    <a href="{{ route('comic.detail', $history->comic) }}">
                                        {{ $history->comic->title }}
                                    </a>
                                </h3>
                                <p class="history-meta">
                                    @if ($history->chapter)
                                        Chapter {{ $history->chapter->chapter_number }}
                                    @else
                                        (Chapter removed)
                                    @endif
                                    @if ($history->page_number)
                                        • Page {{ $history->page_number }}
                                    @endif
                                </p>
                                <p class="history-time">
                                    Last read: {{ $history->last_read_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="history-item-actions">
                                @if ($history->chapter && $history->chapter->is_published)
                                    <a href="{{ route('chapter.reader', ['comic' => $history->comic, 'chapter' => $history->chapter]) }}@if($history->page_number)?page={{ $history->page_number }}@endif" 
                                       class="btn btn-primary">
                                        Continue Reading
                                    </a>
                                @else
                                    <a href="{{ route('comic.detail', $history->comic) }}" class="btn btn-secondary">
                                        View Comic
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

@endsection
