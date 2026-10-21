# AGENTS.md — EME VZW Helper

Single WordPress plugin (no monorepo): Belgian VZW/nonprofit compliance features layered on top of the
**Events Made Easy (EME)** plugin. Requires PHP 8.0+, WP 6.0+.

## Layout & wiring

- `eme-vzw-helper.php` — bootstrap only: plugin header, `EME_VZW_*` constants, autoloader, `plugins_loaded`
  init, activation/deactivation/uninstall hooks, plugin-row links, vendored updater.
  - The autoloader maps `EMEVzw\Foo\Bar` → `includes/Foo/Bar.php`. **One class per file, filename == class
    name.** A new `EMEVzw\Foo` class must live in `includes/Foo.php`.
- `includes/` — all logic: `Config` (settings option + board CRUD), `Checklist` (item definitions + scoring),
  `Publications` (CRUD + AJAX), `PdfGenerator` (dompdf), `Installer` (schema), `Notifications` (cron),
  `Assets` (enqueue), `Admin/Menu` (admin pages).
- `templates/` — plain PHP views included by `Admin\Menu::tpl()` (`templates/<name>.php`). They call
  `EMEVzw\Config::get()` etc. directly and echo HTML; there is no separate view layer.
  `templates/pdf/` holds PDF HTML fragments; `templates/publication-help.php` is rendered to JSON via AJAX.
- `assets/` — `css/admin.css`, `js/admin.js` (**vanilla JS, no jQuery, no build step**),
  `templates/*.png` (300-dpi scans of the official Belgian forms, used as PDF backgrounds).
- `includes/class-eme-updater.php` — vendored copy of EME's GitHub updater (namespace `EmeVzwHelper`, *not*
  `EMEVzw`). Don't refactor it.

## Hard dependency on Events Made Easy

EME is required, not optional: the bootstrap shows an admin notice and bails if `eme_get_event()` is missing.

- EME helpers used: `eme_sanitize_request()`, `eme_sanitize_email()`, `eme_ajax_chooseperson_snapselect`,
  and the constants `EME_PLUGIN_URL` / `EME_VERSION`.
- **dompdf is not vendored here.** `PdfGenerator::load_dompdf()` loads
  `WP_PLUGIN_DIR . '/events-made-easy/includes/dompdf/vendor/autoload.php'`. There is no `composer.json` in
  this repo; PDF output fails without EME.
- The `snapselect` JS/CSS and the EME person picker are also served from the EME plugin directory.
- **No composer, no npm, no CI, no test suite.** Verification = `php -l` + manual check in a WP install with
  EME active.

## Commands

```bash
# lint (exactly what the release script runs)
find . -name '*.php' -not -path './.git/*' -not -path './dist/*' -not -path './vendor/*' | sort | xargs -n1 php -l

# release: lint -> clean-tree check -> version bump -> zip -> commit/push -> GitHub release
bash script/eme_release.sh 1.0.8 1.0.9
```

