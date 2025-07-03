// utils/PromptGenerator.js

class ProductPromptGenerator {
  constructor(options = {}) {
    this.tone = options.tone || 'persuasive';
    this.language = options.language || 'English';
    this.format = options.format || 'HTML';
  }

  productNameDescription(productName) {
    return `Generate a WooCommerce-ready product description for the product: "${productName}". Make it SEO-friendly, ${this.tone}, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use ${this.format} formatting.`;
  }

  improveDescription(description, productName) {
    return `Improve the following product description: "${description}" to a WooCommerce-ready product description for the product "${productName}". Make it SEO-friendly, ${this.tone}, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use ${this.format} formatting.`;
  }

  improveDescriptionCustom(description, productName, customPrompt) {
    return `Improve the following product description: "${description}" to a WooCommerce-ready product description for the product "${productName}" using the prompt: "${customPrompt}". Make it SEO-friendly, ${this.tone}, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use ${this.format} formatting.`;
  }

  improveTitle(productName) {
    return `Improve the product title ${productName} to make it SEO-friendly, not too long, ${this.tone}, and suitable for online shoppers. Avoid introductions, explanations, or any inverted commas. Return the improved title only as plain text.`;
  }

  improveTitleCustom(productName, customPrompt) {
    return `Improve the product title ${productName} using the prompt: "${customPrompt}". Make it SEO-friendly, not too long, ${this.tone}, and suitable for online shoppers. Avoid introductions, explanations, or any inverted commas. Return the improved title only as plain text.`;
  }
}

export default ProductPromptGenerator;
