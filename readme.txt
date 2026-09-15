=== AI Content & Meta Assistant ===
Contributors: techbysh
Donate link: https://techbysh.com/donate
Tags: woocommerce, chatgpt, seo, openai, ai
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI writer for WordPress and WooCommerce. Generate product descriptions, SEO meta, blog content, and images with ChatGPT, Gemini, or Claude.

== Description ==

AI Content & Meta Assistant is a free WordPress AI writer that helps you draft product descriptions, blog content, SEO titles, meta descriptions, and images from your dashboard. You connect your own API keys, choose a model, and generate content where you already edit posts and WooCommerce products.

There is no plugin subscription. You only pay the AI provider for the tokens or images you actually use.

= What it helps you write =

* WooCommerce product descriptions, short descriptions, tags, categories, and attributes
* Blog posts, pages, excerpts, and outlines in the block editor or classic editor
* SEO titles, meta descriptions, and focus keywords
* Alt text, captions, and featured images
* Reusable prompt templates for common store and marketing tasks

= Who it is for =

* Store owners who need better WooCommerce product copy without hiring a writer for every SKU
* Bloggers and editors who want an AI writing assistant inside WordPress
* SEO-focused sites that use Yoast, Rank Math, AIOSEO, or SEOPress
* Teams that want daily generation limits and a local usage log

= AI providers =

Pick the provider that fits your budget and quality needs:

* OpenAI ChatGPT, including DALL-E 3 for images
* Google Gemini
* Anthropic Claude
* Mistral AI
* Groq
* OpenRouter, for many models through one key
* Together.ai for FLUX and Stable Diffusion images

The plugin can load the live model list from your provider. If the request fails, it falls back to a built-in list of recommended models.

= WooCommerce product content =

On the product editor you can generate a full description, a short description for archive pages, tags, categories, attributes, and SEO meta from the product title. You can also generate a product image, save it to the Media Library, and set it as the featured image.

Need to fill gaps across several products? On **Products → All products**, use the bulk action **AI fill empty content**. The plugin only targets empty or thin fields, shows a preview, and waits for you to apply. Existing content is not overwritten, and images are skipped in bulk fill.

= Posts, pages, and editors =

Use the assistant while you write:

* Gutenberg panels for SEO meta and featured images, plus inline help on text blocks
* Classic editor support for posts, pages, and custom post types
* Category, tag, and other public taxonomy descriptions
* Optional Generate / Improve / Translate buttons on ACF and Meta Box text fields
* Elementor editor controls for selected text fields (writes in the editor, not by changing stored Elementor JSON directly)

If WPML or Polylang is active, generated copy can be sent to a translation with a review-friendly workflow.

= SEO meta =

Generate an SEO title, meta description, and focus keywords, then write them into the active SEO plugin:

* Yoast SEO
* Rank Math
* All in One SEO (AIOSEO)
* SEOPress

If none of those plugins is active, values are stored in custom meta fields. Open Graph and Twitter fields are filled only when they are empty, so existing social snippets are left alone.

= Images =

Describe the image you want, optionally enhance the prompt, and generate with DALL-E 3 or Together.ai. Save results to the Media Library. A standalone **Image Generator** page is available under **AI Assistant** for banners, product photos, and blog graphics.

= Content templates =

Save prompt patterns and reuse them with placeholders such as `[TITLE]`, `[PRODUCT_NAME]`, `[CONTENT]`, and `[KEYWORDS]`. Built-in templates cover product descriptions, short copy, FAQs, care instructions, promotional copy, blog outlines, about and category pages, SEO keywords, and image prompts. You can add your own templates at any time.

= Cost control and privacy =

* Set a daily generation limit under **AI Assistant → Settings → Usage & Limits**
* Review totals and recent activity on the **Usage Log** page
* API keys stay in your WordPress database (`wacdmg_ai_creds`) and are sent only to the provider you configured

WordPress.org currently recommends PHP 8.3 or greater for new sites. This plugin still runs on PHP 7.4+ so older but still common hosts keep working.

== Installation ==

