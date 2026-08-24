# v1.0.0 Deployment Checklist

## Before Deployment

- [ ] Create and verify a readable database backup.
- [ ] Back up `storage/app/public/` and retain an off-host copy.
- [ ] Prepare a production `.env`; do not copy the local `.env`.
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Set a unique `APP_KEY` and the correct HTTPS `APP_URL`.
- [ ] Set `SESSION_SECURE_COOKIE=true`.
- [ ] Verify database credentials and connectivity.
- [ ] Verify HTTPS certificates, renewal, and web-server configuration.
- [ ] Verify PHP write access to `storage/` and `bootstrap/cache/`.
- [ ] Confirm sufficient disk space.

## Deployment

- [ ] Run `composer install --no-dev --optimize-autoloader`.
- [ ] Run `php artisan migrate --force` only after backup verification.
- [ ] Run `php artisan storage:link`.
- [ ] Run `npm ci` and `npm run build`, or deploy matching built assets.
- [ ] Run `php artisan optimize`.
- [ ] Verify the web server document root is `public/`.

## Post Deployment

- [ ] Smoke-test `/up` and the homepage.
- [ ] Test registration, login, logout, and profile/password changes.
- [ ] Test admin login, dashboard, and authorization boundaries.
- [ ] Test comic detail, reader navigation, and media URLs.
- [ ] Test comic/chapter/page upload, replacement, and deletion.
- [ ] Test bookmarks, ratings, comments, moderation, and reading history.
- [ ] Confirm backups are readable and restore procedures are documented.
- [ ] Review Laravel and web-server logs.
- [ ] Check disk usage and uploaded-media growth.
- [ ] Confirm migration status and cache health.
