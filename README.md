<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Production Safety

Before deploying, configure the production environment separately from this local development template:

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<secure deployment secret>
APP_URL=https://your-domain
SESSION_SECURE_COOKIE=true
```

Production requirements:

- Enforce HTTPS at the web server or reverse proxy.
- Configure database credentials securely.
- Never commit `.env` or other secrets.
- Do not use the demo seeders for production provisioning. `ComicPlatformSeeder` refuses to run in production.
- Do not regenerate `APP_KEY` after deployment without understanding the effect on encrypted data and sessions.
- Review production mail, logging, cache, queue, and filesystem settings before serving traffic.

## Production Deployment

Run the deployment steps in this order:

1. Install production PHP dependencies:

	```text
	composer install --no-dev --optimize-autoloader
	```

2. Configure the production environment securely. Use `APP_ENV=production`, `APP_DEBUG=false`, a configured `APP_KEY`, an HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, and valid production database credentials.

3. Install and build frontend assets:

	```text
	npm ci
	npm run build
	```

4. Run database migrations:

	```text
	php artisan migrate --force
	```

	Do not run the demo seeders for production provisioning. Hardening A blocks `ComicPlatformSeeder` in production.

5. Configure public storage:

	```text
	php artisan storage:link
	```

	Comic covers and page images use Laravel's public storage disk and require this link or an equivalent persistent production storage arrangement.

6. Ensure the web/PHP process has appropriate write access to:

	```text
	storage/
	bootstrap/cache/
	```

	Do not use insecure permissions such as `chmod 777`.

7. Optimize Laravel:

	```text
	php artisan optimize
	```

	This repository has been verified compatible with route, configuration, view, and event caching through this command. `php artisan optimize:clear` may require database connectivity when `CACHE_STORE=database`.

8. Verify that the application loads, database connectivity works, login and admin access work, storage media URLs resolve, and production debug mode is disabled.

## Nginx / HTTPS Deployment

The parameterized Nginx deployment template is available at:

```text
deploy/nginx/comic-platform.conf.example
```

Before enabling it, replace these placeholders with deployment-specific values:

- `<DOMAIN>`: canonical HTTPS hostname
- `<PROJECT_PUBLIC_PATH>`: absolute path to this project's `public/` directory
- `<PHP_FPM_ENDPOINT>`: PHP-FPM socket or TCP endpoint
- `<TLS_CERTIFICATE_PATH>`: certificate path
- `<TLS_PRIVATE_KEY_PATH>`: private key path

The Nginx document root must remain the project's `public/` directory. The template handles the HTTP-to-HTTPS redirect, Laravel front-controller fallback, PHP-FPM routing, hidden-file protection, ACME challenge paths, and a 3 MB request limit for the application's 2 MB image validation limit. It does not enable HSTS or duplicate Laravel response headers.

For production, use `APP_URL=https://<DOMAIN>`, `SESSION_SECURE_COOKIE=true`, and `APP_DEBUG=false`. Run `php artisan storage:link` so `public/storage` points to `storage/app/public`; keep `storage/` and `bootstrap/cache/` writable by PHP-FPM without making `public/` or the repository world-writable. PHP `upload_max_filesize` and `post_max_size` must allow the 2 MB application limit plus multipart overhead.

The current v1.0 feature set does not require a queue worker, scheduler, or SMTP service. Enable HSTS only after HTTPS, the canonical domain, and certificate renewal have been verified stable; do not enable `includeSubDomains` or `preload` in this phase.

## Operations Runbook

### Backup Policy

Production backups are required for both the database and uploaded media. Back up the application database before every production migration and retain multiple recovery points outside the live VPS. A successful backup is not enough: periodically verify that backup files are readable and perform a restore test.

Back up `storage/app/public/`, including comic covers and chapter/page images. A database backup alone cannot restore the complete application because uploaded media is not stored in the database. Caches, compiled views, build output, sessions, and logs are operational data and can be regenerated or recreated; logs may still need separate retention for incident review.

