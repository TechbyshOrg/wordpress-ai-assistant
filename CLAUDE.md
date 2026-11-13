# CLAUDE.md

WordPress plugin **AI Content & Meta Assistant** (slug `wacdmg-ai-content-assistant`, v2.1.0). It generates product and post content, SEO meta, alt text, taxonomy terms and images through ChatGPT, Gemini, Claude, Groq, Mistral or OpenRouter, plus Together/DALL·E for images. Requires WP 6.0+ and PHP 7.4+.

Every PHP symbol, option, hook and handle uses the `wacdmg_` / `WACDMG_` prefix. Keep it on anything new (WordPress.org requirement).

## Layout

| Path | What it is |
|---|---|
| `wacdmg-ai-content-assistant.php` | Bootstrap: constants (`WACDMG_PLUGIN_DIR/URL/VERSION`, `WACDMG_API_NAMESPACE = wacdmg/v1`), requires classes, instantiates on `plugins_loaded` |
| `includes/class-wacdmg-admin-api.php` | **Core (~2.4k lines).** All REST routes, provider HTTP calls, rate limit and usage log, templates, auto-SEO on publish |
| `includes/class-wacdmg-admin.php` | Admin menus, script enqueueing and `wacdmgAdmin` localization, term panels, product bulk action, media alt field, classic product editor mount point |
| `includes/class-wacdmg-seo.php` | Writes and reads SEO meta for Yoast, Rank Math, AIOSEO, SEOPress and others (post and term), and detects SEO plugins |
| `includes/class-wacdmg-image-ai.php` | Sideloads generated images into the Media Library |
| `includes/class-wacdmg-field-ui.php` | Injects AI buttons into ACF and Meta Box fields (`wacdmg_buttons_html`) |
| `templates/admin-wacdmg-*.php` | 5-line shells, each holding a React mount `div` |
| `app/` | React 19 source for the **admin pages and classic editors** → builds `assets/js/app.js` |
| `block-app/` | React source for the **Gutenberg extensions** → builds `assets/js/block-enhancer.js` (WP packages are externals → `wp.*`) |
| `assets/js/field-assistant.js`, `assets/js/media-alt.js` | **Hand-written** vanilla JS with no build step. Edit these files directly. They are used for ACF, Meta Box, Elementor and the media modal |
| `css/admin-style.css` | Admin styles |
| `readme.txt` | WordPress.org readme (README.md is GitHub-only) |

## Request flow

React UI → `callWpApi(path, method, body, {signal})` (sends the `X-WP-Nonce` header to `wacdmgAdmin.apiBaseUrl`) → REST route in `WACDMG_Admin_API` → `wacdmg_run_ai_prompt()` checks the rate limit, reads `wacdmg_ai_creds` and switches on `provider` to call `wacdmg_handle_ai_prompt_{chatgpt|groq|gemini|claude|mistral|openrouter}` → returns `['success', 'description'|'error']`.

- Third-party providers hook in through the filters `wacdmg_use_additional_provider` and `wacdmg_handle_ai_prompt_other`.
- Permissions: generation routes use `wacdmg_can_generate` (`edit_posts`/`edit_products`). Settings, usage and template CRUD use `wacdmg_can_manage_settings` (`manage_options`).
- To add an endpoint, add a `wacdmg_register_route(...)` line in `wacdmg_register_routes()`.

### React mounting (app/src/index.jsx)
Each component mounts only when its container id exists on the page:
`wacdmg-description-container` (DescriptionGenerator, classic product and post editor), `wacdmg-settings-container`, `wacdmg-image-generator-container`, `wacdmg-templates-container`, `wacdmg-usage-log-container`, `wacdmg-term-container` (TermAssistant), `wacdmg-bulk-fill-container` (BulkFill).
`wacdmgAdmin.currentPage` tells the UI where it is running (`settings`, `product-editor`, `post-editor`, `term-editor`, …).

### Block editor (block-app/src/wpBlock/)
paragraph and text block extensions, the SEO document panel, the AI image panel, the image alt extension, and the product AI panel. Each one registers itself when imported from `block-app/src/index.jsx`.

## Storage (wp_options, no custom tables)
- `wacdmg_ai_creds`: provider, model, per-provider keys (`chatgpt_key`, `gemini_key`, `claude_key`, …), daily limit, SEO target and auto-SEO flags. Keys are masked in `get-settings`. `wacdmg_resolve_saved_key` keeps the stored key when the UI sends back a masked value.
- `wacdmg_templates`: user templates, merged with the built-in defaults from `wacdmg_get_default_templates()`.
- `wacdmg_usage`: per-day usage counts, written by `wacdmg_log_usage()`.

## Duplicated code — keep in sync
`utils/callWpApi.js`, `utils/generationRequest.js`, `utils/variables.js` and `utils/config.js` are **identical copies** in `app/src` and `block-app/src`. `PromptGenerator.jsx` exists in both copies but they differ. The abort/settle logic in `generationRequest.js` (`createGenerationSession`) is also re-implemented by hand in `assets/js/field-assistant.js`. It prevents aborting already-settled fetches, which used to break the next generation.

## Build
```
cd app && npm run build          # → assets/js/app.js
cd block-app && npm run build    # → assets/js/block-enhancer.js
```
`npm run watch` runs a development build in watch mode. The compiled bundles **are committed**, so rebuild after any JSX change. There are no tests and no linters (no PHPCS config, no Jest setup).

## Release
- Bump the version in **three places**: the plugin header `Version:`, `WACDMG_PLUGIN_VERSION`, and `readme.txt` Stable tag and changelog.
- Pushing a tag `X.Y.Z` (no `v` prefix) runs both workflows. One deploys to WordPress.org SVN through 10up (`.wordpress-org/` goes to SVN assets). The other attaches a release zip on GitHub.
- `.distignore` controls what ships: `app/`, `block-app/`, configs, `*.map` and `*.LICENSE.txt` are excluded.
