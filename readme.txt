=== AI Product Descriptions & SEO Meta for WooCommerce ===
Contributors: techbysh
Tags: ai, product description, meta description, alt text, chatgpt
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Write WooCommerce product descriptions, SEO meta, and image alt text with AI. Use your own ChatGPT, Claude, or Gemini API key.

== Description ==

AI Product Descriptions & SEO Meta for WooCommerce is a free AI writing assistant for WordPress. It writes product descriptions, SEO titles and meta descriptions, and image alt text right inside the editors you already use, with your own OpenAI (ChatGPT), Anthropic (Claude), Google (Gemini), Mistral, Groq, or OpenRouter API key.

There is no subscription and no Pro version. You pay your AI provider directly, only for what you generate.

= How it works =

1. Add an API key from the AI provider you prefer, then click **Test Connection**.
2. Open a product, post, page, category, or image and click the AI button for the field you want to fill.
3. Review the result, edit it if you like, then insert it and save.

Most results appear in a preview or in the field first, so you can edit them before saving. The plugin never publishes posts on its own.

= Key features =

**WooCommerce product content**

* Write a full product description, a short description, and a better product title from the product name.
* Suggest tags, categories, attributes, and brands, then add them with one click.
* Write purchase notes, variation descriptions, and product tab text (YIKES Custom Product Tabs).
* Fill in missing alt text for product gallery images.

**Bulk Fill for many products at once**

* Select up to 20 products in **Products → All products** and choose **AI fill empty content**.
* The plugin finds empty fields (description, short description, tags, categories, SEO meta) and writes suggestions.
* Every suggestion has a checkbox. Nothing is saved until you click **Apply**.
* Descriptions you already wrote are kept. A short existing description is expanded from its own text, and it only replaces the original if you tick it.

**SEO titles and meta descriptions**

* Write an SEO title, meta description, and focus keyword for posts, pages, products, and category pages.
* Saves them straight into Yoast SEO, Rank Math, All in One SEO (AIOSEO), or SEOPress. The plugin detects which one you use.
* With Yoast SEO, Rank Math, or SEOPress, empty Open Graph and Twitter fields are filled too. Social text you already wrote is never changed.
* Optional: generate SEO meta automatically when you publish a post, page, or product that has none. This runs in the background, so publishing stays fast.

**Image alt text and captions**

* Alt text and captions are written from what is actually in the image. The image is sent to a vision-capable AI model, not guessed from the file name.
* Works in the Media Library and on Image blocks in the block editor.
* Text is written in your site language and placed in the field for you to review.

**Block editor and classic editor**

* An AI panel in the block editor for SEO meta and featured images.
* AI writing on Paragraph, Heading, List, Quote, Pullquote, Button, Details, Verse, and Preformatted blocks.
* A writing assistant in the classic editor for posts, pages, products, and custom post types.
* AI help for category, tag, and other taxonomy descriptions.

**Custom fields, page builders, and translation plugins**

* Generate, Improve, and Translate buttons on ACF and Meta Box text, textarea, and WYSIWYG fields.
* Generate, Improve, and Translate buttons in the Elementor editor panel.
* Pick the language for each field, or keep your site language.
* With WPML or Polylang, copy generated text onto an existing translation of the post.

**AI image generator**

* Create product photos, featured images, and banners with OpenAI GPT Image or FLUX.2 (Together.ai).
* Improve your prompt with AI, pick a style preset, size, and quality.
* Save images to the Media Library or set them as the featured image.

**Prompt templates**

* 14 built-in templates for product descriptions, FAQs, care instructions, promotional copy, blog outlines, About pages, SEO meta, and image prompts.
* Create your own templates. Use `[TITLE]`, `[PRODUCT_NAME]`, and `[CONTENT]` to insert the current post or product.

**Cost control**

* Set a daily generation limit for the whole site (0 means no limit).
* See totals by type and by day on the **Usage Log** page.

= Perfect for =

* **WooCommerce stores** with many products and thin or missing descriptions.
* **Store owners who want better SEO** without writing every meta description by hand.
* **Sites that need accessible images** with real alt text.
* **Bloggers and editors** who want help with titles, intros, outlines, and excerpts.
* **Agencies and developers** who build with ACF, Meta Box, or Elementor.
* **Anyone who wants AI in WordPress without a monthly plugin subscription.**

= Supported AI providers =

* **OpenAI** (ChatGPT text models and GPT Image)
* **Anthropic** (Claude)
* **Google** (Gemini)
* **Mistral AI**
* **Groq**
* **OpenRouter** (many models with one key)
* **Together.ai** (FLUX.2 images)

The model list loads live from your provider, so you can pick new models as soon as they are available. Settings warns you if a model you saved has been retired.

= Compatibility =

