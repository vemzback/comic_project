# zYx comic staging deployment

This guide deploys the `v1.1.0-rc2` release candidate to a separate staging
environment. Staging must not share its database, uploaded media, credentials,
or application key with production.

## Information required from the hosting provider

- Staging hostname, for example `staging.example.com`
- SSH access and the absolute project path
- PHP 8.2 or newer with the extensions required by Laravel, image uploads, and
  `ZipArchive`
- Composer, Node.js, npm, Nginx, and PHP-FPM
- A dedicated MariaDB/MySQL database and user
- An SMTP account for email verification and password-reset testing
- TLS certificate paths, or access to issue a certificate

## Prepare the release

1. Clone or fetch the repository on the server.
2. Check out the exact `v1.1.0-rc2` tag.
3. Install dependencies and build the assets:

   ```text
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

4. Copy `deploy/staging.env.example` to `.env`. Replace all example hostnames,
   database values, and SMTP values. Do not copy the local development `.env`.
5. Generate a staging-only application key once:

   ```text
   php artisan key:generate
   ```

   Keep this key for the lifetime of the staging environment. Changing it
   invalidates existing sessions and encrypted application data.

The PHP configuration must allow the chapter archive request to reach Laravel.
Use an `upload_max_filesize` of at least `40M` and a `post_max_size` of at least
`42M`, then restart PHP-FPM after changing its configuration.

## Configure the application

Run these commands only after confirming that `.env` points to the dedicated
staging database:

```text
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Give the web/PHP process write access to `storage/` and `bootstrap/cache/`.
Do not make the project or its public directory world-writable.

Copy `deploy/nginx/comic-platform.conf.example` to the server's Nginx
configuration and replace every angle-bracket placeholder. The Nginx document
root must be the project's `public/` directory. Enable the site only after the
configuration test succeeds.

## Create the initial staging administrator

Register a normal account through the application, verify its email, and then
assign the `admin` role using the approved server-side provisioning procedure.
Never expose a public endpoint that promotes users to administrator.

## Smoke test

Verify all of the following over HTTPS:

- `/up` responds successfully.
- The homepage, catalog, genres, and search pages load.
- Registration, email verification, login, logout, and password reset work.
- A normal user cannot access administrator routes.
- An administrator can import metadata and upload a chapter ZIP preview.
- Publishing readiness blocks incomplete comics.
- Covers and comic pages load through `/storage`.
- Reader navigation and reading progress work on desktop and mobile.
- Comments, replies, ratings, bookmarks, and reading history update correctly.
- `APP_DEBUG` is disabled and errors do not expose stack traces.

## Client acceptance test

Provide the client with the staging URL and separate normal-user and
administrator test accounts. Record requested changes as release-candidate
feedback. Do not edit production data while resolving staging feedback.

## Safe rollback

If staging fails before it contains valuable test data, restore the previous
release checkout and its matching dependencies/assets. If migrations or test
content must be preserved, back up both the staging database and
`storage/app/public/` before changing the release. Do not treat
`php artisan migrate:rollback` as a universal recovery command.
