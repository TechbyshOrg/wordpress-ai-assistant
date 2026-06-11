/**
 * PostPromptGenerator — WordPress AI Content Prompt Builder (Gutenberg)
 * Generates structured prompts for all content types.
 */

class PostPromptGenerator {
    constructor(options = {}) {
        this.tone     = options.tone     || 'persuasive';
        this.language = options.language || 'English';
        this.format   = options.format   || 'HTML';
    }

    // =========================================================================
    // Gutenberg Post Prompts
    // =========================================================================

    descriptionPostTitle(postTitle) {
        return `Write an engaging blog post introduction for: "${postTitle}".
Write in ${this.language}. Tone: ${this.tone}.
Hook the reader, explain what they'll learn, and encourage them to keep reading.
Use ${this.format} formatting.
Return only the introduction — no meta commentary.`;
    }

    improveDescription(description, postTitle) {
        return `Rewrite and improve the following paragraph content for "${postTitle}":

"${description}"

Write in ${this.language}. Tone: ${this.tone}.
Keep the core facts, but improve clarity, SEO-friendliness, and engagement.
Use ${this.format} formatting.
Return only the improved content.`;
    }

    improveDescriptionCustom(description, postTitle, customPrompt) {
        return `Improve this paragraph content for "${postTitle}" following these instructions: "${customPrompt}"

Original content:
"${description}"

Write in ${this.language}. Tone: ${this.tone}.
Use ${this.format} formatting. Return only the improved content.`;
    }

    blogOutline(postTitle) {
        return `Create a detailed blog post outline for: "${postTitle}".
Write in ${this.language}. Tone: ${this.tone}.
Include: H2 headings and 2-3 bullet points per section. Target 1500-2000 words total.
Use HTML formatting (h2, ul, li).
Return only the outline — no commentary.`;
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
}

export default PostPromptGenerator;
