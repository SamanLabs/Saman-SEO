# Saman SEO — Backend Audit (2026-07-12)

Four-track parallel audit of `includes/**`: security, data growth & logging, sibling-consistency, performance & robustness. `src-v2/` JS was out of scope.

**Headline:** No critical unauthenticated security holes — REST auth, `$wpdb->prepare` discipline, and output escaping are solid across the board. The real problems are two shipping bugs (now fixed, see P0), unbounded log growth by default, a link-health scanner that cannot survive a real site, and a handful of "fixed in one file, missed in siblings" drifts.

Legend: `[x]` = fixed in this pass, `[ ]` = open.

---

## P0 — Actual bugs (FIXED in this pass)

- [x] **Schema Validator endpoint was dead + latent SSRF.**
  `includes/Api/class-schema-validator-controller.php:40` registered `permission_callback => check_permission`, a method that exists nowhere (the base `REST_Controller` defines `permission_check`). Every request to `/saman-seo/v1/schema-validator/validate` failed closed. Behind it, `validate_url()` fetched a user-supplied URL with **no `wp_http_validate_url()` guard** and **`sslverify => false`** — a full-read SSRF (cloud metadata `169.254.169.254`, internal services) that would go live the moment the callback was corrected. Its siblings (mobile-test `:77`, tools `:400`) both validate.
  **Fix applied:** callback → `permission_check`; added `wp_http_validate_url()` rejection in `validate_url()`; removed `sslverify => false`.

- [x] **Custom assistants table was never created — invalid SQL.**
  `includes/Api/class-assistants-controller.php:690` had a literal `// phpcs:ignore ...` line *inside* the `CREATE TABLE` string, between the `model_id` and `is_active` column definitions. `//` is not a MySQL/dbDelta comment, so the column list was mangled and the custom-assistants feature silently never persisted. Also fixed four mojibake emoji (triple-encoded UTF-8) in icon defaults.
  **Fix applied:** removed the comment line from the SQL; set the DDL `icon` default to `''` (avoids emoji-in-DDL trouble on non-`utf8mb4` DBs) with PHP-side fallbacks — 💬 (SEO Assistant, :137), 📊 (SEO Reporter, :153), 🤖 (custom default, :170/:365). Both files pass `php -l`.

---

## P1 — Log growth & retention (the "big log" problem)

There is exactly **one** pruning cron in the entire plugin (`SAMAN_SEO_404_cleanup`) and it ships **disabled**. Everything else grows monotonically.

| Store | Growth | Limit today |
|---|---|---|
| `{prefix}SAMAN_SEO_404_log` | 1 row per unique 404 URI (dedupe by exact URI, `hits+1` on repeat). Bots logged by default (`ignore_bots` default `'0'`, request-monitor.php:438). A bot spraying random URLs = 1 row per URL, forever. | Daily cleanup cron exists (request-monitor.php:67-105, prunes rows older than `cleanup_404_days`, default 30d) but `enable_404_cleanup` defaults **false** (:68). No row cap ever. |
| `{prefix}SAMAN_SEO_indexnow_log` | 1 fat row (VARCHAR 2048 url) per URL per publish/update (indexnow.php:397-417, hooks :84-89). | `clear_logs($days)` exists (:526-539) but is manual-only — **no cron calls it**. |
| `{prefix}SAMAN_SEO_assistant_usage` | 1 row per assistant message (assistants-controller.php:541-553). | **Nothing.** No prune, no cap, no clear endpoint. |
| `{prefix}SAMAN_SEO_link_scans` | 1 row per scan (weekly cron + every manual scan), never deleted. | None. |
| `SAMAN_SEO_monitor_slugs` option | 1 entry per slug change / trashed monitored post (redirect-manager.php:167, 216). **Autoloaded** on every request. | Only removed when admin acts (:341-347, :750-755). |

### Shared `saman_seo_daily_maintenance` cron (BUILT in this pass)

New service `includes/class-saman-seo-service-maintenance.php` (`Saman\SEO\Service\Maintenance`). Registered in `Plugin::boot()`, scheduled on activation, self-heals every boot (re-registers if WP-Cron dropped the event). All prunes are idempotent and filterable; verified with a 12-assertion stub harness (scheduling idempotency + `monitor_slugs` date-drop / cap / autoload=false).