1. In WordPress, go to **Plugins → Add New**, search for “AI Content & Meta Assistant”, and install it. Or upload the plugin zip and activate it.
2. Open **AI Assistant → Settings**, or click **Settings** next to the plugin on the Plugins screen.
3. Add at least one API key (OpenAI, Gemini, Claude, Mistral, Groq, OpenRouter, and/or Together.ai).
4. Choose your default text model. Use **Test Connection** to confirm the key works.
5. Optional: set the image provider, SEO plugin integration, and a daily generation limit.
6. Save settings, then edit a post or WooCommerce product to start generating.

WooCommerce is optional. Blog, page, SEO, and image tools work without it. Product-only features appear when WooCommerce is active.

== How to use ==

**On a WooCommerce product:** open the product, find the AI assistant panel, generate a description or other fields, preview, then insert. Use **Bulk Fill** from the products list when you want empty fields filled in batches of up to 20 products, with review before apply.

**On a post or page:** write or improve titles, excerpts, paragraphs, and SEO meta from the editor. Use a content template when you have a repeatable prompt.

**For images:** open **AI Assistant → Image Generator**, or use the featured-image controls in the editor.

**For templates:** open **AI Assistant → Content Templates** to copy, edit, or create prompts.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. WooCommerce unlocks product descriptions, short descriptions, tags, attributes, bulk fill, and related store tools. Posts, pages, SEO meta, templates, and image generation work on a normal WordPress site.

= Is this plugin free? Is there a Pro version? =

The plugin is free to install. There is no paid Pro edition in this plugin. You bring your own API keys and pay the AI providers directly for usage.

= How do I get API keys? =

Create keys on the provider sites you want to use:

* OpenAI: https://platform.openai.com/
* Anthropic Claude: https://console.anthropic.com/
* Google Gemini: https://aistudio.google.com/
* Mistral: https://console.mistral.ai/
* Groq: https://console.groq.com/
* OpenRouter: https://openrouter.ai/
* Together.ai (images): https://api.together.xyz/

= What is OpenRouter? =

OpenRouter is a unified API. One key can reach many models from OpenAI, Anthropic, Google, Meta, and others. It is useful if you want to compare models without managing several accounts.

= Will my API keys leave my site? =

Keys are stored in your WordPress options table. They are sent only to the official provider API when you generate content or test a connection. The plugin does not resell keys or route traffic through a Techbysh proxy.

= Which SEO plugins are supported? =

Yoast SEO, Rank Math, All in One SEO, and SEOPress. You can leave integration on Auto or pick a plugin in Settings. If no SEO plugin is active, meta is saved to generic custom fields.

= Does generated SEO overwrite my existing Yoast or Rank Math fields? =

SEO titles, descriptions, and keywords are written to the detected plugin fields. Open Graph and Twitter fields are updated only when those social fields are empty.

= How does WooCommerce bulk fill work? =

Select products in **Products → All products**, choose **AI fill empty content**, and review the Bulk Fill screen. Only empty or thin fields are generated. You preview each product and click Apply. Images are not generated in this flow. Up to 20 products can be processed in one selection.

= Does bulk fill replace descriptions I already wrote? =

No. It is designed to fill gaps. Review the preview before you apply.

= Can I generate content in other languages? =

Yes. Choose a language in the assistant when you generate. Supported languages include English, Spanish, French, German, Italian, Portuguese, Dutch, Arabic, Hindi, Japanese, Chinese (Simplified), Korean, Russian, Turkish, and Polish.

= Does it work with Gutenberg and the classic editor? =

Yes. Block editor panels cover SEO and featured images, and text-block actions can draft or improve selected content. The classic editor gets an assistant on posts, pages, products, and other supported types.

= Does it work with ACF, Meta Box, or Elementor? =

On ACF and Meta Box, Generate / Improve / Translate actions are available for text, textarea, and WYSIWYG fields. In Elementor, those actions run on the selected control in the editor.

= Does it work with WPML or Polylang? =

When translation languages are available, you can generate copy and send it toward a translation. Always review translated text before you publish.

= How do content templates work? =

Templates are saved prompts. Placeholders such as `[TITLE]`, `[PRODUCT_NAME]`, `[CONTENT]`, and `[KEYWORDS]` are replaced with the current post or product data before the prompt is sent. Built-in templates cannot be deleted; your custom templates can.

= Where do I set a daily limit? =

Go to **AI Assistant → Settings → Usage & Limits**. The limit applies to the whole site, not per user role. Administrators manage settings and can view the Usage Log.

= Who can generate content? =

