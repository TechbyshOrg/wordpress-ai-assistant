# AI Content & Meta Assistant — Roadmap

Internal planning notes. **Not deployed**: `docs/` is listed in `.distignore`.

- Last updated: 2026-11-13
- Current version: 2.1.1 (not yet released or committed at the time of writing)
- IDs (BUG-, SEC-, UX-, PERF-, COMP-, A11Y-, PRIV-, GROW-, CODE-, FEAT-) refer to the full audit from 2026-11-13.

---

## Status at a glance

| Phase | Scope | Status |
|---|---|---|
| 1. NOW (2.1.1) | Broken features, security, data safety | **Done** — awaiting manual test and release |
| Quick wins | Small fixes, under 1 hour each | **Done** (in 2.1.1) |
| 2. NEXT (2.2) | Onboarding, cost control, SEO output, i18n, bundle size | Not started — needs decisions below |
| 3. LATER | Large features and refactors | Not started |

---

## Decisions needed before Phase 2

1. **Contributor access** — should Contributors keep AI access by default, or should it be Author and above? (Affects SEC-01.)
2. **WordPress 7.0 Connectors** — read API keys from core Settings → Connectors when present? Proposed rule: plugin keys win if both are set. (Affects FEAT-03.)
3. **Paid tier** — planned or not? If yes, background bulk generation (FEAT-04) and the Media Library bulk alt pass are the natural paid features.

---

## Phase 1 — NOW (done in 2.1.1)

| Step | ID | What changed |
|---|---|---|
| 1 | BUG-01, BUG-14, BUG-15 | DALL-E 3 (retired 2026-05-12) replaced by GPT Image (default `gpt-image-2`, model selectable and refreshable). New save path for base64 images. Legacy settings normalized on read. |
| 2 | BUG-02 | Auto-SEO on publish limited to viewable post types (`wacdmg_auto_seo_post_types` filter), queued via Action Scheduler / WP-Cron, one JSON request instead of three, works for scheduled posts. |
| 3 | BUG-03, UX-03 | Alt text and captions sent to vision models with the actual image (768px copy). Results fill the field for review; bulk gallery fill saves with `save: true`. |
| 4 | BUG-04 | Field assistant (ACF, Meta Box, variations, YIKES, Elementor) has a language selector; defaults to site language. |
| 5 | SEC-02, SEC-03, SEC-07, PERF-03 | DOMPurify sanitizes AI HTML at every editor sink. New terms only created with the taxonomy's `edit_terms` capability. |
| 6 | BUG-08 | Bulk Fill never replaces existing content unless explicitly ticked (`replace` param enforced server-side). Thin detection by characters (`wacdmg_thin_description_chars`). |
| 7 | BUG-10 | Model defaults updated (central `DEFAULT_TEXT_MODELS`), retired-model warnings in Settings, FLUX.2 for Together.ai. |
| 8 | BUG-13 | `uninstall.php` removes API keys and usage log always; settings/templates unless "keep" is on. Deactivation cancels queued jobs. |
| 9 | COMP-02 | HPOS and Cart & Checkout Blocks compatibility declared. |
| 10 | PERF-02 | Field assistant observes only `#woocommerce-product-data`, batched per animation frame. |

### Manual test checklist for 2.1.1 (needs at least one real API key)

1. Images: Settings → Image Generation → Refresh list → Save. Generate on the Image Generator page (saved and unsaved/Download). Set a featured image from the block editor.
2. Auto-SEO: enable, publish a post — publishing is instant, SEO meta appears within about a minute. Adding a menu link adds no usage.
3. Alt text: Media Library → Generate with AI on a real photo. Text describes the photo and is not saved silently.
4. Field language: ACF field → pick a language → Translate.
5. Security: as a Contributor, generate categories — only existing ones are applied.
6. Bulk Fill: one product with an empty description, one with a one-line description. Empty is filled; short one is unticked and kept unless ticked.
7. Models: Settings dropdowns load live lists; a retired saved model shows a yellow warning.
8. WooCommerce → Settings → Advanced → Features: plugin not flagged.
9. Uninstall (throwaway site): delete plugin — API keys gone.

### Known unverified items

- "Tested up to" value — confirm against the latest WordPress release before each release.
- Groq Llama retirement and exact Gemini Pro model ID — left out of fallback lists; live lists cover them.
- AIOSEO and Yoast write behaviour (BUG-05, BUG-06) — test against real plugin versions in Phase 2.

---

## Quick wins (done 2026-11-13, part of 2.1.1)

- [x] Fix `Plugin URI` in the plugin header (now `wordpress.org/plugins/wacdmg-ai-content-assistant/`).
- [x] Confirm "Tested up to" — 7.1 is correct (WordPress 7.1 released 2026-08-19; local test site runs 7.1.4).
- [x] Daily limit 0 = no limit. Also fixed: Settings ignored a saved 0 on load and reset it to 100 on the next save.
- [x] Removed `react-scripts`, testing packages, `sass`, `web-vitals`, `webpack-dev-server`, the `start` script, CRA `public/` dirs, and 8 dead source files. `block-enhancer.js` 248 KB → 72 KB (no React bundled). `npm audit`: 70/69 advisories → **0** in both apps.
- [x] Stopped tracking the stale 1.2 MB source map; `*.map` is git-ignored.
- [x] SEC-04: Gemini key sent in `x-goog-api-key` header (generation and model list). Model list now requests `pageSize=1000` (was cut off at 50).
- [x] PRIV-03: `*.LICENSE.txt` files ship in the release zip.
- [x] PRIV-01: suggested privacy-policy text registered.

