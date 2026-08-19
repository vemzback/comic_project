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
