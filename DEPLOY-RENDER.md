# Deploy Aura Quotes Beta: GitHub, Docker, Apache and Render

This package is the English dark-themed Aura Quotes application with the existing account, quote, resource, interest, collection and reporting features. Music is not included.

## What runs where

- Git records source changes; GitHub stores the repository.
- Render builds the Dockerfile and runs PHP 8.5 with Apache.
- Apache serves only the public directory on Render's assigned PORT.
- Neon PostgreSQL stores accounts, quotes, sessions, interests, collections and reports outside the application container.
- No Node.js build, local Docker installation, Composer installation on your PC, or paid service is required for this Beta workflow. Render installs the locked production dependencies in the image.

## 1. Extract and publish the source to GitHub

Extract the ZIP to a short folder, for example C:\AR. Dockerfile and render.yaml must be directly inside that folder.

Create an empty private repository on GitHub, for example aura-quotes. Do not initialize it with a README or .gitignore. In PowerShell, run the following commands from the extracted folder. Replace YOUR-USERNAME with your own GitHub username:

```powershell
cd C:\AR
git init -b codex/render-beta
git add .
git commit -m "Prepare Aura Quotes Render beta"
git remote add origin https://github.com/YOUR-USERNAME/aura-quotes.git
git push -u origin codex/render-beta
```

If Git requests your identity, configure your own GitHub name and email, then repeat the commit. Complete GitHub's browser sign-in when requested. The first pushed branch becomes the repository's default branch. GitHub Desktop can also publish the local repository if you prefer a graphical interface.

The package excludes .env, SQLite databases and vendor. The ignore rules keep secrets, local databases, logs and generated caches out of Git. Do not upload the older local-run ZIP or any private .env file.

## 2. Create the free persistent database

1. Sign in at https://neon.com and select the Free plan.
2. Create a project named Aura Quotes in an available Frankfurt region to match the Render region, if available.
3. Open Connect, select the database/role and copy the PostgreSQL connection URL. A direct connection is sufficient for this small Beta. Preserve sslmode=require in the URL. Copy only the URL, without a psql command or surrounding quotes.
4. Keep the URL private. Enter it only in Render's DB_URL environment variable; do not commit it to GitHub or send it in a chat.

Example shape only (not a working credential):

```text
postgresql://USER:PASSWORD@HOST/DATABASE?sslmode=require
```

Neon's Free plan has no fixed trial end date and has resource limits. Render's own free PostgreSQL database expires after 30 days, so it is not the default used here.

## 3. Deploy the Render Blueprint

1. Sign in at https://dashboard.render.com.
2. Choose New > Blueprint and connect the GitHub repository. Authorize access to that repository.
3. Select the branch that contains render.yaml (codex/render-beta if you followed the commands above).
4. Render reads render.yaml. Confirm that the web service plan is Free and the runtime is Docker. The file does not create paid resources or a Render database.
5. When prompted for DB_URL, paste the private Neon connection URL from step 2.
6. Create/deploy the Blueprint and wait for the build and startup logs.
7. Open the HTTPS onrender.com address displayed by Render. APP_URL uses Render's supplied address automatically.

Keep the Docker command at its default. Do not enter npm run build, php artisan serve, or a separate start command. The Docker entrypoint prepares the application and starts Apache.

The first startup runs migrations, seeds 102 English quotes and 13 official health resources, and caches configuration, routes and Blade views. No test users are created. Register your own account through the website.

APP_KEY_SEED is generated once by Render. The entrypoint derives a stable 32-byte Laravel APP_KEY from it. Preserve the same seed when redeploying or moving the service; changing it invalidates encrypted cookies and sessions. Alternatively, an existing valid APP_KEY can be set explicitly and takes precedence.

## 4. Verify the public Beta

- Open /up, /, /resources, /signup and /login.
- Register, log out and log in again; verify that HTTPS forms and secure sessions work.
- Publish a quote, like/save it, add it to a private collection, select interests and submit a report.
- Redeploy the service and confirm that the same account and saved data remain in Neon.
- Verify that /.env, /composer.json and /database/database.sqlite return 403 or 404.
- Measure first visit after a period of inactivity separately from subsequent page loads.

GitHub Actions includes a Docker build, PostgreSQL relationship checks and Apache HTTP smoke checks. These run after you push the repository. They have not run merely because the package was created. Review the Actions result and Render's successful deployment before calling the Beta live.

## Updating the website

Commit and push changes to the connected branch. Render is configured to redeploy on commits. Startup migration and seeding are repeatable. Do not use migrate:fresh, db:wipe, change APP_KEY_SEED, or delete the Neon database when updating. Existing data in an older local SQLite database is not automatically imported; this deployment starts a separate cloud database from the seed library.

## Free-plan limits and the 3-second target

Render Free stops the web service after 15 minutes without incoming traffic. The next request may wait about one minute while the application starts. Docker and Apache do not remove this limit. A guaranteed first visit under 3 seconds is therefore not supported by this free Beta configuration. Warm-page performance must be measured on the public deployment. An always-on paid service removes the idle spin-down delay, but page speed still requires measurement.

Neon also scales idle compute down, so database wake-up can affect the first request. Keep both services within their free quotas. This setup does not run a background queue worker; QUEUE_CONNECTION=sync matches the application's current synchronous features. Existing local SQLite data is not embedded in the container.

## Troubleshooting

- Build fails: inspect the first Docker/Composer error in Render Logs and the GitHub Actions build result.
- SQLSTATE/connection error: check DB_URL, the Neon database status and sslmode=require. Do not put the URL in build commands or source files.
- A page works but /login or /resources returns 404: confirm Render uses the Dockerfile and that Apache serves /var/www/html/public.
- Login does not persist: use HTTPS, keep SESSION_SECURE_COOKIE=true, and preserve APP_KEY_SEED.
- Invalid encryption key: preserve the generated APP_KEY_SEED or supply a valid Laravel APP_KEY; do not generate a different key at each startup.
- New custom domain: add it in Render and set APP_URL to its full HTTPS URL, then redeploy.

## Official references

- https://render.com/docs/docker
- https://render.com/docs/blueprint-spec
- https://render.com/docs/free
- https://render.com/docs/environment-variables
- https://neon.com/pricing
- https://neon.com/docs/guides/laravel