Found while doing these:

- **DOMPurify 3.2.6 (added in Step 5) had known bypasses**, including one for `USE_PROFILES`, which this plugin uses. Upgraded to 3.4.17 everywhere (both apps, `assets/js/lib/purify.min.js`, PHP script version, readme).
- **Vendored DOMPurify would not have shipped.** `.distignore`'s `vendor` rule (meant for Composer) matched `assets/js/vendor/`. Moved to `assets/js/lib/`. Lesson: check the release file list after adding any new folder (see "Release check" below).
- `CLAUDE.md` was not excluded from the release; now listed in `.distignore` along with `docs/`.
- Both `package-lock.json` and `yarn.lock` exist in each app; npm keeps both in sync. Pick one package manager eventually (low priority).

---

## Phase 2 — NEXT (target 2.2, about 1–2 months)

Ordered by impact.

1. **WordPress 7.0 Connectors (FEAT-03)** — use core-stored provider keys via the AI Client; keep plugin keys as fallback (WP < 7.0, Groq, Mistral, OpenRouter). Keep the plugin's own rate limit (core has none). *Medium.*
2. **First-run setup and errors (UX-01, UX-02, UX-07)** — "Connect an AI provider" notice and 3-step setup card; empty states in editor panels; replace `alert()` with inline notices; actionable error messages (401 → check key link, 429 → provider quota). *Small–medium.*
3. **Cost control (SEC-01, FEAT-07, BUG-09)** — "Who can use AI" roles setting + filter; per-user daily limits; count every provider request; store token usage from responses and show cost in Usage Log. *Medium. Breaking for Contributors — needs decision 1.*
4. **SEO meta that shows up (BUG-05, BUG-06, BUG-07)** — AIOSEO model API instead of post meta; refresh Yoast indexables; output `<title>`/meta description for the generic target when no SEO plugin is active (default off for existing sites). *Medium.*
5. **Output quality (FEAT-05, FEAT-06, COMP-05)** — brand-voice setting added to every prompt; product prompts include attributes, price, existing text, optional image; all panels default to site language (`wacdmgAdmin.defaultLanguage` is already localized). *Small–medium.*
6. **Translatable admin UI (COMP-01)** — wrap React and vanilla JS strings with `wp.i18n`; `wp_set_script_translations()` for all handles. *Medium.*
7. **Bundle size (PERF-01, PERF-05)** — `block-enhancer.js` is already down to 72 KB (quick wins). Remaining: `app.js` (≈345 KB) still bundles React 19 — migrate both apps to `@wordpress/scripts` with `wp.element` externals and `.asset.php` deps; extract CSS. Do before or with item 6. *Medium.*
8. **Accessibility (A11Y-01 to A11Y-05)** — label/input association, Settings `TabPanel`, `aria-live` statuses, contrast (`#94a3b8` → `#646970`), emoji-only labels. *Small.*
9. **Stability leftovers** — BUG-11 truncation/Gemini thinking detection; BUG-12 Elementor writes to the focused control; BUG-16 buttons only where the script loads; BUG-17 consistent error shapes and 4xx on failed tests; BUG-18 remove unused `/chat`. *Small each.*
10. **Growth (GROW-01, GROW-02)** — review request after 20 successful generations (dismissible); "Report a problem" link. *Small.*

## Phase 3 — LATER (3+ months)

- **FEAT-04** Background bulk generation via Action Scheduler: whole catalogue, progress, review queue, approve all, cost estimate first.
- **FEAT-02 (part 2)** Media Library bulk alt text pass.
- **FEAT-08** Register actions with the WordPress Abilities API (agents / MCP).
- **FEAT-09** Custom OpenAI-compatible endpoint (Ollama, LM Studio, vLLM); validate URL; admin-only.
- **FEAT-10** WP-CLI: `wp wacdmg fill --post_type=product --fields=excerpt,seo --dry-run`.
- **FEAT-11** Streaming responses.
- **CODE-01 to CODE-06** Split `class-wacdmg-admin-api.php` into provider classes and a key resolver; one shared JS utils package; PHPCS + Plugin Check in CI; PHPUnit/Jest tests (port the 2.1.1 runtime test scripts); prompt/settings filters; release script to keep the three version fields in sync.

---

## Conventions

- **Version** lives in three places: plugin header `Version:`, `WACDMG_PLUGIN_VERSION`, `readme.txt` Stable tag. Also add an Upgrade Notice.
- **Changelog** entries in `readme.txt` (and `README.md`) use only: Fix, New, Improvement, Security.
- **Shared JS utils** (`callWpApi.js`, `generationRequest.js`, `variables.js`, `config.js`, `sanitizeHtml.js`) must stay identical in `app/src/utils` and `block-app/src/utils`.
- **Model defaults**: `DEFAULT_TEXT_MODELS` in `includes/class-wacdmg-admin-api.php` and the fallback lists in `variables.js` must agree. Review on every release.
- **Rebuild** after JSX changes: `cd app && npm run build` and `cd block-app && npm run build` — bundles are committed. For development use `npm run watch` (there is no dev server; scripts need the WordPress admin page).
- **Third-party JS** that ships unbundled lives in `assets/js/lib/` (not `vendor/`, which `.distignore` excludes). Credit it in the readme's "Third-party Libraries" section and keep its license file next to it.
- **Release check**: before tagging, list what the zip will contain (git-tracked files minus `.distignore` patterns, matched per path segment like `rsync --exclude-from`). Confirm new files ship and dev files (`docs/`, `CLAUDE.md`, `app/`, `*.map`) do not.
