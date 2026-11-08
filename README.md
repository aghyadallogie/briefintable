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
| `/briefings/create` | Livewire form (needs the `create briefings entries` permission) |
| `/my-drafts` | The signed-in user's own unpublished briefings (the navbar email links here; not cached) |
| `/my-drafts/{id}/edit` | Edit one of your own drafts in the same form |
| `/cp` | Statamic control panel (also the login page; a fresh login lands on `/briefings`, or on the page you were originally sent from) |

Configure the database in `.env` (`DB_CONNECTION=pgsql`, or `sqlite` for a throwaway local DB), then `php artisan migrate`.

## How it fits together

- **Reading** is Statamic + Antlers: [`BriefingController`](app/Http/Controllers/BriefingController.php) renders [`briefings/index.antlers.html`](resources/views/briefings/index.antlers.html); the single-briefing page is `briefings/show.antlers.html`.
- **Writing** is Livewire: [`BriefingForm`](app/Livewire/BriefingForm.php) creates an entry in the `briefings` collection. "Publish immediately" sets Statamic's native `published` flag (there is no separate status field).
- **Reserved slugs:** our own `/briefings/<word>` routes (today `create`) are matched before Statamic's `/briefings/{slug}`, so a briefing with that slug could never be opened. [`ReservedSlugs`](app/Support/ReservedSlugs.php) reads the word from the registered routes; the form skips it (a briefing titled "Create" gets `create-2`) and the blueprint's slug field rejects it in the CP.
- **Fields** are defined in the blueprint [`resources/blueprints/collections/briefings/briefing.yaml`](resources/blueprints/collections/briefings/briefing.yaml).

### Roles and publishing

Admins are Statamic super users (`super: true`). Outside writers get the `contributor` role from [`resources/users/roles.yaml`](resources/users/roles.yaml). It has only `create briefings entries`.

| | Can open `/briefings/create` | Can publish from the form | Can open `/cp` |
|---|---|---|---|
| Contributor | yes | no, always saved as a draft | no (Statamic's "Unauthorized" page) |
| Admin (super) | yes | yes | yes |
| Any other logged-in user | no (403) | no | only with `access cp` |

Admins publish contributors' drafts in the CP (Collections → Briefings → filter by Draft). The form checks the permission on the route and again in `save()`, and ignores the `published` flag unless the user has `publish briefings entries` ([`ContributorRoleTest`](tests/Feature/ContributorRoleTest.php)).

- **Editing and deleting drafts:** only the author can, and only while the briefing is unpublished. This is the `manage-own-draft` Gate in [`AppServiceProvider`](app/Providers/AppServiceProvider.php), not a Statamic permission, so admins can't change other people's drafts through the app (they use the CP) and nobody can edit a briefing once it is live. Anything else is a 404. Delete is permanent ([`EditDraftsTest`](tests/Feature/EditDraftsTest.php)).
- Roles need `STATAMIC_PRO_ENABLED=true`; without it the CP user form has no Roles field. Pro is free to try locally and needs a licence in production.
- To create a contributor: CP → Users → create (or `php please make:user`), then set the role to Contributor.
- Never make your only admin a contributor: contributors can't open the CP to undo it. Recover by editing the user file in `/users`: put `super: true` back and delete the `roles:` line.
- Saving roles from the CP rewrites `roles.yaml` and drops its comments.

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

### Output escaping

Titles and bodies are user input shown on public (and cached) pages. Plain-text fields go through `| sanitize` in the templates; markdown is rendered with `html_input: escape` and `allow_unsafe_links: false` in [`config/statamic/markdown.php`](config/statamic/markdown.php), so raw HTML and `javascript:` links come out as text. This applies to every markdown field, including in the CP. Any new template that prints user data needs the same treatment ([`BriefingsXssTest`](tests/Feature/BriefingsXssTest.php)).

## Testing and code style

```bash
composer test                  # PHPUnit; uses in-memory SQLite and the array cache
vendor/bin/pint                # format
```

`tests/TestCase.php` refuses to run on any database other than SQLite, so tests can't wipe your real DB.

## Known gaps

- No rate limit on the form, and contributors can't edit or delete a briefing once an admin has published it.
- Users are flat files, so they don't survive a host with an ephemeral disk.
