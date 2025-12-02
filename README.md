LaravelPHP_CommServe

Community services platform combining legacy PHP pages and a Laravel app.

Deploy Options (Free)

- Legacy PHP (mysqli): 000webhost, InfinityFree, AwardSpace
   - Upload `*.php` to `public_html`
   - Create MySQL DB and set credentials in `userAccounts/config.php`
   - Ensure `uploads/` is writable

- Laravel (temp-laravel): Railway, Fly.io, Koyeb (Docker)
   - Use provided `Dockerfile` in `temp-laravel/`
   - Pair with MySQL (Railway plugin or PlanetScale)
   - Set `.env` (see variables below) and run migrations

Quick Start (Docker Compose)

Requirements: Docker Desktop

Commands:

```
docker compose up -d
docker compose exec laravel php artisan migrate --force
```

Then visit `http://localhost:8080`.

Railway Deploy (Laravel)

- Connect GitHub repo
- Set environment variables:
   - `APP_ENV=production`
   - `APP_KEY` (generate locally via `php artisan key:generate --show`)
   - `APP_URL` (Railway URL)
   - `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Deploy using the Dockerfile; Railway builds Nginx + PHP-FPM
- Run `php artisan migrate --force` via Railway shell

Legacy PHP Deploy (000webhost)

- Upload legacy PHP files (root-level PHP, `dashboards/`, `pages/`, `includes/`)
- Configure `userAccounts/config.php` with host/user/pass/db
- Create/import tables if needed; the app auto-creates columns when missing

Environment Variables

- Laravel `.env` sample in `.env.example` and `railway.json`
- Key vars:
   - `APP_ENV`, `APP_KEY`, `APP_URL`
   - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Repo Notes

- `temp-laravel/` is an embedded repo. Consider converting to a submodule or vendor its contents:
   - Submodule: `git rm --cached temp-laravel` then `git submodule add <repo> temp-laravel`
   - Vendor: remove `temp-laravel/.git` and commit contents
