=== AI Writer, SEO Meta & WooCommerce Product Description Assistant ===
Contributors: techbysh
Donate link: https://techbysh.com/donate
Tags: ai writer, content generator, seo meta, woocommerce description, openrouter
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart AI content generator for WordPress and WooCommerce. Instantly generate blog posts, product descriptions, SEO tags, and high-quality AI images. Supports OpenAI, Google Gemini, Anthropic Claude, Mistral, Groq, and OpenRouter with real-time model loading.

== Description ==

**AI Content & Meta Assistant** by **Techbysh** is a premium, all-in-one AI writing and media suite designed to scale your content creation. Seamlessly integrate the power of leading AI models directly into your WordPress dashboard, classic editor, WooCommerce product editor, and Gutenberg block editor.

With version 2.0.0, the plugin has been upgraded to a major release, introducing a multi-provider platform supporting text generation from **OpenAI ChatGPT**, **Google Gemini**, **Anthropic Claude**, **Mistral AI**, **Groq**, and **OpenRouter**, as well as fast image generation via **DALL-E 3** and **Together.ai (FLUX / Stable Diffusion)**.

### Why choose AI Content & Meta Assistant?
* **Utmost SEO Optimization:** Generate SEO-friendly titles, descriptions, focus keywords, and tags. Works out-of-the-box with **Yoast SEO**, **Rank Math**, and **All in One SEO (AIOSEO)**.
* **WooCommerce Autopilot:** Save hours writing product descriptions. Generate descriptions, short excerpts, and product tags directly from the WooCommerce product edit screen.
* **Dynamic Model Fetching:** Never deal with broken "model decommissioned" issues. The plugin dynamically queries API endpoints to load active models in real-time.
* **Custom Prompt Templates:** Save your own prompt patterns (e.g., custom blog structures, marketing angles) and re-run them with one click.
* **Usage Dashboard:** Track daily and monthly generation limits, API calls, and usage logs directly from your WordPress dashboard.

== Installation ==

1. Upload the plugin folder `wordpress-ai-assistant` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **AI Assistant → Settings** in your WordPress sidebar.
4. Input your API keys for the providers you wish to use (OpenAI, Gemini, Claude, Mistral, Groq, or OpenRouter).
5. Set your preferred default models and save settings.

== Key Features & Locations ==

Here is where you can find and use all the features of the AI Content & Meta Assistant:

### 1. WooCommerce Product Editor (WooCommerce Edit Product Page)
* **Generate Description from Title:** Click to instantly write a persuasive product description in 15+ languages.
* **Improve Description:** Select a tone and rewrite existing descriptions for clarity or SEO.
* **Improve Title:** Rewrite product titles to make them more SEO-friendly and click-worthy.
* **Short Description Generator:** Automatically draft brief product summaries and insert them directly into the WooCommerce Excerpt box.
* **Product Tags Generator:** Create 8-12 relevant WooCommerce product tags in one click.
* **Product SEO Meta:** Generate SEO title, focus keywords, and meta description from your product title and content.
* **Generate AI Product Image:** Describe a product image, generate it, save to the Media Library, and optionally set it as the featured image.

### 2. Gutenberg Block Editor (Posts, Pages & Custom Post Types)
* **AI Paragraph Enhancer (Block Settings Sidebar):**
  * Generate a paragraph from the post title.
  * Apply custom prompts to generate text inline.
  * Create complete blog post outlines (H2 headings & bullet points).
  * Improve existing content or rewrite it with custom prompts in different tones.
* **AI SEO Assistant (Document Settings Sidebar):**
  * Auto-generate focus keywords, SEO title, and meta descriptions.
  * Automatically writes directly to **Yoast SEO**, **Rank Math**, or **AIOSEO** fields if installed (or falls back to custom meta).
* **AI Featured Image (Document Settings Sidebar):**
  * Auto-fill prompt from post title.
  * Generate featured images with style presets (photorealistic, digital art, sketch, etc.).
  * Automatically save to Media Library and set as Featured Image.

