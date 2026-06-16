=== AI Writer, SEO Meta & WooCommerce Product Description Assistant ===
Contributors: techbysh
Donate link: https://techbysh.com/donate
Tags: ai writer, content generator, seo meta, woocommerce description, openrouter, chatgpt, claude, gemini, mistral, groq, content assistant, product descriptions, seo titles, ai images, flux
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart AI content generator for WordPress and WooCommerce. Instantly generate blog posts, product descriptions, SEO tags, and high-quality AI images. Supports OpenAI, Google Gemini, Anthropic Claude, Mistral, Groq, and OpenRouter with real-time model loading.

== Description ==

**AI Content & Meta Assistant** by **Techbysh** is a premium, all-in-one AI writing, media generation, and SEO optimization suite designed to scale your content creation workflow. Seamlessly integrate the power of leading AI models directly into your WordPress dashboard, classic editor, WooCommerce product editor, and Gutenberg block editor.

With version 2.0.0, the plugin has been upgraded to a major release, introducing a multi-provider platform supporting text generation from **OpenAI ChatGPT**, **Google Gemini**, **Anthropic Claude**, **Mistral AI**, **Groq**, and **OpenRouter**, as well as fast image generation via **DALL-E 3** and **Together.ai (FLUX / Stable Diffusion)**.

By connecting directly to your own API accounts, the plugin operates without any monthly subscription fees—you only pay for the exact API tokens you use.

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

== Detailed Feature Highlights ==

### 1. WooCommerce Product Content & Image Generator
Automate your e-commerce store writing using the built-in WooCommerce editor panel:
* **Product Description Generator:** Automatically write compelling, benefit-focused product descriptions based solely on your product title.
* **Excerpts & Short Descriptions:** Instantly draft brief summaries perfect for product archive pages, and insert them directly into the WooCommerce Short Description box.
* **Smart WooCommerce Tags:** Generate 8 to 12 contextually relevant product tags with a single click to improve internal site search and SEO taxonomy.
* **WooCommerce SEO Metadata:** Generate search-engine optimized product titles, focus keywords, and meta descriptions.
* **AI Product Image Generator:** Describe the product image you need, select a generation style, and the plugin will generate it, upload it to your WordPress Media Library, and set it as the product's featured image.

### 2. Gutenberg Block Editor AI writing Assistant
Enhance your blogging workflow with the dedicated Document and Block sidebar panels:
* **Inline Paragraph Enhancer:** Select a block or draft a paragraph from your post title.
* **AI Article Outliner:** Generate structured blog outlines with H2/H3 headings and bullet points using models like Claude 3.5 Sonnet or Gemini 1.5 Pro.
* **Content Rewriter & Improver:** Refine your writing instantly by choosing from 9+ tones (Professional, Conversational, Friendly, Formal, etc.) or applying custom prompt instructions.
* **Automated SEO Panel:** Generate Google-friendly meta descriptions, focus keywords, and SEO titles. Seamlessly syncs with Yoast, Rank Math, and AIOSEO.
* **Featured Image Generator:** Read the title of your post, auto-suggest a graphic prompt, generate a high-quality visual, and set it as the post's featured image.

### 3. Standalone Image Generator Canvas
A playground for creating website graphics, blog banners, and social assets:
* **Multiple Image Engines:** Generate images using DALL-E 3 or Together.ai (hosting FLUX.1-schnell, FLUX.1-dev, and Stable Diffusion XL).
* **AI Prompt Enhancer:** Automatically expand short image descriptions into highly detailed art prompts using AI.
* **Style Presets:** Select from pre-configured styles like Photorealistic, Digital Art, Cinematic, 3D Render, Anime, Sketch, or Vector.
* **Batch Generation:** Generate multiple images simultaneously and save your favorites directly to the Media Library.

### 4. Custom Content Templates
Create, edit, and manage custom prompt templates for repetitive tasks:
* **Placeholders Support:** Define placeholders like `[TITLE]`, `[PRODUCT_NAME]`, `[KEYWORDS]`, or `[CONTENT]` that automatically pull data from your current post or product.
* **Preloaded Library:** Includes standard templates for social posts, FAQ generation, AIDA marketing copywriting, and summary writing.

### 5. Administration & Cost Control
* **Daily Generation Limits:** Enforce maximum daily generations to prevent unexpected API costs or abuse by editors and writers.
* **Detailed Usage Logs:** View daily and monthly stats with interactive visual charts indicating how many text and image generations occurred.
* **Clean & Secure UI:** Responsive settings panel without non-standard emojis, aligned for a premium aesthetic, and designed with database-safe values.

== Screenshots ==

