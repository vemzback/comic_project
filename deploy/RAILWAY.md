# Railway staging setup

This is the recommended low-maintenance staging path for zYx comic. It uses
the `codex/release-v1.1` GitHub branch, a Railway MySQL service, and a persistent
volume for uploaded covers, profile photos, and comic pages.

## Plan and region

Start with the Railway trial. Use the Hobby plan if the staging site needs to
remain available after the trial. Select the Southeast Asia (Singapore) region
for both the application and MySQL service.

The application needs two persistent data locations: the MySQL service storage
and the application media volume. Do not treat the application's ephemeral
filesystem as permanent storage.

## Project creation

1. Sign in to Railway using the GitHub account that can access this repository.
2. Create a new project from the `comic_project` GitHub repository.
3. Select the `codex/release-v1.1` source branch.
4. Add a MySQL service to the same Railway project.
5. Add a volume to the application service and mount it at:

   ```text
   /app/storage/app/public
   ```

6. Use `npm run build` as the custom build command.
7. Use the following pre-deploy command:

   ```text
   chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh
   ```

8. Set the health-check path to `/up`.
9. Generate a Railway domain under the application's Networking settings.
10. After the domain exists, set `APP_URL` to its full HTTPS URL and redeploy.

## Application variables

Generate `APP_KEY` locally with `php artisan key:generate --show`. Copy only the
result into Railway's Variables screen; never put the real key in Git.

Configure these application-service variables, replacing the example domain:

```text
APP_NAME=zYx comic
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://your-generated-domain.up.railway.app
APP_KEY=replace_in_railway_only

DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
PUBLIC_STORAGE_URL=/storage

LOG_CHANNEL=stderr
LOG_LEVEL=warning

ANILIST_API_URL=https://graphql.anilist.co
GOOGLE_BOOKS_API_URL=https://www.googleapis.com/books/v1/volumes
GOOGLE_BOOKS_API_KEY=replace_in_railway_only
GOOGLE_CLIENT_ID=replace_in_railway_only
GOOGLE_CLIENT_SECRET=replace_in_railway_only
GOOGLE_REDIRECT_URI=https://your-generated-domain.up.railway.app/auth/google/callback

MAIL_MAILER=log
```

If Railway names the database service something other than `MySQL`, update the
service name in the `DB_URL` reference.

Register the exact `GOOGLE_REDIRECT_URI` value as an authorized redirect URI in
Google Cloud Console. Google login remains unavailable until all three Google
OAuth variables are configured.

## Email limitation on Trial and Hobby

Railway disables outbound SMTP on Free, Trial, and Hobby plans. Keep
`MAIL_MAILER=log` for the first technical deployment; verification and reset
messages can then be inspected in the Railway application logs.

Before client acceptance testing, choose one of these options:

- Add an HTTPS API-based transactional email provider such as Resend.
- Upgrade to Railway Pro and configure SMTP.

Do not enter SMTP credentials on Trial or Hobby because the network connection
will be blocked even when the credentials are correct.

## Required checks

- The deployment and migration logs contain no errors.
- `/up` responds successfully over HTTPS.
- Covers and comic pages still load after a redeploy.
- A test upload remains present after a redeploy, proving the volume is mounted.
- MySQL is reached over Railway's private service reference.
- Registration, login, reader, comments, ratings, and admin import are tested.
- No real client or production data is copied into staging without approval.
