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
| `/` | Landing page: hero, how it works, the 3 newest published briefings and the published count (cached per list version, so publishing refreshes it) |
| `/briefings` | Public, paginated list of published briefings |
| `/briefings/{slug}` | A single briefing |
| `/briefings/create` | Livewire form (needs the `create briefings entries` permission) |
| `/my-drafts` | The signed-in user's own unpublished briefings (the navbar email links here; not cached) |
| `/my-drafts/{id}` | Read-only details page for one of your own drafts (the title on My Drafts links here), with a "Back to Drafts" link |
| `/my-drafts/{id}/edit` | Edit one of your own drafts in the same form |
| `/cp` | Statamic control panel (also the login page; a fresh login lands on `/briefings`, or on the page you were originally sent from) |

Configure the database in `.env` (`DB_CONNECTION=pgsql`, or `sqlite` for a throwaway local DB), then `php artisan migrate`.

## How it fits together

- **Reading** is Statamic + Antlers: [`BriefingController`](app/Http/Controllers/BriefingController.php) renders [`briefings/index.antlers.html`](resources/views/briefings/index.antlers.html); the single-briefing page is `briefings/show.antlers.html`.
- **Writing** is Livewire: [`BriefingForm`](app/Livewire/BriefingForm.php) creates an entry in the `briefings` collection. "Publish immediately" sets Statamic's native `published` flag (there is no separate status field).
- **Querying briefings:** [`App\Support\Briefings`](app/Support/Briefings.php) holds the rules that were repeated in several files: the collection handle, what "published" means, the display order (`published_at` desc, then `created_at` desc) and `uniqueSlug()`, which the write form uses to name a new briefing. The landing page, the list controller and My Drafts use `published()`, `newestPublished()` and `draftsOf()`, and the Antlers list receives its collection and sort from the controller instead of hardcoding them. To change the order, change `Briefings::ORDER` ([`BriefingsQueryTest`](tests/Feature/BriefingsQueryTest.php)), and the index on `(collection, published, published_at)` that serves it ([migration](database/migrations/2026_11_09_000002_add_published_index_to_entries_table.php)). The date printed on a card is `written_at` (when it was created), the same on the landing page, the list and the briefing page, never "last modified". Everything else is a plain Statamic call where it is needed.
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

- **Editing and deleting drafts:** only the author can, and only while the briefing is unpublished. The rule lives in [`OwnDrafts`](app/Support/OwnDrafts.php), not in a Statamic permission, so admins can't change other people's drafts through the app (they use the CP) and nobody can edit a briefing once it is live. Someone else's briefing is a 404. If an admin publishes your draft while you are editing or deleting it, nothing is changed: the form stays open with your text and says so, and a delete redirects to My Drafts with a message. The final check and the write run under a row lock (`SELECT ... FOR UPDATE` on Postgres), so a publish landing at the same moment is never overwritten. Delete is permanent ([`EditDraftsTest`](tests/Feature/EditDraftsTest.php)).
- Known limit: saving a draft overwrites its title and content even if an admin changed them in the CP without publishing. There is no conflict detection.
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

### Theming

The look (modelled on table.media: Roboto Slab, red accent, white bordered cards) is defined once, as design tokens in [`resources/css/site.css`](resources/css/site.css): fonts, colours (`primary`, `ink`, `muted`, `line`, `page`, `surface`, status colours), radii and shadows. Edit a value there and the whole app follows; they stay real CSS variables, so a page or theme can also override them (`:root { --color-primary: #1d4ed8; }`).

- Views use the token names (`bg-primary`, `text-ink`, `rounded-card`) and a few component classes (`btn`, `btn-primary`, `btn-secondary`, `card`, `field`, `badge-*`, `alert-*`, `link`), never raw palette colours like `indigo-600`. [`ThemeTokensTest`](tests/Feature/ThemeTokensTest.php) fails if one slips in. `home.antlers.html` is the unused Statamic starter page and is exempt.
- **Logo:** the navbar brand is a brand-coloured circle with a "B" plus the "Briefintable" wordmark (shown from the `sm` breakpoint up), one link home named "Briefintable, home" for screen readers. `.logo-mark`, `.brand` and `.brand-name` in `site.css` are themed through `--color-primary`, `--font-heading` and `--color-ink`.
- **Favicon and theme colour:** [`public/favicon.svg`](public/favicon.svg) and the `theme-color` meta tag in both layouts are images or plain attributes, so they cannot read CSS variables. If you change `--color-primary`, change those three places too; `ThemeTokensTest` fails until they match. (`favicon.ico` is still Laravel's default, kept as a fallback for old browsers.)
- **Navbar:** [`App\View\Components\Navbar`](app/View/Components/Navbar.php) decides what to show (links, the one primary button, the account menu) and [`navbar.blade.php`](resources/views/components/navbar.blade.php) draws it for desktop and phone. The brand is on the left; the links, the primary button and the menus form one group on the right. The account menu opens from a user profile icon (outline head and shoulders, as on table.briefings), with the signed-in email shown inside the dropdown. The same links appear on every page and the current one is marked (`aria-current`). On the write form the button is hidden with `invisible` rather than removed, so it keeps its space and the links stay in the same place. The menus are Alpine components (one `menu` state on the header, so only one is open; outside click and Escape close it) that fade and scale in and out with Tailwind `x-transition` classes, the same pattern table.media uses. Livewire pages already ship their own Alpine, so [`site.js`](resources/js/site.js) only loads and starts Alpine (from npm, as a separate chunk) when `window.Alpine` is missing; never start a second copy. Without JavaScript the menus stay closed. To add a link, add it to `Navbar::__construct()`.
- **Motion:** briefing cards (list, My Drafts, landing) fade and rise into place with a stagger (`rise-in` plus `style="--i: <index>"`) and lift slightly on hover (`card-lift`). It is pure CSS, defined once in `site.css` (`--animate-rise`, `--rise-stagger`), capped at 8 steps and switched off for `prefers-reduced-motion`. Give any new list of cards the same two classes.
- **No flicker between pages:** the layout is made stable on purpose. `html` always reserves its scrollbar gutter (otherwise short pages are ~15px wider than long ones and the centred navbar slides sideways), Roboto Slab's Latin face is declared in `site.css` with `font-display: block` and preloaded from [`head-assets`](resources/views/components/head-assets.blade.php) (the package's default `swap` painted a fallback serif first and then jumped), and both layouts emit identical head tags and the identical navbar. [`PageStabilityTest`](tests/Feature/PageStabilityTest.php) pins this. Don't re-import `@fontsource-variable/roboto-slab` as a stylesheet.
- Roboto Slab is self-hosted through `@fontsource-variable/roboto-slab`, so no request goes to Google. Swap `--font-sans` / `--font-heading` to change it.
- After changing CSS or views run `npm run build` (or keep `npm run dev` running).

## Testing and code style

```bash
composer test                  # PHPUnit; uses in-memory SQLite and the array cache
vendor/bin/pint                # format
```

`tests/TestCase.php` refuses to run on any database other than SQLite, so tests can't wipe your real DB.

## Known gaps

- No rate limit on the form, and contributors can't edit or delete a briefing once an admin has published it.
- Users are flat files, so they don't survive a host with an ephemeral disk.