* WordPress 6.0 or later, PHP 7.4 or later. Tested up to WordPress 7.1.
* WooCommerce is optional. Compatible with High-Performance Order Storage (HPOS) and the Cart & Checkout blocks.
* SEO plugins: Yoast SEO, Rank Math, All in One SEO, SEOPress.
* Editors and builders: block editor, classic editor, Elementor, ACF, Meta Box.
* Translation plugins: WPML, Polylang.
* On multisite, each site has its own settings and API keys.

= What it does not do =

* It does not publish posts or products on its own.
* It does not print SEO tags in your page head. Use an SEO plugin for that. Without one, SEO meta is saved to custom fields only.
* It does not load any scripts or styles on the public side of your site.
* It is not an AI chatbot for visitors.
* It does not include AI credits. You need an account with an AI provider.

= Privacy and your data =

* Content is sent to the AI provider you choose, and only when you generate something.
* API keys are stored in your WordPress database and sent only to that provider. They are not sent to us.
* Usage statistics stay on your site.
* Deleting the plugin removes saved API keys and the usage log. Settings and custom templates are removed too, unless you choose to keep them.

= Support =

* Ask questions in the [support forum](https://wordpress.org/support/plugin/wacdmg-ai-content-assistant/).
* Report bugs or follow development on [GitHub](https://github.com/TechbyshOrg/wordpress-ai-assistant).

= External services =

This plugin sends prompts and related content you choose to generate (such as a product title or excerpt) to the AI provider you configure. Requests happen only when you generate content, test a connection, or fetch models. No customer checkout data is sent.

1. **OpenAI API** (https://api.openai.com) — ChatGPT text and GPT Image images. Privacy: https://openai.com/policies/privacy-policy
2. **Groq API** (https://api.groq.com) — Fast text generation. Privacy: https://groq.com/privacy-policy
3. **Google Gemini API** (https://generativelanguage.googleapis.com) — Gemini text models. Privacy: https://policies.google.com/privacy
4. **Anthropic Claude API** (https://api.anthropic.com) — Claude text models. Privacy: https://www.anthropic.com/privacy
5. **Mistral AI API** (https://api.mistral.ai) — Mistral text models. Privacy: https://mistral.ai/terms
6. **OpenRouter API** (https://openrouter.ai) — Unified access to many models. Privacy: https://openrouter.ai/privacy
7. **Together.ai API** (https://api.together.xyz) — FLUX.2 image models. Privacy: https://www.together.ai/privacy
8. **Google Apps Script** (https://script.google.com) — Optional deactivation feedback, sent to the plugin author (Techbysh) and stored in a Google Sheet. It is sent **only** when an administrator deactivates the plugin and clicks **Submit & Deactivate** in the feedback popup; **Skip & Deactivate** and closing the popup send nothing. It contains the chosen reason, any text typed, the plugin, WordPress, and PHP versions, the site language, whether the site is multisite, and the active theme name. The site URL and admin email are included only if the administrator ticks the opt-in checkbox. Privacy: https://policies.google.com/privacy Terms: https://policies.google.com/terms

API keys remain in your WordPress database and are not shared with Techbysh.

= Third-party libraries =

* **DOMPurify** 3.4.17 (https://github.com/cure53/DOMPurify), used to sanitize AI-generated HTML. Licensed under MPL-2.0 or Apache-2.0. Bundled as assets/js/lib/purify.min.js (license in assets/js/lib/DOMPurify-LICENSE) and inside the compiled admin scripts.

== Installation ==

1. In WordPress, go to **Plugins → Add New Plugin**, search for "AI Product Descriptions & SEO Meta", then click **Install Now** and **Activate**. Or upload the plugin zip under **Plugins → Add New Plugin → Upload Plugin**.
2. Open **AI Assistant → Settings** in the admin menu, or click **Settings** under the plugin name on the Plugins screen.
3. On the **AI Provider** tab, choose a provider, paste your API key, and click **Test Connection**.
4. Choose a model and click **Save Settings**.
5. Optional: set the image provider (**Image Generation** tab), your SEO plugin and auto SEO meta (**SEO Integration** tab), and a daily limit (**Usage & Limits** tab).
6. Open any product, post, page, or image and start generating.

WooCommerce is optional. Product features appear only when WooCommerce is active.

== Frequently Asked Questions ==

= What does this plugin do? =

AI Product Descriptions & SEO Meta for WooCommerce writes product descriptions, SEO titles and meta descriptions, and image alt text inside WordPress. It uses your own API key from OpenAI, Anthropic, Google, Mistral, Groq, or OpenRouter. Most results appear in a preview or in the field first, so you can edit them before saving.

= Is it free? Is there a subscription? =

The plugin is free, with no Pro version and no plugin subscription. You pay your AI provider directly for the text and images you generate.

= Which AI providers and models can I use? =

OpenAI (ChatGPT and GPT Image), Anthropic Claude, Google Gemini, Mistral AI, Groq, and OpenRouter for text. OpenAI GPT Image and Together.ai FLUX.2 for images. The model list loads live from your provider, so you can choose current models.

= How do I get an API key, and what does it cost? =

Create a key in your provider's dashboard: [OpenAI](https://platform.openai.com/), [Anthropic](https://console.anthropic.com/), [Google AI Studio](https://aistudio.google.com/), [Mistral](https://console.mistral.ai/), [Groq](https://console.groq.com/), [OpenRouter](https://openrouter.ai/), or [Together.ai](https://api.together.xyz/). Prices depend on the provider and model you choose, so check their pricing pages. You can set a daily generation limit in the plugin to control usage.

= Do I need WooCommerce? =

No. SEO meta, alt text, blog writing, templates, and the image generator work on any WordPress site. Product descriptions, Bulk Fill, and other store features appear when WooCommerce is active.

= How do I generate product descriptions in bulk? =

Go to **Products → All products**, select up to 20 products, and choose **AI fill empty content** from the Bulk actions menu. The plugin writes suggestions for empty fields. Review them, keep the checkboxes you want, and click **Apply** or **Apply all reviewed**.

= Will it overwrite my existing descriptions or SEO meta? =

No. Bulk Fill only fills empty fields. A short existing description is expanded from its own text and replaces the original only if you tick it. Existing short descriptions and SEO meta are never overwritten by Bulk Fill, and auto SEO on publish only runs when a post has no SEO meta yet.

= Which SEO plugins does it work with? =

Yoast SEO, Rank Math, All in One SEO (AIOSEO), and SEOPress. The plugin detects the active one, or you can choose it under **AI Assistant → Settings → SEO Integration**. Without an SEO plugin, the meta is saved to custom fields but not printed on your pages.

= Can it write image alt text? =

Yes. It sends the image to a vision-capable AI model and writes alt text or a caption based on what is in the picture, in your site language. Use it in the Media Library or on an Image block. Your selected model must support images.

= Does it work with the block editor, classic editor, Elementor, ACF, and Meta Box? =

Yes. The block editor gets AI panels and AI writing on nine common text blocks. The classic editor gets a writing assistant. ACF and Meta Box text fields and the Elementor editor panel get Generate, Improve, and Translate buttons.

= Can it write in other languages? Does it work with WPML or Polylang? =

Yes. You can choose the language when you generate, and alt text uses your site language. With WPML or Polylang, you can copy generated text onto an existing translation of the post. Create the translation first, and review machine-written text before you publish.

= Will it slow down my website? =

No. The plugin loads nothing on the public side of your site. It only runs in the WordPress admin, and only when you click a generate button or use the background auto SEO option.

= Where are my API keys stored, and what data is sent? =

API keys are stored in your WordPress database and sent only to the provider you selected. When you generate, the plugin sends the text or image you are working on to that provider. Nothing is sent to us, and no customer or order data is sent.

= How do I control costs and who can use it? =

Set a daily generation limit for the whole site under **Settings → Usage & Limits**, and check the **Usage Log** page. Users who can edit posts or products can generate content. Only administrators can change API keys, providers, and limits.

= What happens when I delete the plugin? =

Saved API keys and the usage log are always removed, and queued background jobs are cancelled. Settings and custom templates are removed too, unless you turned on "Keep settings and custom templates when the plugin is deleted". Content and SEO meta you saved to your posts stay.

= What should I do if generation fails? =

Read the error message: it usually comes from your provider (invalid key, no credit, or a retired model). Click **Test Connection** in Settings, check your provider account has billing set up, and choose another model if yours was retired.

== Screenshots ==

1. AI Provider settings: choose OpenAI, Gemini, Claude, Mistral, Groq, or OpenRouter, add your API key, and test the connection.
2. Image Generation settings: choose the image provider, model, default size, and quality.
3. SEO Integration settings: write meta to Yoast SEO, Rank Math, AIOSEO, or SEOPress, and turn on auto SEO meta on publish.
4. Usage & Limits settings: see generation totals and set a daily limit.
5. AI Image Generator: describe an image, improve the prompt with AI, pick a style, and save it to the Media Library.
6. Content Templates: reusable AI prompts for product descriptions, blog posts, SEO meta, and images.
7. Usage Log: track AI generations by type and by day, stored on your own site.

== Changelog ==

= 2.1.1 =
* Fix: Image generation works again (GPT Image and FLUX.2).
* Fix: Alt text is written from the actual image.
* Fix: Bulk Fill keeps existing descriptions; updated AI models.
* Security: Sanitized AI output and safer term creation.
* Improvement: WooCommerce HPOS support and faster editor screens.

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

= 2.1.1 =
Important fixes: image generation works again after OpenAI retired DALL-E 3, alt text is written from the actual image, AI-generated HTML is sanitized, Bulk Fill no longer replaces existing descriptions, and outdated AI models are updated. Review Settings → Image Generation after updating.

= 2.1.0 =
Adds Bulk Fill, more content templates, broader field support, and a Settings link. Requires PHP 7.4 or later.
