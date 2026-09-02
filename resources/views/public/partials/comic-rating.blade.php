<div class="comic-card-rating" aria-label="{{ $comic->ratings_count > 0 ? number_format((float) $comic->ratings_avg_score, 1) . ' out of 5 from ' . $comic->ratings_count . ' ratings' : 'Not rated yet' }}">
    <span class="comic-rating-star" aria-hidden="true">★</span>
    @if ($comic->ratings_count > 0)
        <strong>{{ number_format((float) $comic->ratings_avg_score, 1) }}</strong>
        <span>({{ $comic->ratings_count }})</span>
    @else
        <span>Not rated</span>
    @endif
</div>