### 3. Standalone Admin Submenus (AI Assistant Menu)
* **Settings:** Redesigned tabbed menu to manage providers, configure DALL-E/Together.ai image models, set up SEO plugin mappings, and define daily generation limits.
* **Image Generator:** A standalone high-end image generator page supporting batch generations, prompt enhancement with AI, style presets, custom dimensions, and one-click saving to the WordPress Media Library.
* **Content Templates:** Create, save, edit, and organize custom prompt templates with placeholders like `[PRODUCT_NAME]` or `[TITLE]`. Preloaded with useful e-commerce, blog outline, and SEO templates.
* **Usage Log:** Detailed analytics dashboard tracking total generations, daily generation counts, monthly activity, and usage categorized by type.

== Screenshots ==

1. **Multi-Provider AI Settings Panel** — Manage API keys, select default models, set rate limits, and choose SEO plugin integrations.
2. **Interactive Standalone AI Image Generator** — Generate high-quality images using FLUX, Stable Diffusion, and DALL-E 3 with custom presets and styles.
3. **Custom Content Templates Manager** — Create, organize, and reuse custom prompt templates with flexible dynamic placeholders.
4. **AI Generation Usage Log & Analytics** — View detailed charts, daily limits, and generation history stats directly in your dashboard.
5. **WooCommerce Product Content & Meta Enhancer** — Click to generate product descriptions, short descriptions, meta fields, and tags inside WooCommerce.
6. **Gutenberg Block Editor AI Assistant** — Draft outlines, rewrite content, apply prompts, and automate SEO metadata and featured images.

== Frequently Asked Questions ==

= How do I get API keys for the providers? =
You can sign up on the respective developer consoles of each provider:
* **OpenAI:** https://platform.openai.com/
* **Anthropic Claude:** https://console.anthropic.com/
* **Google Gemini:** https://aistudio.google.com/
* **Mistral AI:** https://console.mistral.ai/
* **Groq:** https://console.groq.com/
* **OpenRouter:** https://openrouter.ai/

= What is OpenRouter and how does it benefit me? =
OpenRouter is a unified interface that lets you access hundreds of open-source and proprietary models (including Llama 3, Claude, Gemini, and GPT-4) using a single API key. It is highly cost-effective and provides access to the latest AI models.

= Will my API keys be shared with anyone? =
No. All API keys and generated credentials are saved locally in your WordPress database using standard WordPress option tables (`wacdmg_ai_creds`). They are only sent directly to the official provider endpoints during API requests.

= How does SEO integration work? =
The plugin automatically detects if you have **Yoast SEO**, **Rank Math**, or **All in One SEO (AIOSEO)** active. When you generate SEO metadata, it writes the title, description, and focus keywords directly to the corresponding fields of those plugins. If no SEO plugin is active, it saves them to custom WordPress meta fields.

= What image generators are supported? =
You can use **DALL-E 3** (via your OpenAI key) or **Together.ai** (which hosts ultra-fast open models like **FLUX.1-schnell** and Stable Diffusion).

= Are there daily generation limits? =
Yes, you can configure daily rate limits inside the **AI Assistant → Settings** page under the General tab. This helps prevent unexpected API costs if you have multiple editors on your WordPress site.

= Does this plugin support translation? =
Yes, the plugin supports generating text content and product descriptions in over 15 languages, including Spanish, German, French, Italian, Chinese, Japanese, and Portuguese.

== Changelog ==

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

== Support ==

For help, visit: https://techbysh.com/support
Donate to support development: https://techbysh.com/donate
GitHub repo: https://github.com/TechbyshOrg/wordpress-ai-assistant

== External Services ==

This plugin makes direct API requests to the following external servers depending on your configuration:

1. **OpenAI API** (https://api.openai.com) - For text (ChatGPT) and image (DALL-E 3) generation.
2. **Groq API** (https://api.groq.com) - For fast text generation.
3. **Google Gemini API** (https://generativelanguage.googleapis.com) - For Gemini text models.
4. **Anthropic Claude API** (https://api.anthropic.com) - For Claude text models.
5. **Mistral AI API** (https://api.mistral.ai) - For Mistral text models.
6. **OpenRouter API** (https://openrouter.ai) - For hundreds of unified AI models.
7. **Together.ai API** (https://api.together.xyz) - For FLUX and Stable Diffusion image models.

All keys and tokens are stored securely in your WordPress database and never shared with third parties.
