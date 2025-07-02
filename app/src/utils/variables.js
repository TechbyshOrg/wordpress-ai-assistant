// OpenAI ChatGPT models (via API & ChatGPT interface)
export const chatgptModels = [
    { value: 'gpt-4o', label: 'GPT-4o (Omni)' },
    { value: 'gpt-4o-mini', label: 'GPT-4o mini' },
    { value: 'gpt-4.5', label: 'GPT-4.5 (Orion)' },
    { value: 'gpt-4.1', label: 'GPT-4.1' },
    { value: 'gpt-4.1-mini', label: 'GPT-4.1 mini' },
    { value: 'gpt-4.1-nano', label: 'GPT-4.1 nano' },
    { value: 'o3', label: 'o3 (full reasoning)' },
    { value: 'o3-mini', label: 'o3-mini' },
    { value: 'o3-mini-high', label: 'o3-mini-high' },
    { value: 'o3-pro', label: 'o3-pro' },
    { value: 'o4-mini', label: 'o4-mini' },
    { value: 'o4-mini-high', label: 'o4-mini-high' },
    { value: 'o3-deep-research', label: 'o3-deep-research' },
    { value: 'o4-mini-deep-research', label: 'o4-mini-deep-research' }
];

// Groq AI models (latest public offerings)
export const groqModels = [
    { value: 'llama3-8b-8192', label: 'Llama-3 8B (8K context)' },
    { value: 'llama3-70b-8192', label: 'Llama-3 70B (8K context)' },
    { value: 'llama-3.1-8b-instant', label: 'Llama-3.1 8B Instant (128K ctx)' },
    { value: 'llama-3.1-70b-versatile', label: 'Llama-3.1 70B Versatile (128K ctx)' },
    { value: 'llama-3.2-1b-preview', label: 'Llama-3.2 1B Preview (128K ctx)' },
    { value: 'llama-3.2-3b-preview', label: 'Llama-3.2 3B Preview (128K ctx)' },
    { value: 'llama-3.2-11b-text-preview', label: 'Llama-3.2 11B Text Preview (128K ctx)' },
    { value: 'llama-3.2-90b-text-preview', label: 'Llama-3.2 90B Text Preview (128K ctx)' },
    { value: 'llama3-groq-8b-8192-tool-use-preview', label: 'Llama-3 Groq 8B Tool-Use (preview)' },
    { value: 'llama3-groq-70b-8192-tool-use-preview', label: 'Llama-3 Groq 70B Tool-Use (preview)' }
];
