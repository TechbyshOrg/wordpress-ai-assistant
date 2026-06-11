/**
 * PromptGenerator — WordPress AI Content Prompt Builder
 * Generates structured prompts for all content types.
 */

class ProductPromptGenerator {
    constructor(options = {}) {
        this.tone     = options.tone     || 'persuasive';
        this.language = options.language || 'English';
        this.format   = options.format   || 'HTML';
    }

    // =========================================================================
    // WooCommerce Product Prompts
    // =========================================================================

    productNameDescription(productName) {
        return `Generate a WooCommerce-ready product description for: "${productName}".
Write in ${this.language}. Tone: ${this.tone}.
Include key benefits, features, and a compelling call to action.
Use ${this.format} formatting with bullet points.
Return only the description — no introductions, explanations, or additional commentary.`;
    }

    productShortDescription(productName, keywords = '') {
        const kwHint = keywords ? ` Keywords to include: ${keywords}.` : '';
        return `Write a short WooCommerce product summary (2-3 sentences, max 100 words) for: "${productName}".
Write in ${this.language}. Tone: ${this.tone}.${kwHint}
Return only the short description as plain text. No HTML needed.`;
    }

    improveDescription(description, productName) {
        return `Rewrite and improve the following WooCommerce product description for "${productName}":

"${description}"

Write in ${this.language}. Tone: ${this.tone}.
Keep the core facts, but improve clarity, SEO-friendliness, and engagement.
Use ${this.format} formatting with bullet points.
Return only the improved description.`;
    }

    improveDescriptionCustom(description, productName, customPrompt) {
        return `Improve this product description for "${productName}" following these instructions: "${customPrompt}"

Original description:
"${description}"

Write in ${this.language}. Tone: ${this.tone}.
Use ${this.format} formatting. Return only the improved description.`;
    }

    improveTitle(productName) {
        return `Improve this product title for an online store: "${productName}"
Write in ${this.language}. Tone: ${this.tone}.
Make it SEO-friendly, scannable, and under 70 characters.
Return only the improved title as plain text — no quotes, no explanation.`;
    }

    improveTitleCustom(productName, customPrompt) {
        return `Improve this product title: "${productName}" following these instructions: "${customPrompt}"
Write in ${this.language}. Tone: ${this.tone}.
Make it SEO-friendly, under 70 characters.
Return only the improved title as plain text — no quotes, no explanation.`;
    }

    productTags(productName, description = '') {
        const descHint = description ? `\nProduct description: "${description.substring(0, 500)}"` : '';
        return `Generate 8-12 relevant product tags for "${productName}".${descHint}
Write in ${this.language}.
Return only a comma-separated list of tags. No numbers, no explanation, no extra text.
Example format: tag one, tag two, tag three`;
    }

    // =========================================================================
    // SEO Meta Prompts
    // =========================================================================

    seoMetaTitle(postTitle, keywords = '') {
        const kwHint = keywords ? ` Primary keyword: "${keywords}".` : '';
        return `Write an SEO meta title for: "${postTitle}".${kwHint}
Write in ${this.language}.
Requirements: Under 60 characters. Include primary keyword naturally. Compelling for click-throughs.
Return only the meta title as plain text — no quotes, no explanation.`;
    }

    seoMetaDescription(postTitle, content = '', keywords = '') {
        const kwHint = keywords ? ` Primary keyword: "${keywords}".` : '';
        const contentHint = content ? `\nContent preview: "${content.substring(0, 300)}"` : '';
        return `Write an SEO meta description for a page titled: "${postTitle}".${kwHint}${contentHint}
Write in ${this.language}. Tone: ${this.tone}.
Requirements: 140-160 characters. Include primary keyword. End with a call to action.
Return only the meta description as plain text — no quotes, no explanation.`;
    }

    seoFocusKeywords(postTitle, content = '') {
        const contentHint = content ? `\nContent: "${content.substring(0, 500)}"` : '';
        return `Suggest 3-5 focus keywords for a page titled: "${postTitle}".${contentHint}
Write in ${this.language}.
Return only a comma-separated list of keywords. No explanation.`;
    }

    // =========================================================================
    // Blog Post / Page Content Prompts
    // =========================================================================

    descriptionPostTitle(postTitle) {
        return `Write an engaging blog post introduction for: "${postTitle}".
Write in ${this.language}. Tone: ${this.tone}.
Hook the reader, explain what they'll learn, and encourage them to keep reading.
Use ${this.format} formatting.
Return only the introduction — no meta commentary.`;
    }

    blogIntro(postTitle) {
        return this.descriptionPostTitle(postTitle);
    }

    blogOutline(postTitle) {
        return `Create a detailed blog post outline for: "${postTitle}".
Write in ${this.language}. Tone: ${this.tone}.
Include: H2 headings and 2-3 bullet points per section. Target 1500-2000 words total.
Use HTML formatting (h2, ul, li).
Return only the outline — no commentary.`;
    }

    // =========================================================================
    // Image Generation Prompts
    // =========================================================================

    imageGenerationPrompt(subject, style = '', context = '') {
        let prompt = subject;
        if (style) {
            prompt += `, ${style}`;
        }
        if (context) {
            prompt += `. Context: ${context}`;
        }
        return prompt;
    }

    enhanceImagePrompt(rawPrompt) {
        return `Enhance this image generation prompt to make it more detailed, vivid, and specific: "${rawPrompt}"
Return only the enhanced prompt as a single sentence or paragraph.
No explanation, no labels — just the improved prompt text.`;
    }

    // =========================================================================
    // Alt Text Prompts
    // =========================================================================

    altText(imageDescription, context = '') {
        const ctxHint = context ? ` The image is used on a page about: "${context}".` : '';
        return `Write a concise, descriptive alt text for an image described as: "${imageDescription}".${ctxHint}
Write in ${this.language}.
Requirements: Under 125 characters. Descriptive. SEO-friendly. Start with the main subject.
Return only the alt text as plain text — no quotes, no labels.`;
    }

    // =========================================================================
    // Chat Prompts
    // =========================================================================

    chatSystemPrompt(siteContext = '') {
        return siteContext
            ? `You are an AI writing assistant for a WordPress website: "${siteContext}". Help the user with content creation, SEO, and writing tasks. Be concise and helpful.`
            : 'You are an AI writing assistant for WordPress. Help the user with content creation, SEO, and writing tasks. Be concise and helpful.';
    }
}

export default ProductPromptGenerator;