- [x] 404 log: unconditional hard row cap (`saman_seo_404_log_max_rows`, default 10000), deletes oldest by `last_seen`. Cap is unconditional so a bot flood can't blow the table up even when a user intentionally keeps logs; the existing time-based `enable_404_cleanup`/`cleanup_404_days` cron is left untouched.
- [x] IndexNow log: daily `clear_logs()` via `saman_seo_indexnow_log_retention_days` (default 90).
- [x] Assistant usage: prune older than `saman_seo_assistant_usage_retention_days` (default 365).
- [x] Link scans: keep last `saman_seo_link_scans_max_rows` (default 50) **and** reap `running` scans older than `saman_seo_link_scan_stale_minutes` (default 60) → marked `failed` (this is the stale-lock reaper for P2 #1; the scan rewrite itself is still open).
- [x] `SAMAN_SEO_monitor_slugs`: cap to `saman_seo_monitor_slugs_max` (100) + drop older than `saman_seo_monitor_slugs_retention_days` (90); rewritten with `autoload => false`.
- [x] `ignore_bots` default flipped → on (request-monitor.php:438); `last_seen` index added to the 404 table + `device_label` self-heal folded into the version gate (SCHEMA_VERSION → 7), so no per-request `SHOW COLUMNS`.

### Cleanup gaps (deactivation / uninstall)

- [x] `Plugin::deactivate()` now clears all four scheduled hooks (`saman_seo_daily_maintenance`, `SAMAN_SEO_404_cleanup`, `SAMAN_SEO_link_health_scan`, `SAMAN_SEO_sitemap_cron`) instead of only flushing rewrite rules.
- [x] `uninstall.php` now also clears the maintenance + link-health + sitemap crons and sweeps `_transient_%SAMAN_SEO%` / `_transient_%saman_seo%` rows (and their `_timeout_` partners) that the option `LIKE 'SAMAN_SEO_%'` delete missed.
- [x] Verified: `Internal_Linking::activate()` only adds the `SAMAN_SEO_link_rules` **option** (no table) — covered by the uninstall `LIKE 'SAMAN_SEO_%'` option delete. No forgotten table.
- Positive: uninstall.php drops all 8 known custom tables (:37-51). Good coverage otherwise.

---

## P2 — Will fall over at scale

- [x] **1. Link Health full scan is unbounded AND synchronous. (REWRITTEN)** Was `posts_per_page => -1` + synchronous `wp_remote_head` per link in one request. Now cursor-based chunked background processing: `start_scan()` snapshots the published-post count, inserts the scan row, and schedules a `SAMAN_SEO_link_health_process` single event (nudged with `spawn_cron()`); `process_chunk()` scans `saman_seo_link_health_chunk_size` (25) posts at a time via `WHERE ID > last_post_id ORDER BY ID ASC LIMIT n`, advances the cursor, and reschedules itself until the list is exhausted — memory bounded to one chunk regardless of site size. A transient lock prevents overlapping chunk runs. `start_scan()` now reaps scans stuck in `running` past `saman_seo_link_scan_stale_minutes` (60) so a wedged scan can't block future ones. The `save_post` hook is deferred to a `SAMAN_SEO_link_health_single` cron event (editor save no longer blocks on HTTP) and gated behind `enable_link_health_scan`. Per-link HTTP timeout cut 10s → 5s (`saman_seo_link_health_timeout`) and now guarded by `wp_http_validate_url()` (blocks SSRF to internal hosts; unvalidated URLs marked `unknown`, not `broken`). Scans table gained `last_post_id` (SCHEMA_VERSION → 2). Verified with a 19-assertion stub harness (cursor advance, chunk-boundary completion incl. exact-multiple, single inline, reaper, lock). Single scans still run inline (one explicit post).
- [x] **2. Sitemap index hit loads every post on the site.** `get_sitemap_lastmod()` now uses a single memoized `MAX(post_modified_gmt)` query per post-type group instead of hydrating every URL (`get_post_type_lastmod()`), and the whole computed index is cached in the `SAMAN_SEO_sitemap_index_cache` transient (DAY), flushed on `save_post`/`deleted_post`/`trashed_post`.
- [x] **3. `SHOW COLUMNS` on every request.** The per-request `has_column('device_label')` fallback was removed; the check now lives in `migrate_to_version_7()` behind the version gate (SCHEMA_VERSION → 7). `Request_Monitor::boot()` is now just a version-int compare on the common path.
- [x] **4. IndexNow blocks publishing.** The submission is deferred to a `SAMAN_SEO_indexnow_submit` single cron event (`maybe_submit_post` schedules; `cron_submit` runs `submit_url`). Publishing/updating no longer blocks on the 15s POST, and logging is preserved (runs in cron).
- [x] **5. Social card generator uncached public GD endpoint.** Generated PNGs are now written to `uploads/saman-seo-cards/card-{md5(title|design|dims)}.png`, cache hits are streamed straight from disk, and responses carry `Cache-Control: public, max-age` (default WEEK, filterable) instead of `nocache_headers()`.
- [x] **6. Video schema never caches oEmbed failures.** Both `get_youtube_oembed`/`get_vimeo_oembed` now cache a `'none'` sentinel (HOUR) on failure and treat it as null, so a dead video is not re-fetched on every view.
- [x] **7. Redirect object cache default.** `SAMAN_SEO_redirect_object_cache` now defaults **on** when `wp_using_ext_object_cache()` (a persistent cache genuinely helps), off otherwise (redirect-manager.php).
- [x] **8. Internal-linking cache check moved before rule prep.** `filter()` now checks the rendered-content transient before calling `get_prepared_rules()`, so cache hits no longer pay per-rule `get_post()`/`get_permalink()` resolution.
- [x] **9. Settings export.** `export_meta()` replaced with a single indexed `postmeta` query + `maybe_unserialize`, instead of `posts_per_page => -1` + per-ID `get_post_meta`.
- [x] **10. `SAMAN_SEO_sitemap_cron`.** The scheduled hook now points at `run_scheduled_regeneration()`, which refreshes the sitemap index cache (#2) instead of calling `flush_rewrite_rules()` every interval. The manual "regenerate" button keeps the flush.

---

## P3 — Sibling drift & hardening (the class you specifically asked about)

- [x] **11. The `eeeb412` "merged words" fix propagated.** Extracted a shared `Helpers\strip_content_to_text()` (the spacing-aware strip core) and refactored `generate_content_snippet()` to use it. All ~13 sites now route through one of the two: snippet sites → `generate_content_snippet()` (audit-controller.php:310/688/752, audit.php:187, admin-ui.php:659, video-schema.php:152, sitemap-enhancer news description); full-text/word-count sites → `strip_content_to_text()` (audit-controller.php:543/648, tools-controller.php:534/824/887). Verified with a 6-assertion stub harness (`<p>a</p><p>b</p>` → `a b`, script/style/comment stripping).
- [x] **12. `video-schema.php` aligned to its siblings.** Hook changed to `SAMAN_SEO_jsonld_graph` priority 20 / 2 args, gates on the passed `$post` instead of `is_singular()` + global, and all external oEmbed `title`/`description`/`author`/`thumbnail_url`/`duration` are now `sanitize_text_field`/`esc_url_raw`/`(int)`-cast before entering the graph.
- [x] **13. IndexNow allowlist + key leak.** New `sanitize_search_engine()` constrains the host to the known `$search_engines` allowlist both on save and defensively at POST time. The controller's `get_settings()` no longer returns the raw `api_key` — only the masked `api_key_display` and a `has_api_key` boolean (the JS never used the raw key).
- [x] **14. Raw `apply_filters`** at the news-sitemap `posts_per_page` now routes through `saman_seo_apply_filters()`.
- [x] **15. Assorted hardening:**
  - CSV exports now neutralize leading `= + - @` (tab/CR too) via a shared `csv_cell()` helper across both export blocks (verified in the stub harness).
  - JSON-LD `wp_json_encode` now passes `JSON_HEX_TAG | JSON_HEX_AMP` (frontend.php).
  - Schema-migration consistency: Redirect_Manager and IndexNow now self-heal via a version-gated `maybe_upgrade_schema()` on boot (cheap option-int compare), matching Link_Health/Request_Monitor. (Assistants still uses lazy `SHOW TABLES`; its table is now created correctly after the P0 fix.)
  - `Updater/class-plugin-installer.php` now rejects any `$download_url` whose host isn't `github.com`/`githubusercontent.com` (filterable) before installing. Verified with a 7-assertion stub harness incl. suffix-spoof rejection.

---

## Already solid — do not re-recommend

- Every REST route (all 20 controllers) has a real `permission_callback`; **zero `__return_true`**. Base `REST_Controller::permission_check()` enforces `manage_options`; per-object endpoints use `edit_post` correctly.
- `$wpdb->prepare` for all value params; `esc_like()` on every LIKE; `ORDER BY` always mapped from an enum allowlist; `IN()` lists built from generated placeholders; table names only from `$wpdb->prefix`. No injectable query found.
- `.htaccess` controller validates dangerous directives, checks balanced tags, uses `WP_Filesystem`, `realpath()` containment on restore, rotates backups.
- All `admin_post_*` / `wp_ajax_*` handlers pair nonce (`check_admin_referer`/`check_ajax_referer`) with a capability check.
- Frontend meta/OG/Twitter/canonical/breadcrumb output escaped at the sink line-by-line; AI output through `wp_kses_post` + event-handler stripping; 404 data `esc_html`'d in the dashboard widget.
- Version-gated `dbDelta` (not on every load); internal-linking versioned-transient cache with self-expiry is a genuinely good design.
- Bounded correctly: llm.txt 1–500/type, audit 100 posts, news sitemap 1000 + 2-day window, IndexNow 10k URL slice, preview body 200KB + same-host restriction.

---

## Status

**All audit items are resolved.** P0 (2 bugs), P1 (maintenance cron + retention + cron/uninstall cleanup + defaults), P2 #1–#10 (scale/hot-path), and P3 #11–#15 (sibling drift + hardening) are all `[x]`. Every touched file passes `php -l`; the non-trivial new logic (maintenance prunes, link-health chunking, `strip_content_to_text`, `is_trusted_source`, `csv_cell`) is covered by stub harnesses (12 + 19 + 17 assertions across three runs). Full end-to-end verification against the live DB still wants a manual pass in Local (system PHP here lacks mysqli): run a link-health scan, hit `/sitemap_index.xml`, publish a post (IndexNow + sitemap-cache flush), and load a post with a social card.

Schema bumps that apply on next admin load: Request_Monitor → 7 (last_seen index, device_label), Link_Health → 2 (last_post_id).