1. **Multi-Provider AI Settings Panel** — Manage API keys, select default models, set rate limits, and choose SEO plugin integrations.
2. **Interactive Standalone AI Image Generator** — Generate high-quality images using FLUX, Stable Diffusion, and DALL-E 3 with custom presets and styles.
3. **Custom Content Templates Manager** — Create, organize, and reuse custom prompt templates with flexible dynamic placeholders.
4. **AI Generation Usage Log & Analytics** — View detailed charts, daily limits, and generation history stats directly in your dashboard.
5. **WooCommerce Product Content & Meta Enhancer** — Click to generate product descriptions, short descriptions, meta fields, and tags inside WooCommerce.
6. **Gutenberg Block Editor AI Assistant** — Draft outlines, rewrite content, apply prompts, and automate SEO metadata and featured images.

== Frequently Asked Questions ==

= How do I get API keys for the providers? =
You can register for API keys directly on the official developer platforms:
* **OpenAI (ChatGPT & DALL-E):** https://platform.openai.com/
* **Anthropic Claude:** https://console.anthropic.com/
* **Google Gemini:** https://aistudio.google.com/
* **Mistral AI:** https://console.mistral.ai/
* **Groq (Fast Llama/Gemma):** https://console.groq.com/
* **OpenRouter (Unified API):** https://openrouter.ai/

= What is OpenRouter and how does it benefit me? =
OpenRouter is an API aggregator that lets you access hundreds of open-source and proprietary AI models (including models from Meta, Mistral, Google, and Anthropic) using a single API key and account. It is highly cost-effective, handles model fallback, and ensures you always have access to the latest open models.

= Will my API keys be shared or stored externally? =
No. All API keys and secrets are saved locally in your own self-hosted WordPress database under the standard WordPress option tables (`wacdmg_ai_creds`). They are only sent directly to the official provider endpoints during API requests, ensuring complete privacy and data security.

= How does SEO plugin integration work? =
The plugin automatically detects if you have **Yoast SEO**, **Rank Math**, or **All in One SEO (AIOSEO)** active. When you generate SEO metadata, it writes the title, description, and focus keywords directly to the corresponding fields of those plugins. If no SEO plugin is active, it saves them to custom WordPress meta fields.

= What image generators are supported? =
You can use **DALL-E 3** (via your OpenAI key) or **Together.ai** (which hosts ultra-fast open models like **FLUX.1-schnell** and Stable Diffusion).

= Are there daily generation limits? =
Yes, you can configure daily rate limits inside the **AI Assistant → Settings** page under the General tab. This helps prevent unexpected API costs if you have multiple editors on your WordPress site.

= Does this plugin support translation? =
Yes, the plugin supports generating text content and product descriptions in over 15 languages, including Spanish, German, French, Italian, Chinese, Japanese, and Portuguese.

= Can I use this plugin for free? =
Yes. The plugin itself is completely free to download and use. There are no monthly subscription fees. You only pay for your actual API usage directly to the AI providers (OpenAI, Gemini, Claude, Mistral, Groq, Together.ai, or OpenRouter).

= Which SEO plugins are fully compatible? =
The plugin is fully compatible with:
* **Yoast SEO** (Free & Premium)
* **Rank Math SEO** (Free & Pro)
* **All in One SEO / AIOSEO** (Free & Pro)
* Fallback custom meta fields (for themes that read custom fields)

= What are Custom Templates and how do they work? =
Custom Templates are pre-saved prompt patterns that you can reuse across your posts or products. By utilizing dynamic tags like `[TITLE]`, `[PRODUCT_NAME]`, `[KEYWORDS]`, or `[CONTENT]`, the plugin automatically swaps these tags with the actual product/post information before sending the prompt to the AI.

= How does the Dynamic Model Fetching work? =
Instead of hardcoding model lists which frequently get decommissioned, the plugin queries the active API keys to retrieve the live list of models supported by the provider. If the API is offline or you do not have a key entered, the plugin falls back to a curated list of active recommended models.

= What should I do if I get a network or connection error? =
The plugin includes detailed error logging. If a call fails, it will display the exact error message returned by the provider (e.g., "Invalid API Key", "Quota Exceeded", "Model Deprecated"). You should verify your key and check your provider account's billing status or credit balance.

= Is WooCommerce bulk generation supported? =
Currently, you can generate and optimize descriptions, titles, tags, and images on a product-by-product basis using the WooCommerce product editor screen. This ensures high-quality editorial control. Bulk auto-generation tools are planned for future major releases.

= Can I configure limits for different user roles? =
Rate limits configured in the Settings menu apply globally to all users on the site to safeguard your API budget. Only users with administrator privileges can modify settings or view the Usage Log.

== Changelog ==

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