### Restore Procedure

For a clean recovery:

1. Restore the application source or release and the matching production environment configuration.
2. Install production Composer dependencies with `composer install --no-dev --optimize-autoloader`.
3. Install/build frontend dependencies and assets with `npm ci` and `npm run build`.
4. Restore the database backup and uploaded media backup.
5. Verify ownership and permissions for `storage/` and `bootstrap/cache/`, then run `php artisan storage:link` when the public storage link is absent.
6. Apply only migrations newer than the restored database state with `php artisan migrate --force`. Do not blindly replay historical migrations.
7. Rebuild Laravel caches with `php artisan optimize`.
8. Complete the restoration checks below.

### Migration and Rollback Safety

**BACKUP BEFORE MIGRATION.** Code rollback and database rollback are separate operations. A failed release should first restore the previous application code, matching Composer dependencies, and matching frontend assets. Evaluate database changes separately; if a migration changed production data incompatibly, restore a verified backup or use a specifically reviewed corrective migration.

Do not use `php artisan migrate:rollback` as a universal production recovery command. Rollback methods can remove tables, columns, and data.

### Logging and Disk Monitoring

Laravel writes application logs under `storage/logs/`. Production must provide log retention, rotation, disk-growth control, and regular error review. Rotation may be handled by deployment-level tooling such as `logrotate`, journald, or external log collection; this repository does not automate it.

Monitor growth of uploaded media, application logs, the database, database sessions/cache, and deployment/build artifacts. A full disk can cause uploads, logs, database writes, cache/session writes, and deployments to fail.

Minimum v1.0 monitoring should cover HTTP uptime, `/up`, database availability, disk usage, Laravel errors, Nginx/PHP-FPM errors, TLS certificate expiry, backup success, and periodic restore verification.

### Failure Response

| Failure | First verification | Safe recovery direction |
|---|---|---|
| Database unavailable | Check database service, connectivity, and application error logs | Restore database service or fail over using the approved operations plan; do not change credentials blindly |
| Storage unwritable | Check PHP-FPM ownership/permissions for `storage/` and `bootstrap/cache/` | Correct owner/group permissions without making directories world-writable |
| Disk full | Check filesystem usage and largest growing paths | Preserve evidence, rotate/retain logs safely, and expand or clean approved operational data |
| Storage symlink missing | Check `public/storage` and `storage/app/public/` | Run `php artisan storage:link` after confirming the target and permissions |
| Build assets missing | Check `public/build/` and the deployment build output | Restore matching built assets or rebuild from the matching release |
| Session/cache database failure | Check the database and `sessions`/`cache` tables | Restore database availability; do not silently switch production stores |
| Logging failure | Check `storage/logs/` permissions, disk space, and PHP-FPM/Nginx logs | Restore writable logging paths and retain infrastructure-level error evidence |

### Release Checklist

Before deployment:

- Verified database backup exists and is readable.
- Off-host backup copy is available.
- Sufficient disk space is available.
- Production environment and HTTPS certificate are configured.
- Database connectivity is confirmed.

During deployment:

- Install Composer production dependencies.
- Build frontend assets.
- Run `php artisan migrate --force` only after backup verification.
- Run `php artisan storage:link`.
- Verify runtime ownership for `storage/` and `bootstrap/cache/`.
- Run `php artisan optimize`.

After deployment, verify:

- `/up` responds.
- Homepage and database-backed comic pages work.
- Normal-user and admin login work.
- Comic reader, cover images, and chapter/page images load.
- HTTPS redirect and C3A security headers are present.
- Sessions persist across requests.
- Logs can be written and reviewed.
- Migration status is expected.
- Disk usage is healthy.

The current v1.0 release does not require a queue worker, scheduler, or SMTP service. Password recovery remains deferred. Deleting a comic may leave cascaded chapter/page image files orphaned; cleanup automation is post-v1.0 work. An intermittent `ChapterFactory` uniqueness collision remains a separate test-engineering issue and is not fixed by this runbook.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