Users who can edit the relevant content and pass the plugin’s capability checks can generate. Only administrators can change API keys, providers, and rate limits.

= What should I do if generation fails? =

The error shown is usually the provider message (invalid key, quota, deprecated model, or network). Test the connection in Settings, confirm billing on the provider account, and try another model if the selected one was retired.

= Which WordPress and PHP versions are supported? =

Requires WordPress 6.0 or later and PHP 7.4 or later. Tested up to WordPress 7.1. WordPress recommends PHP 8.3+ for new hosting, which this plugin supports.

= Can the plugin write shipping or legal policies for me? =

You can prompt it, but you should not treat generated legal, medical, or compliance text as final. Review everything before it goes live.

== Screenshots ==

1. Multi-provider settings — API keys, models, rate limits, and SEO integration.
2. Standalone AI image generator with prompt enhancer and style presets.
3. Content templates with reusable prompts and placeholders.
4. Usage log with generation totals and recent activity.
5. WooCommerce product assistant for descriptions, short descriptions, tags, and SEO.
6. Gutenberg AI assistant for outlines, rewriting, SEO meta, and featured images.

== Changelog ==

= 2.1.0 =
* Expanded AI writing across more WordPress and WooCommerce fields, including taxonomies, ACF, Meta Box, and Elementor.
* Added Bulk Fill for empty WooCommerce product fields, with preview before apply.
* Added SEOPress support and safer handling of empty Open Graph / Twitter fields.
* Added more built-in content templates for products, marketing, SEO, pages, and images.
* Added a Settings link on the Plugins screen.
* Improved admin page layout so settings, templates, usage, and related screens use available width.
* Compatibility: tested up to WordPress 7.1. Requires PHP 7.4+.

= 2.0.1 =
* Fixed several general bugs and made improvements.

= 2.0.0 =
* Major release: Added OpenRouter API provider support.
* Implemented dynamic model fetching to prevent "model decommissioned" issues.
* Redesigned settings UI for better alignment.
* Removed non-standard emojis from settings tabs and WP menus to ensure database and clean UI compatibility.
* Added detailed error reporting to display raw API and server errors instead of generic network errors.
* Integrated CI/CD deploy workflows for automated asset and plugin updates.

= 1.1.0 =
* Feature overhaul: Multi-provider support (Gemini, Claude, Mistral, Groq, ChatGPT).
* Added standalone AI Image Generator page with prompt enhancer.
* Added AI featured image generation and Media Library sideloading.
* Added Gutenberg document-level panels for AI SEO Assistant and AI Featured Image.
* Added WooCommerce short description, product tags, and SEO generator.
* Added standalone Content Templates manager.
* Added localized Usage Log dashboard.
* Added tabbed settings and daily rate limit configuration.

= 1.0.1 =
* General Bug Fixes

= 1.0.0 =
* First stable release. Ensured OpenAI API key integration.

== Upgrade Notice ==

= 2.1.0 =
Adds Bulk Fill, more content templates, broader field support, and a Settings link. Requires PHP 7.4 or later.

== External Services ==

This plugin sends prompts and related content you choose to generate (such as a product title or excerpt) to the AI provider you configure. Requests happen only when you generate content, test a connection, or fetch models. No customer checkout data is sent.

1. **OpenAI API** (https://api.openai.com) — ChatGPT text and DALL-E 3 images. Privacy: https://openai.com/policies/privacy-policy
2. **Groq API** (https://api.groq.com) — Fast text generation. Privacy: https://groq.com/privacy-policy
3. **Google Gemini API** (https://generativelanguage.googleapis.com) — Gemini text models. Privacy: https://policies.google.com/privacy
4. **Anthropic Claude API** (https://api.anthropic.com) — Claude text models. Privacy: https://www.anthropic.com/privacy
5. **Mistral AI API** (https://api.mistral.ai) — Mistral text models. Privacy: https://mistral.ai/terms
6. **OpenRouter API** (https://openrouter.ai) — Unified access to many models. Privacy: https://openrouter.ai/privacy
7. **Together.ai API** (https://api.together.xyz) — FLUX and Stable Diffusion image models. Privacy: https://www.together.ai/privacy

API keys remain in your WordPress database and are not shared with Techbysh.

For help: https://techbysh.com/support
GitHub: https://github.com/TechbyshOrg/wordpress-ai-assistant
