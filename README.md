# Aura Quotes: Render Beta source

Start with [DEPLOY-RENDER.md](DEPLOY-RENDER.md) for GitHub, Docker, Apache and Render deployment. This source package omits vendor and the local SQLite database; Render installs production dependencies from composer.lock and uses a private PostgreSQL DB_URL.

English dark-themed Laravel quote and health-awareness platform.

## Start on Windows

1. Extract the ZIP completely into a new folder.
2. Open the AuraQuotes folder and double-click START-AURA.cmd.
3. Open http://127.0.0.1:8000 and create your account.

The launcher detects PHP from PATH or Laravel Herd, generates a local .env and application key, applies migrations, seeds the library, compiles Blade views and starts the server. Re-running setup preserves your data. PHP 8.4.1 or newer and the extensions checked by scripts/check-requirements.php are required. For a local source checkout, run composer install first; no npm build or MySQL configuration is needed for this local release.

For another port: powershell -NoProfile -ExecutionPolicy Bypass -File .\start.ps1 -Port 8001

## Start manually

    php scripts/setup.php
    php artisan serve

If vendor is missing, restore it using composer install.

## Included features

- Account registration, login, remember me and logout.
- Public quote library, search, category filtering and pagination.
- Publish, edit and delete your own quotes with server-side ownership checks.
- Likes and saved quotes stored per account.
- Public official health-resource directory, with search and category filtering.
- Related resource links on quote cards.
- Personal interests and For you / Newest feeds.
- Private named collections for saved quotes, including rename, delete, add and remove.
- Private content reports with reason, optional details and a personal report history.
- English dark interface, local assets and responsive layouts.

The seeders create 102 English seed messages and 13 resource entries. This source distribution contains no SQLite database, user accounts or passwords. Some external official resources are in Arabic, as indicated on their cards. Reports remain open records; an administrative moderation screen is not part of this release.

## Checks

    php tests/verify.php

This runs 38 integration checks on an isolated in-memory database without modifying the application database. The release also passed 84 HTTP acceptance checks and browser checks for collections, reporting, responsive layout and local page loading. Local measured homepage load times were below 0.5 seconds; the performance requirement remains 3 seconds in the intended deployment environment.

## Documentation

docs/AuraQuotes_SRS.docx and docs/AuraQuotes_SRS.pdf follow the nine-section QuoteIt reference and describe this release. docs/AuraQuotes_ERD.pdf and docs/AuraQuotes_ERD.mmd describe all 17 tables. Vector diagrams and a schema reference are included. Install or upgrade the database using Laravel migrations, not the schema-reference SQL file.
