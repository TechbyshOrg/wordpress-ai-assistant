// =========================================================================
// AI Provider Models
// =========================================================================

// OpenAI ChatGPT models
export const chatgptModels = [
    { value: 'gpt-4o', label: 'GPT-4o (Omni) — Best overall' },
    { value: 'gpt-4o-mini', label: 'GPT-4o mini — Fast & affordable' },
    { value: 'o3-mini', label: 'o3-mini — Efficient reasoning' },
    { value: 'o1-mini', label: 'o1-mini — Specialized reasoning' },
    { value: 'o1', label: 'o1 — High-effort reasoning' },
];

// Groq AI models
export const groqModels = [
    { value: 'llama-3.3-70b-versatile', label: 'Llama-3.3 70B Versatile — Recommended' },
    { value: 'llama-3.1-8b-instant', label: 'Llama-3.1 8B Instant — Ultra fast' },
];

// Google Gemini models
export const geminiModels = [
    { value: 'gemini-3.5-flash', label: 'Gemini 3.5 Flash — Recommended' },
    { value: 'gemini-3.1-pro', label: 'Gemini 3.1 Pro — Best quality' },
    { value: 'gemini-3.1-flash-lite', label: 'Gemini 3.1 Flash Lite — Affordable' },
];

// Anthropic Claude models
export const claudeModels = [
    { value: 'claude-sonnet-4-6', label: 'Claude Sonnet 4.6 — Recommended' },
    { value: 'claude-haiku-4-5', label: 'Claude Haiku 4.5 — Fast' },
    { value: 'claude-opus-4-8', label: 'Claude Opus 4.8 — Highly capable' },
    { value: 'claude-fable-5', label: 'Claude Fable 5 — Advanced reasoning' },
];

// Mistral AI models
export const mistralModels = [
    { value: 'mistral-large-latest', label: 'Mistral Large — Best quality' },
    { value: 'mistral-small-latest', label: 'Mistral Small — Fast & efficient' },
    { value: 'codestral-latest', label: 'Codestral — Code specialist' },
];

// OpenRouter models
export const openrouterModels = [
    { value: 'openai/gpt-4o', label: 'GPT-4o (OpenAI)' },
    { value: 'openai/gpt-4o-mini', label: 'GPT-4o mini (OpenAI)' },
    { value: 'anthropic/claude-3.5-sonnet', label: 'Claude 3.5 Sonnet (Anthropic)' },
    { value: 'google/gemini-flash-1.5', label: 'Gemini 1.5 Flash (Google)' },
    { value: 'meta-llama/llama-3.3-70b-instruct', label: 'Llama 3.3 70B (Meta)' },
    { value: 'deepseek/deepseek-chat', label: 'DeepSeek V3 / R1 (DeepSeek)' },
];

// =========================================================================
// Image Generation Models / Options
// =========================================================================

export const imageProviders = [
    { value: 'dalle', label: 'DALL-E 3 (OpenAI)' },
    { value: 'together', label: 'FLUX / Stable Diffusion (Together.ai)' },
];

export const imageSizes = [
    { value: '1024x1024', label: '1024 × 1024 — Square' },
    { value: '1792x1024', label: '1792 × 1024 — Landscape (16:9)' },
    { value: '1024x1792', label: '1024 × 1792 — Portrait (9:16)' },
];

export const imageQualities = [
    { value: 'standard', label: 'Standard' },
    { value: 'hd', label: 'HD — More detail (higher cost)' },
];

export const imageStyles = [
    { value: 'vivid', label: 'Vivid — Hyper-real, dramatic' },
    { value: 'natural', label: 'Natural — Subtle, realistic' },
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
        description: 'Industry-leading GPT models. Excellent all-rounder for content, SEO, and image generation (DALL-E 3).',
        keyPlaceholder: 'sk-...your-openai-key',
        docsUrl: 'https://platform.openai.com/api-keys',
        models: chatgptModels,
    },
    groq: {
        label: 'Groq',
        color: '#f55036',
        description: 'Lightning-fast inference with open-source models like Llama 3.3. Great free tier for getting started.',
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
