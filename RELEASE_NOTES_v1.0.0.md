# Comic Project v1.0.0

## Overview

Initial release of the Laravel comic reading platform.

## Public Features

- Comic discovery by listing, genre, and search
- Public comic details and published chapter reader
- Cover and page-image fallbacks

## User Features

- Registration, login, logout, and profile management
- Password changes
- Bookmarks, ratings, comments, and reading history

## Admin Features

- Dashboard metrics
- Comic, chapter, and page management
- Cover/page upload and replacement
- User role management
- Comment moderation

## Security/Hardening

- CSRF protection and session rotation
- Admin authorization middleware
- Validated uploads and escaped user content
- Publication-time access control
- Database-enforced reading-history identity
- Bounded public and admin search input
- Security response headers

## Media Handling

Comic covers and chapter pages use Laravel's public storage disk. Comic deletion removes descendant page media and the comic cover. Back up both the database and `storage/app/public/`.

## Responsive/Mobile

Responsive public and admin layouts include mobile navigation and table/form adaptations.

## Testing

The release suite passes with 242 tests and 678 assertions. Blade compilation and the Vite production build pass.

## Known Deferred Items

- HSTS until production HTTPS and certificate renewal are verified
- A stricter CSP after inline-asset compatibility review
- Browser-based responsive and full WCAG testing
- Deeper performance profiling and pagination review for large installations
- Search wildcard optimization and stronger media-path validation

## Deployment Notes

Use a production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, a unique `APP_KEY`, secure cookies, and production database credentials. Back up the database and uploaded media before migrations. Run the deployment checklist in `DEPLOYMENT_CHECKLIST.md`.
