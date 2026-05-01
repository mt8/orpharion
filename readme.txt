=== Orpharion ===
Contributors: mt8biz
Tags: options, database, cleanup, performance, autoload
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track which plugin or theme accesses each wp_options row, then quarantine or clean orphans.

== Description ==

The `wp_options` table accumulates leftovers from plugins and themes that have been deactivated or deleted but never cleaned up. Those rows bloat the autoload payload on every page load and there is no built-in way to decide which ones are safe to remove.

Orpharion observes which options are actually read at runtime, attributes each read to the plugin or theme that caused it, and surfaces the raw signals (accessor, autoload flag, size, last-read timestamp) so administrators can decide what to remove:

1. **Observe** — the tracker records when and by whom each option is read, using the live PHP backtrace to identify the real caller.
2. **Quarantine** — rename the option temporarily so WordPress and the accessing plugin can no longer see it; confirm nothing breaks.
3. **Delete** — the row is removed from both `wp_options` and the tracking table. Use the **Export selected** bulk action first if you want a restore copy; Orpharion never writes option_value content to the server filesystem on your behalf (option_value can contain API keys, SMTP credentials, and other secrets that should not leak into backups of `wp-content/`).

Core WordPress options are locked out of destructive operations, on both the deletion and the import side.

== Features ==

* Per-option read tracking via dynamically registered `option_{$name}` filters, so every `get_option()` call is attributed to the real plugin or theme on the call stack.
* Accessor inference (live tracker data → slug prefix → curated core list) with an active / inactive flag so you can filter down to options whose owner is no longer present.
* Sortable options table with individual columns for accessor, autoload, size, and last-read timestamp — no opaque composite score.
* Quarantine mode with automatic expiry (restore / delete / keep) and a manifest table that flags options that are still being accessed.
* **No server-side backup**: JSON exports are browser downloads (or explicit CLI `--output`), never written to disk on the server — so option_value content does not leak into `wp-content/` snapshots.
* REST API under `/wp-json/orpharion/v1/*` (requires `manage_options`).
* WP-CLI commands for scripted pipelines, including `--accessor-type` / `--inactive-only` filters on `list`, `export`, and `clean` (`clean` requires an explicit `--i-have-a-backup` acknowledgment).

== Installation ==

1. Upload the plugin directory or install the zip via **Plugins → Add New → Upload**.
2. Activate the plugin.
3. Open the **Orpharion** menu in the WordPress admin sidebar and let the tracker run for a few days before acting on the results.

== Frequently Asked Questions ==

= Is tracking safe to leave on in production? =

Yes. Tracking is buffered in memory and flushed once per request at `shutdown`. A sampling-rate option and a 10-minute activation window triggered by admin traffic keep the overhead predictable.

= Will deleting an option break my site? =

Use Quarantine first. A quarantined option is renamed, not deleted; if anything breaks you can restore it with one click. Only permanently delete after you have confirmed nothing is broken during the quarantine window.

= What happens to my data if I uninstall the plugin? =

Uninstalling restores any active quarantines to their original names, drops the custom tracking and quarantine tables, removes plugin-owned options, and clears the scheduled cron job. Your regular `wp_options` data is untouched.

= Where does the name "Orpharion" come from? =

The orpharion is a Renaissance plucked-string instrument invented in England in 1581 by John Rose. The name is a 16th-century coinage from Orpheus and Arion, two legendary musicians of Greek mythology. Music by John Dowland, William Byrd, and others was published for it. The plugin borrows the name as a nod to the idea of carefully tuning what sits in your `wp_options` table.

== Source code ==

The published plugin ships with the compiled admin bundle in `build/` (`build/index.js`, `build/index.css`, `build/index.asset.php`). The non-compiled source for that bundle lives in `src/` in the public GitHub repository:

* Repository: https://github.com/mt8/orpharion
* Build tool: [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts) (uses webpack + Babel under the hood).
* Reproduce the bundle:

  1. Clone the repository.
  2. Install dependencies: `npm install` (Node.js version compatible with `@wordpress/scripts` is required).
  3. Build: `npm run build` — produces the same `build/` files that are shipped with the plugin.
  4. Watch mode for development: `npm run start`.

PHP code is shipped uncompiled and is the same in the published ZIP and in the repository.

== Changelog ==

= 1.1.2 =
* Admin menu icon: the opacity override is now attached to a registered style handle via `wp_add_inline_style()` on `admin_enqueue_scripts` instead of being printed as an inline `<style>` tag from `admin_head`. Visual behavior is unchanged.
* Read tracker: the `WP_PLUGIN_DIR` and `WPMU_PLUGIN_DIR` references used to classify backtrace frames are now routed through `wp_normalize_path()`, with a comment recording why those constants are referenced (identifying *other* plugins' install roots — Orpharion's own location is resolved from `__FILE__`).
* Documentation: `readme.txt` now includes a "Source code" section pointing to the public GitHub repository (`https://github.com/mt8/orpharion`) and the `npm run build` flow used to regenerate the bundled admin assets.

= 1.1.1 =
* WP-CLI: `wp orpharion export --output=<file>` now requires a bare `*.json` filename and always writes into `wp-content/uploads/orpharion/`. Absolute or relative paths and non-`.json` extensions are rejected so option_value content (which can include API keys, SMTP credentials, and other secrets) cannot be written to a web-accessible location. The export directory is created on demand with `index.html` and `.htaccess` so it is not browseable.

= 1.1.0 =
* Renamed the plugin from "Optrion" to "Orpharion" to remove an external trademark conflict before the WordPress.org review. The slug, text domain, REST namespace, WP-CLI command, internal option keys, hooks, and the quarantine rename prefix all move from `optrion`/`optrion_` to `orpharion`/`orpharion_`. There is no in-place migration: this is a one-shot rename done before any wp.org release.

= 1.0.3 =
* Consolidated the protected-option rules (WordPress core options, Orpharion's own `orpharion_*` namespace, and the quarantine rename namespace) into a single `ProtectedOptions` helper. The cleaner, importer, quarantine, and options-list filter now all derive their behavior from that one source of truth, with consistent name normalization across all of them.

= 1.0.2 =
* Options list: pick rows per page (25 / 50 / 100 / 200) and jump directly to a page number.
* Options list: the quarantine / export / delete action bar is now also rendered below the table, so you can act on a selection without scrolling back up.
* Hardened the protected-option check to match the storage layer's collation semantics (case-insensitive, trailing whitespace tolerant), so a non-canonical spelling cannot slip past Orpharion's safeguards.

= 1.0.1 =
* Hardened the importer to mirror the cleaner's protected-name set: WordPress core options, Orpharion's own internal options (`orpharion_*`), and the quarantine rename namespace are skipped instead of being written to `wp_options`. Skipped entries are reported in the import summary and the WP-CLI output.

= 1.0.0 =
* Initial public release.
* Per-option read tracking: every `get_option()` call is attributed to the real plugin or theme on the PHP backtrace.
* Options list with individual signal columns (accessor, autoload, size, last accessed) and inactive-only / autoload-only / accessor-type filters.
* Quarantine workflow with manifest table, automatic expiry (restore / delete / keep), and a still-accessed guard that blocks deletion of options that are still being read.
* No server-side persistence of option_value content: JSON exports are browser downloads only.
* REST API under `/wp-json/orpharion/v1/*` (requires `manage_options`).
* WP-CLI commands for scripted pipelines, including `--accessor-type`, `--inactive-only`, and `--autoload-only` filters on `list`, `export`, and `clean`.
* Full Japanese localization.

== License ==

This plugin is licensed under the GPL, version 2 or later.
