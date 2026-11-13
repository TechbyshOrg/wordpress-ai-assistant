// =========================================================================
// AI Provider Models
// =========================================================================

// These are fallbacks only. Settings loads each provider's live model list, and
// providers retire models often — keep defaults in sync with DEFAULT_TEXT_MODELS in
// includes/class-wacdmg-admin-api.php. Last reviewed: November 2026.

// OpenAI ChatGPT models
export const chatgptModels = [
    { value: 'gpt-5.4-mini', label: 'GPT-5.4 mini — Recommended' },
    { value: 'gpt-5.5', label: 'GPT-5.5 — Best quality' },
    { value: 'gpt-5.4', label: 'GPT-5.4' },
    { value: 'gpt-5.4-nano', label: 'GPT-5.4 nano — Lowest cost' },
];

// Groq AI models
export const groqModels = [
    { value: 'openai/gpt-oss-120b', label: 'GPT-OSS 120B — Recommended' },
    { value: 'openai/gpt-oss-20b', label: 'GPT-OSS 20B — Fastest' },
];

// Google Gemini models
export const geminiModels = [
    { value: 'gemini-3.8-flash', label: 'Gemini 3.8 Flash — Recommended' },
    { value: 'gemini-3.6-flash', label: 'Gemini 3.6 Flash' },
    { value: 'gemini-3.5-flash-lite', label: 'Gemini 3.5 Flash Lite — Lowest cost' },
];

// Anthropic Claude models
export const claudeModels = [
    { value: 'claude-sonnet-5-5', label: 'Claude Sonnet 5.5 — Recommended' },
    { value: 'claude-haiku-5-5', label: 'Claude Haiku 5.5 — Fastest, lowest cost' },
    { value: 'claude-opus-5-5', label: 'Claude Opus 5.5 — Most capable' },
    { value: 'claude-fable-5-2', label: 'Claude Fable 5.2' },
];

// Mistral AI models ("-latest" aliases always point to the current version)
export const mistralModels = [
    { value: 'mistral-large-latest', label: 'Mistral Large — Best quality' },
    { value: 'mistral-medium-latest', label: 'Mistral Medium — Balanced' },
    { value: 'mistral-small-latest', label: 'Mistral Small — Fast & efficient' },
];

// OpenRouter models (the full live list loads even without a key)
export const openrouterModels = [
    { value: 'openrouter/auto', label: 'Auto — OpenRouter picks a model' },
];

// Together.ai image models (fallback; Settings can load the live list)
export const togetherImageModels = [
    { value: 'black-forest-labs/FLUX.2-pro', label: 'FLUX.2 [pro] — Recommended' },
    { value: 'black-forest-labs/FLUX.2-flex', label: 'FLUX.2 [flex]' },
    { value: 'black-forest-labs/FLUX.2-max', label: 'FLUX.2 [max] — Highest fidelity' },
];

// =========================================================================
// Image Generation Models / Options
// =========================================================================

export const imageProviders = [
    { value: 'openai', label: 'OpenAI GPT Image (uses your OpenAI key)' },
    { value: 'together', label: 'FLUX (Together.ai)' },
];

// Fallback list; Settings can load the live list from OpenAI.
export const openaiImageModels = [
    { value: 'gpt-image-2', label: 'GPT Image 2 — Recommended' },
    { value: 'gpt-image-1.5', label: 'GPT Image 1.5' },
    { value: 'gpt-image-1-mini', label: 'GPT Image 1 mini — Lowest cost' },
];

export const imageSizes = [
    { value: '1024x1024', label: '1024 × 1024 — Square' },
    { value: '1536x1024', label: '1536 × 1024 — Landscape (3:2)' },
    { value: '1024x1536', label: '1024 × 1536 — Portrait (2:3)' },
];

export const imageQualities = [
    { value: 'low', label: 'Low — Fastest, lowest cost' },
    { value: 'medium', label: 'Medium — Balanced' },
    { value: 'high', label: 'High — Most detail, highest cost' },
];

