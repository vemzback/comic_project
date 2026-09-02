<section class="admin-card publication-readiness {{ $publicationReadiness['ready'] ? 'publication-readiness-ready' : 'publication-readiness-blocked' }}" aria-labelledby="publication-readiness-heading">
    <div class="publication-readiness-header">
        <div>
            <p class="eyebrow">Publishing Safety</p>
            <h2 id="publication-readiness-heading">Publication Readiness</h2>
            <p>
                {{ $publicationReadiness['ready']
                    ? 'This comic has the required content and can be published safely.'
                    : 'Complete the items below before publishing this comic.' }}
            </p>
        </div>
        <span class="readiness-badge {{ $publicationReadiness['ready'] ? 'is-ready' : 'is-blocked' }}">
            {{ $publicationReadiness['ready'] ? 'Ready to publish' : count($publicationReadiness['blockers']).' blocking '.(count($publicationReadiness['blockers']) === 1 ? 'issue' : 'issues') }}
        </span>
    </div>

    <ul class="publication-checklist">
        @foreach ($publicationReadiness['checks'] as $check)
            <li class="{{ $check['passed'] ? 'is-complete' : 'is-incomplete' }}">
                <span class="publication-check-icon" aria-hidden="true">{{ $check['passed'] ? '✓' : '!' }}</span>
                <span>
                    <strong>{{ $check['label'] }}</strong>
                    <small>{{ $check['message'] }}</small>
                </span>
            </li>
        @endforeach
    </ul>

    @if ($publicationReadiness['warnings'])
        <div class="publication-warnings">
            <strong>Recommended before launch</strong>
            <ul>
                @foreach ($publicationReadiness['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