`script/eme_release.sh` needs `php`, `zip`, `git` and an authenticated `gh`, and **refuses to run with a dirty
working tree**. It bumps the version with a single `sed -i "s/$old/$new/"` over the whole of
`eme-vzw-helper.php` — which is why the `Version:` header *and* the `EME_VZW_VERSION` constant both live in
that file and must stay in sync. Never bump only one by hand. The script also rewrites `Stable tag:` in
`readme.txt` but adds **no changelog entry** (readme.txt's changelog is stale at 1.0.2). Feature commits do not
bump the version; only the release script does.

The build artifact lands in `dist/` (gitignored) and the zip excludes `dist/`, `script/` and root `*.md`, so
`AGENTS.md` never ships to WordPress users.

## Database

Tables are `{$wpdb->prefix}emevzw_board`, `_checks`, `_publications`, `_pub_board`. Settings live in the single
option `emevzw_helper_settings` (array).

- **Schema setup runs from two places, because the activation hook does not fire on every update.**
  `register_activation_hook()` binds to `activate_{$plugin}`, which WordPress skips for *silent* activations.
  Core's own update path (wp.org / auto-update) silently deactivates before swapping files
  (`Plugin_Upgrader::deactivate_plugin_before_upgrade()`) and reactivates silently
  (`wp-admin/update.php?action=activate-plugin` → `activate_plugin( ..., $silent = true )`), so on that path
  `Installer::activate()` **and** `Installer::deactivate()` do not run at all. The bundled GitHub updater
  (`includes/class-eme-updater.php` → `post_install()`) reactivates *non-silently*, so there the activation hook
  does run. Don't depend on either channel:
  - `Installer::maybe_upgrade()` is hooked on `plugins_loaded` (priority 5, from `eme-vzw-helper.php`) and compares
    the `emevzw_db_version` option with `Installer::DB_VERSION`. If they differ it runs `install_site()`:
    `create_tables()` (idempotent `CREATE TABLE IF NOT EXISTS`) → `migrate( $current_version )` → store the new
    version. The check is a cheap `get_option()`; when versions match, nothing is queried at all.
  - `install_site()` takes a MySQL named lock `emevzw_db_upgrade_{$wpdb->prefix}` (`GET_LOCK(..., 30)` /
    `RELEASE_LOCK`), the same pattern EME uses (`events-made-easy/includes/eme-install.php`), so parallel requests
    (cron + a browser tab) can't upgrade concurrently. Lock names are per-site (they contain the table prefix).
  - `Installer::migrate( int $current_version )` receives the *stored* version; `0` means "not installed yet" and
    means "nothing to migrate" (`create_tables()` already made the current schema). `DB_VERSION` is `'6'`.
  - When you add a column/table: bump `DB_VERSION`, add a `if ( $current_version < N )` block in `migrate()`.
    The `plugins_loaded` check picks it up on existing installs — no activation needed.
- **Multisite:** network activation loops all sites (`activate( bool $network_wide )` → `switch_to_blog()` per
  site), `wp_initialize_site` (priority 900) installs the schema for newly created sites while the plugin is
  network-active, and `uninstall()` cleans every site because core's `uninstall_{$file}` action passes no
  arguments. Single-site installs keep working unchanged.
- Board members are soft-deleted (`is_active = 0`); a hard delete (`eme_vzw_purge_board_member`) is rejected for
  members who are still active.
- `emevzw_pub_board` links a publication to board members with `change_type` `B` = Benoeming, `E` =
  Einde/ontslag (`save()` also accepts `H`).
- Tables are dropped on uninstall only when the option `emevzw_helper_delete_on_uninstall` is `'yes'`.

## AJAX & security conventions

- All handlers are `wp_ajax_eme_vzw_*` (logged-in only, no `nopriv` variant) and must call both
  `check_ajax_referer( 'eme_vzw_nonce', 'nonce' )` and `current_user_can( 'manage_options' )`.
- The nonce action is `eme_vzw_nonce`; it is localized to JS as `emeVzw.nonce` in `includes/Assets.php`.
  `emeVzw.i18n.emeadminnonce` is a *separate* EME `eme_admin` nonce, used only by the person picker hooked as
  `wp_ajax_eme_vzw_snapselect_searchemeperson` → EME's `eme_ajax_chooseperson_snapselect`.
- `admin.js` posts `{action, nonce, ...}` to `admin-ajax.php`. PDF endpoints are GET:
  `?action=eme_vzw_download_pdf&nonce=...&pub_id=...` (add `&debug=1` for the calibration grid).
- Sanitize input with EME's `eme_sanitize_request()`; use prepared statements for anything user-supplied.

## PDF forms

`PdfGenerator` renders the official Formulier I/II plus the bestuursverbod declaration via dompdf. The
official form scans are rasterized backgrounds; all text is placed on top at fixed absolute **mm** coordinates,
and Luik B pagination is handled by `PdfGenerator` itself, not by dompdf.

- Regenerate backgrounds with `pdftoppm -png -r 300 <source>.pdf assets/templates/formulier1_bg` (see
  `howto.txt`).
- After a layout change, calibrate via `?action=eme_vzw_download_pdf&debug=1` (renders a 10 mm grid) and adjust
  `X_OFFSET_MM` / `Y_OFFSET_MM` in `includes/PdfGenerator.php`.

## Language, style & data

- All user-facing strings are **Dutch**, wrapped in `__()` / `esc_html__()` with text domain `eme-vzw-helper`.
  Keep new strings Dutch.
- Every PHP file starts with `defined( 'ABSPATH' ) || exit;`; classes are namespaced under `EMEVzw`.
- Checklist items are pure data in `Checklist::get_definitions()`: key, label, weight 0–3, group
  (`general` | `current_year`), `sub_questions[]` with type `yesno|date|number|text`. `general` rows are stored
  with `fiscal_year = NULL` so they apply to every year.
- A sub-question may carry `default_from_config` + `update_config_if_newer`, in which case answering it writes
  back into the settings option (e.g. `board_mandate_end`, `ubo_last_confirmed`, `rpb_last_confirmed`).
- After each saved checklist answer the action `eme_vzw_check_saved` fires.
- Currency is deliberately pinned to `EUR` in `Config::save()`; the intl-based `isValidCurrencyCode()` helper is
  parked (its only call site sits in a commented-out block) and is effectively dead code.