export const imageStylePresets = [
    { value: '', label: 'No specific style' },
    { value: 'photorealistic, high resolution, professional photography', label: 'Photorealistic' },
    { value: 'digital art, concept art, highly detailed illustration', label: 'Digital Art' },
    { value: 'pencil sketch, charcoal drawing, artistic', label: 'Sketch / Drawing' },
    { value: 'oil painting, impressionist, canvas texture', label: 'Oil Painting' },
    { value: 'minimalist, flat design, clean lines, vector art', label: 'Minimalist / Flat' },
    { value: 'watercolor, soft colors, artistic illustration', label: 'Watercolor' },
    { value: 'cinematic, dramatic lighting, movie still', label: 'Cinematic' },
    { value: '3D render, octane render, studio lighting', label: '3D Rendered' },
    { value: 'anime style, manga, Japanese illustration', label: 'Anime / Manga' },
];

// =========================================================================
// Content Tones & Languages
// =========================================================================

export const toneOptions = [
    { value: 'persuasive', label: 'Persuasive' },
    { value: 'professional', label: 'Professional' },
    { value: 'friendly', label: 'Friendly & Casual' },
    { value: 'luxury', label: 'Luxury & Premium' },
    { value: 'minimal', label: 'Minimal & Direct' },
    { value: 'informative', label: 'Informative & Educational' },
    { value: 'humorous', label: 'Humorous & Witty' },
    { value: 'urgent', label: 'Urgent & Action-Oriented' },
];

export const languageOptions = [
    { value: 'English', label: 'English' },
    { value: 'Spanish', label: 'Spanish' },
    { value: 'French', label: 'French' },
    { value: 'German', label: 'German' },
    { value: 'Italian', label: 'Italian' },
    { value: 'Portuguese', label: 'Portuguese' },
    { value: 'Dutch', label: 'Dutch' },
    { value: 'Arabic', label: 'Arabic' },
    { value: 'Hindi', label: 'Hindi' },
    { value: 'Japanese', label: 'Japanese' },
    { value: 'Chinese (Simplified)', label: 'Chinese (Simplified)' },
    { value: 'Korean', label: 'Korean' },
    { value: 'Russian', label: 'Russian' },
    { value: 'Turkish', label: 'Turkish' },
    { value: 'Polish', label: 'Polish' },
];

// =========================================================================
// Provider Meta (for UI display)
// =========================================================================

export const providerMeta = {
    chatgpt: {
        label: 'OpenAI ChatGPT',
        color: '#10a37f',
        description: 'Industry-leading GPT models. Excellent all-rounder for content, SEO, and image generation (GPT Image).',
        keyPlaceholder: 'sk-...your-openai-key',
        docsUrl: 'https://platform.openai.com/api-keys',
        models: chatgptModels,
    },
    groq: {
        label: 'Groq',
        color: '#f55036',
        description: 'Very fast responses from open models such as GPT-OSS. Good for quick drafts.',
        keyPlaceholder: 'gsk-...your-groq-key',
        docsUrl: 'https://console.groq.com/keys',
        models: groqModels,
    },
    gemini: {
        label: 'Google Gemini',
        color: '#4285f4',
        description: 'Google\'s multimodal AI. Excels at long context, reasoning, and multilingual content.',
        keyPlaceholder: 'AIza...your-gemini-key',
        docsUrl: 'https://aistudio.google.com/app/apikey',
        models: geminiModels,
    },
    claude: {
        label: 'Anthropic Claude',
        color: '#d97706',
        description: 'Superior at following complex instructions and producing nuanced, high-quality long-form content.',
        keyPlaceholder: 'sk-ant-...your-claude-key',
        docsUrl: 'https://console.anthropic.com/account/keys',
        models: claudeModels,
    },
    mistral: {
        label: 'Mistral AI',
        color: '#7c3aed',
        description: 'European AI with strong multilingual capabilities and open-source model options.',
        keyPlaceholder: 'your-mistral-api-key',
        docsUrl: 'https://console.mistral.ai/api-keys/',
        models: mistralModels,
    },
    openrouter: {
        label: 'OpenRouter',
        color: '#5b21b6',
        description: 'Access multiple AI models from OpenAI, Anthropic, Google, and Meta through a single API key.',
        keyPlaceholder: 'sk-or-...your-openrouter-key',
        docsUrl: 'https://openrouter.ai/keys',
        models: openrouterModels,
    },
};

// =========================================================================
// Template Types
// =========================================================================

export const templateTypes = [
    { value: 'general', label: 'General' },
    { value: 'product', label: 'WooCommerce Product' },
    { value: 'post', label: 'Blog Post / Article' },
    { value: 'page', label: 'Page' },
    { value: 'seo', label: 'SEO Meta' },
    { value: 'image', label: 'Image Prompt' },
];
