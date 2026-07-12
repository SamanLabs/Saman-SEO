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
- [ ] Still open: flip `ignore_bots` default → on (logging-behavior change, left to you); add a `last_seen` index on the 404 table (the cap DELETE orders by it — fine at 10k rows, but an index is cheap).

### Cleanup gaps (deactivation / uninstall)

- [x] `Plugin::deactivate()` now clears all four scheduled hooks (`saman_seo_daily_maintenance`, `SAMAN_SEO_404_cleanup`, `SAMAN_SEO_link_health_scan`, `SAMAN_SEO_sitemap_cron`) instead of only flushing rewrite rules.
- [x] `uninstall.php` now also clears the maintenance + link-health + sitemap crons and sweeps `_transient_%SAMAN_SEO%` / `_transient_%saman_seo%` rows (and their `_timeout_` partners) that the option `LIKE 'SAMAN_SEO_%'` delete missed.
- [ ] Still open: verify the `SAMAN_SEO_internal_link_rules` table (if `Internal_Linking::activate()` creates one) is in the uninstall drop list.
- [ ] Verify `SAMAN_SEO_internal_link_rules` table (if created by `Internal_Linking::activate()`) is in the uninstall drop list — the audit could not confirm it is.
- Positive: uninstall.php drops all 8 known custom tables (:37-51). Good coverage otherwise.

---

## P2 — Will fall over at scale

- [ ] **1. Link Health full scan is unbounded AND synchronous.** `link-health.php:215-276` — `get_posts_to_scan()` uses `posts_per_page => -1`, then `process_scan_batch()` runs in one request doing a `wp_remote_head` (10s timeout) per link. It *will* hit `max_execution_time` on any real site. Worse: a dead scan stays `status='running'` with no stale-lock reaper (:170-176), so **all future scans are permanently blocked**. The `save_post` hook (:454-472) also checks every link synchronously in the editor — 30 links can hang a save for minutes — and Link_Health has **no `module_enabled()` gate at all**. Fix: chunk via cron/Action Scheduler, reap `running` rows older than 1h, defer the save-hook check, add a module gate.
- [ ] **2. Sitemap index hit loads every post on the site.** `get_sitemap_lastmod()` (sitemap-enhancer.php:787-801) runs a full `WP_Query` per sitemap page just to compute `lastmod`, under `nocache_headers()` (:417). At 50k posts, every crawler hit to `/sitemap_index.xml` hydrates all of them. Fix: single `MAX(post_modified_gmt)` query per group + a transient invalidated on `save_post`.
- [ ] **3. `SHOW COLUMNS` on literally every request.** `Request_Monitor::boot()` runs `maybe_upgrade_schema()` → `has_column('device_label')` (request-monitor.php:49-51, 1218) *before* the module-enabled check, so an uncached information-schema query fires on every frontend/admin/REST/cron request even with the module off. Fix: fold the `device_label` check into the versioned schema gate; guard `maybe_upgrade_schema()` behind `is_admin()`.
- [ ] **4. IndexNow blocks publishing.** 15s-timeout `wp_remote_post` fires inside the save request (indexnow.php:353, hooks :83-89). Fix: `blocking => false` (move result logging to cron) or a short timeout.
- [ ] **5. Social card generator is an uncached public GD endpoint.** Any `?SAMAN_SEO_social_card=1&title=...` renders a 1200×630 PNG (~3MB alloc) under `nocache_headers()` (social-card-generator.php:48-116) — and it's the default `og:image` for posts without a featured image (frontend.php:947-955), so crawlers hammer it. Trivial CPU-DoS. Fix: write generated PNGs to uploads keyed by `md5(title|design)`, 302 to the file, long-lived cache headers.
- [ ] **6. Video schema never caches oEmbed failures.** video-schema.php:223-266 caches successes for a day but returns `null` on error without caching, so a dead YouTube/Vimeo video = a blocking 2s remote GET on every view of that post, forever (×2 fallbacks × dead videos). Fix: cache a `'none'` sentinel on failure.
- [ ] **7. Redirect lookup: 2 uncached queries per page view by default.** `maybe_redirect()` (redirect-manager.php:769-839) runs an exact-match query then a regex-rule scan on every page view; the object-cache layer (with negative caching — good) defaults **off** (`SAMAN_SEO_redirect_object_cache` `'0'`, :857). Fix: default it on when `wp_using_ext_object_cache()`; cache the regex-rule list in a versioned option.
- [ ] **8. Internal-linking engine does per-rule work before its cache check.** `filter()` (internal-linking-engine.php:97-110) calls `get_prepared_rules()` (per-rule `get_post()` + `get_permalink()`) *before* checking the rendered-content transient, so cache hits still pay O(rules) resolution on every `the_content`. Fix: check the transient first; on a miss, use a single DOM traversal evaluating all rules instead of one traversal per rule (:598-600).
- [ ] **9. Settings export walks every post ever created.** `export_meta()` (importers.php:97-116) uses `posts_per_page => -1, fields => ids` then per-ID `get_post_meta` (meta cache not primed) → ~50k queries + giant JSON in memory. Fix: one `$wpdb` query on `postmeta WHERE meta_key = '_SAMAN_SEO_meta'`.
- [ ] **10. `SAMAN_SEO_sitemap_cron` just runs `flush_rewrite_rules()` on a schedule** (sitemap-settings.php:211-217) — recurring non-trivial work with no cache to regenerate. Fix: no-op it or make it warm a real sitemap cache.

