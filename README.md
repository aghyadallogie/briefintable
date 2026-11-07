# Briefintable

A small briefings app: anyone can read published briefings, and logged-in users can write new ones.

**Stack:** Laravel 12 · Statamic 6 (+ Eloquent driver) · Livewire 4 · Tailwind 4 / Vite 7 · PHP ^8.2

## Quick start

```bash
composer setup                 # install, .env, key, migrate, npm build
php please make:user --super   # create a Statamic user (also your login for /cp)
composer dev                   # app + queue + logs + Vite
```

Then open the URL that `php artisan serve` prints (usually `http://127.0.0.1:8000`).

| URL | What |
|---|---|
| `/briefings` | Public, paginated list of published briefings |
| `/briefings/{slug}` | A single briefing |
| `/briefings/create` | Livewire form (login required) |
| `/cp` | Statamic control panel (also the login page) |

Configure the database in `.env` (`DB_CONNECTION=pgsql`, or `sqlite` for a throwaway local DB), then `php artisan migrate`.

## How it fits together

- **Reading** is Statamic + Antlers: [`BriefingController`](app/Http/Controllers/BriefingController.php) renders [`briefings/index.antlers.html`](resources/views/briefings/index.antlers.html); the single-briefing page is `briefings/show.antlers.html`.
- **Writing** is Livewire: [`BriefingForm`](app/Livewire/BriefingForm.php) creates an entry in the `briefings` collection. "Publish immediately" sets Statamic's native `published` flag (there is no separate status field).
- **Fields** are defined in the blueprint [`resources/blueprints/collections/briefings/briefing.yaml`](resources/blueprints/collections/briefings/briefing.yaml).

### Where data lives

Set in [`config/statamic/eloquent-driver.php`](config/statamic/eloquent-driver.php):

| Data | Stored in |
|---|---|
| Entries, collections | Database |
| Blueprints, fieldsets, forms, assets, **users** | Files (users in `/users`, which is gitignored) |

A collection that exists only in the database isn't in git, so a fresh environment needs it created (CP, or `php please eloquent:import-collections` from a YAML).

### The list cache

The rendered list is wrapped in Statamic's `{{ cache }}` tag. The key is `briefings.list.v{version}.n{perPage}.p{page}` ([`BriefingsCache`](app/Support/BriefingsCache.php)).

- The published count (used for the last-page / 404 check) is cached under the same version, so a cache hit makes no database queries.
- Saving or deleting a briefing that is, or just was, published bumps the version through an event listener in [`AppServiceProvider`](app/Providers/AppServiceProvider.php), so new content shows up immediately. Draft-to-draft changes don't bump it.
- Entries written straight to the database fire no event. Run `php artisan cache:clear` after that, after editing the list template, and on every deploy.
- Pager links are relative (`?page=N`) on purpose, because the cached HTML must not contain the host that rendered it.
- Change the page size with `BriefingController::PER_PAGE`.

## Testing and code style

```bash
composer test                  # PHPUnit; uses in-memory SQLite and the array cache
vendor/bin/pint                # format
```

`tests/TestCase.php` refuses to run on any database other than SQLite, so tests can't wipe your real DB.

## Known gaps

- No authorization beyond "logged in": any user can create and publish a briefing.
- `content` is markdown rendered as HTML without sanitising.
- Users are flat files, so they don't survive a host with an ephemeral disk.
