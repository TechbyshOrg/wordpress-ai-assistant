// utils/PromptGenerator.js

class PostPromptGenerator {
  constructor(options = {}) {
    this.tone = options.tone || 'informative';
    this.language = options.language || 'English';
    this.format = options.format || 'plain';
  }

  descriptionPostTitle(postTitle) {
    return `Generate a WordPress Gutenberg block description for the post titled: ${postTitle}. Make it SEO-friendly, ${this.tone}, and suitable for online readers. Do not include any introductions, explanations, or double inverted quotes—return only the block description. Use ${this.format} formatting.`;
  }

  improveDescription(description, postTitle) {
    return `Improve the following block description: ${description} for the post titled ${postTitle}. Make it SEO-friendly, ${this.tone}, and suitable for online readers. Do not include any introductions, explanations, or double inverted quotes—return only the block description. Use ${this.format} formatting.`;
  }

  improveDescriptionCustom(description, postTitle, customPrompt) {
    return `Improve the following block description: ${description} for the post titled ${postTitle} using the prompt: ${customPrompt}. Make it SEO-friendly, ${this.tone}, and suitable for online readers. Do not include any introductions, explanations, or double inverted quotes—return only the block description. Use ${this.format} formatting.`;
  }

  // improveTitle(postTitle) {
  //   return `Improve the post title ${postTitle} to make it SEO-friendly, not too long, ${this.tone}, and suitable for online readers. Do not include any introductions, explanations, or double inverted quotes—return only the improved title as plain text.`;
  // }

  // improveTitleCustom(postTitle, customPrompt) {
  //   return `Improve the post title ${postTitle} using the prompt: ${customPrompt}. Make it SEO-friendly, not too long, ${this.tone}, and suitable for online readers. Do not include any introductions, explanations, or double inverted quotes—return only the improved title as plain text.`;
  // }
}

export default PostPromptGenerator;