---

## P3 — Sibling drift & hardening (the class you specifically asked about)

- [ ] **11. The `eeeb412` "merged words" sanitization fix wasn't propagated.** It landed only in `Helpers\generate_content_snippet()` (helpers.php:383-392). The same raw `wp_strip_all_tags($post->post_content)` (which turns `<p>a</p><p>b</p>` into `ab`) remains at ~13 sites feeding meta descriptions, schema text, word counts and sitemap captions: audit-controller.php:310/543/648/688/752, tools-controller.php:534/824/887, admin-ui.php:659, audit.php:187, sitemap-enhancer.php:1577, video-schema.php:152. Fix: route them all through the helper.
- [ ] **12. `video-schema.php` is the odd sibling out on every axis.** Different hook priority/args than the other 8 schema services (priority 25/1-arg vs 20/2-arg), fires on any singular type via `is_singular()` + global `$post` instead of CPT-gating (:66), and injects external oEmbed `title`/`description`/`thumbnail_url` into JSON-LD with no `sanitize_text_field`/`esc_url` (:166/:187/:190). Fix: align the hook signature, gate properly, sanitize the external data.
- [ ] **13. IndexNow: `search_engine` not allowlisted before use as a host.** indexnow.php:122 sanitizes but doesn't validate against `$search_engines` (:52-58); `submit_urls()` then POSTs the URL list **and API key** to `sprintf('https://%s/indexnow', $search_engine)` (:342-344). An admin-set rogue host receives both. Also `get_settings()` returns the full `api_key` alongside the masked `api_key_display` (indexnow-controller.php:290-298). Fix: validate against the allowlist; don't return the full key.
- [ ] **14. One raw `apply_filters('saman_seo_news_sitemap_posts_per_page')`** (sitemap-enhancer.php:1494) bypasses the mandated `saman_seo_apply_filters()` helper, so it gets no legacy `SAMAN_SEO_*` / `samanseo_*` aliases (CLAUDE.md rule). Every other emission (60+) is correct. Fix: route through the helper.
- [ ] **15. Assorted hardening (low):**
  - CSV exports (redirects-controller.php:1692-1706, :1070-1084) don't neutralize leading `= + - @`; mostly mitigated by input sanitization, but prefix formula-triggering cells with `'`.
  - JSON-LD `wp_json_encode` (frontend.php:653) could add `JSON_HEX_TAG | JSON_HEX_AMP` to harden the `</script>` breakout defense for breadcrumb names.
  - Schema-migration strategy is inconsistent across the 5 table-creating services: Link_Health & Request_Monitor self-heal every boot; Redirect_Manager & IndexNow only migrate on activation; Assistants has no version option at all (can never migrate, only fresh-create).
  - `Updater/class-plugin-installer.php:41` passes `$download_url` straight to `Plugin_Upgrader->install()` with no source verification. Inert today (no caller), but constrain to the trusted `SamanLabs/*` registry before ever wiring it up.

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

## Suggested execution order

1. ~~P0 (done)~~ → **2.** shared `saman_seo_daily_maintenance` cron + default flips (kills the whole P1 log problem) → **3.** link-health batching + stale-lock reaper (P2 #1) → **4.** the propagated `wp_strip_all_tags` helper cleanup (P3 #11) → then the remaining P2 hot-path items by traffic exposure (#2, #3, #5).
