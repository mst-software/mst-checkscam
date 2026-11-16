---
name: mst-laravel-conventions
description: Mandatory Laravel coding conventions for the MST CheckScam app (Laravel 12, PHP 8.2+, Pest, Pint, Tailwind 4, Blade, Vietnamese UI). Use whenever creating or editing PHP, routes, controllers, models, migrations, helpers, Blade views, Tailwind CSS or tests, and whenever unsure how something should be written in this repo.
---

# MST CheckScam Laravel conventions

Follow the code that already exists in this repo. Read a sibling file before writing a new one. See `CLAUDE.md` for architecture (search normalization, masking, rate limiting, Newfeed).

## Language policy

- **Code is English**: identifiers, PHPDoc/comments, route names, migration and column names, test names, commit messages.
- **UI copy is Vietnamese**: Blade text, validation messages, flash messages and mail. Keep the tone of the surrounding pages.
- Docs in `docs/` are Vietnamese; keep that language when editing them.

## General rules

1. **Read a sibling file first** (controller next to controller, view next to view). Prefer the newer style when styles are mixed.
2. **Create files with `php artisan make:...`** and `--no-interaction`.
3. **Use Laravel Boost MCP when available**: `search-docs` before writing Laravel, Pest or Tailwind code; `database-schema` before writing migrations or models.
4. **No documentation files** unless the user asks. No throwaway verification scripts or tinker when a test proves the behaviour.
5. **Every behaviour change needs a Pest test** (`tests/Feature`, `tests/Unit`). Run the smallest relevant run: `php artisan test --compact --filter=...`. Never delete a test without approval.
6. **Run `vendor/bin/pint --dirty` before finishing** (`composer lint` only checks).
7. Do not add dependencies or new top-level folders without approval.

## PHP

- Curly braces on every control structure.
- Explicit parameter and return types; constructor property promotion.
- Enum cases TitleCase.
- Descriptive names (`isExpiringSoon`, not `check`).
- Pint rules (`pint.json`): `laravel` preset, alphabetical imports, no unused imports, trailing commas in multiline arrays/arguments/parameters.

## Laravel

- **Validation:** the app currently validates in controllers with `$request->validate(...)`. Keep that in existing controllers; for large or reused rule sets prefer a FormRequest (`php artisan make:request`).
- **Database:** Eloquent and `Model::query()`. `DB::` is reserved for `DB::transaction` and the `REGEXP_REPLACE` search expressions in `SearchController`. Eager load (`with`) to avoid N+1; reusable filters as `scopeXxx(Builder $query): Builder`.
- **Models:** new models use `casts()` (older ones use `$casts`; when editing an old model match it, do not modernize drive-by). Every relationship has a return type. See `laravel-eloquent-conventions`.
- **Media:** models with images implement `HasMedia` and register conversions in `registerMediaConversions()` (see `Report`).
- **Config:** never call `env()` outside `config/`. Admin-editable settings go through `Setting` + `ConfigHelper`.
- **Routes:** new URLs and route names are ALWAYS English (`/warnings`, `/watchlist`), never Vietnamese slugs. Existing Vietnamese URLs (`/to-cao-lua-dao`, `/bao-hiem-cs`, ...) stay for SEO; do not rename them without approval. Everything lives in `routes/web.php`; admin under `Route::prefix('admin')`. The catch-all `GET /{slug}` must stay last, so add new top-level routes above it. Always named routes and `route()`.
- **Search/PII:** use `StringHelper` (`detectQueryType`, `normalizeString`, `mask_*`) instead of writing new ad-hoc normalization or masking.
- **Slugs:** `Insurance` and `Post` share one slug namespace; use `StringHelper::generateGlobalUniqueSlug()`.
- **Authorization:** policies (e.g. `NewfeedPostPolicy`) and `Auth::user()->cannot(...)`, not manual ownership checks. Admin role checks use `isAdmin()` / `isModerator()`.
- **Rate limits** stay in controllers as documented in `CLAUDE.md`; do not move them to middleware without approval.
- Heavy or slow work goes to queued jobs (`QUEUE_CONNECTION=database`).

## Blade and styling

- Layouts in `resources/views/layouts`, reusable parts in `resources/views/components` and `partials`; reuse before creating new ones.
- Tailwind 4 via Vite (`resources/css/app.css`). Follow existing class patterns; use the `tailwindcss-development` skill. After CSS/JS changes run `npm run build` or `npm run dev`.
- Blade is formatted with Prettier (`prettier-plugin-blade`, `.bladeformatterrc.json`); run `composer format` if unsure. `.editorconfig`: UTF-8, LF, 4-space indent.
- Public pages must show masked data only (see `StringHelper` masks).

## Tests (Pest)

- `php artisan make:test --pest <Name>` (add `--unit` for unit tests).
- Use `RefreshDatabase` in a test file (it is not enabled globally in `tests/Pest.php`) and factories; check for existing factories and add missing ones.
- Use `testing-best-practices` for test design.

## Working with the user

- State assumptions; present alternatives when there are several readings.
- Keep it minimal and surgical: every changed line traces to the request. Mention unrelated dead code instead of deleting it.
- For multi-step work, give a short plan where each step can be verified.
