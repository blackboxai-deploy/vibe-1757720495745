/**
 * WordPress Integration Utilities
 * For AI Content Replacer Pro Plugin
 */

// WordPress compatibility checker
export class WordPressCompatibility {
  /**
   * Check WordPress version compatibility
   */
  static checkWordPressVersion(currentVersion: string): {
    compatible: boolean;
    minVersion: string;
    recommendations: string[];
  } {
    const minSupportedVersion = '5.0.0';
    const recommendedVersion = '6.0.0';
    
    const isCompatible = this.compareVersions(currentVersion, minSupportedVersion) >= 0;
    const isRecommended = this.compareVersions(currentVersion, recommendedVersion) >= 0;
    
    const recommendations: string[] = [];
    
    if (!isCompatible) {
      recommendations.push(`WordPress ${minSupportedVersion} or higher is required`);
      recommendations.push('Please update your WordPress installation');
    } else if (!isRecommended) {
      recommendations.push(`WordPress ${recommendedVersion} or higher is recommended for optimal performance`);
      recommendations.push('Consider updating WordPress for better security and features');
    }
    
    return {
      compatible: isCompatible,
      minVersion: minSupportedVersion,
      recommendations
    };
  }

  /**
   * Compare version strings
   */
  private static compareVersions(version1: string, version2: string): number {
    const parts1 = version1.split('.').map(Number);
    const parts2 = version2.split('.').map(Number);
    
    const maxLength = Math.max(parts1.length, parts2.length);
    
    for (let i = 0; i < maxLength; i++) {
      const part1 = parts1[i] || 0;
      const part2 = parts2[i] || 0;
      
      if (part1 > part2) return 1;
      if (part1 < part2) return -1;
    }
    
    return 0;
  }

  /**
   * Check PHP version compatibility
   */
  static checkPHPVersion(currentVersion: string): {
    compatible: boolean;
    minVersion: string;
    recommendations: string[];
  } {
    const minSupportedVersion = '7.4.0';
    const recommendedVersion = '8.0.0';
    
    const isCompatible = this.compareVersions(currentVersion, minSupportedVersion) >= 0;
    const isRecommended = this.compareVersions(currentVersion, recommendedVersion) >= 0;
    
    const recommendations: string[] = [];
    
    if (!isCompatible) {
      recommendations.push(`PHP ${minSupportedVersion} or higher is required`);
      recommendations.push('Contact your hosting provider to upgrade PHP');
    } else if (!isRecommended) {
      recommendations.push(`PHP ${recommendedVersion} or higher is recommended`);
      recommendations.push('Consider upgrading PHP for better performance and security');
    }
    
    return {
      compatible: isCompatible,
      minVersion: minSupportedVersion,
      recommendations
    };
  }
}

// Page builder integration utilities
export class PageBuilderIntegration {
  private static supportedBuilders = [
    'elementor',
    'gutenberg', 
    'beaver-builder',
    'divi',
    'visual-composer',
    'oxygen',
    'bricks',
    'cornerstone'
  ];

  /**
   * Detect active page builders
   */
  static detectActiveBuilders(): {
    detected: string[];
    supported: string[];
    compatibility: Record<string, boolean>;
  } {
    // In actual WordPress environment, this would check active plugins
    const detected = ['gutenberg', 'elementor']; // Mock data
    
    const compatibility: Record<string, boolean> = {};
    detected.forEach(builder => {
      compatibility[builder] = this.supportedBuilders.includes(builder);
    });
    
    return {
      detected,
      supported: this.supportedBuilders,
      compatibility
    };
  }

  /**
   * Parse page builder content
   */
  static parseBuilderContent(content: string, builderType: string): {
    textContent: string[];
    preservedStructure: any;
    replacementMap: Map<string, string>;
  } {
    const textContent: string[] = [];
    const replacementMap = new Map<string, string>();
    
    switch (builderType) {
      case 'elementor':
        return this.parseElementorContent(content);
      case 'gutenberg':
        return this.parseGutenbergContent(content);
      case 'beaver-builder':
        return this.parseBeaverBuilderContent(content);
      default:
        return this.parseGenericContent(content);
    }
  }

  /**
   * Parse Elementor content
   */
  private static parseElementorContent(content: string): {
    textContent: string[];
    preservedStructure: any;
    replacementMap: Map<string, string>;
  } {
    const textContent: string[] = [];
    const replacementMap = new Map<string, string>();
    
    try {
      // Elementor stores content as JSON
      const elementorData = JSON.parse(content);
      this.extractElementorText(elementorData, textContent, replacementMap);
      
      return {
        textContent,
        preservedStructure: elementorData,
        replacementMap
      };
    } catch {
      // Fallback to generic parsing
      return this.parseGenericContent(content);
    }
  }

  /**
   * Extract text from Elementor JSON structure
   */
  private static extractElementorText(data: any, textContent: string[], replacementMap: Map<string, string>): void {
    if (Array.isArray(data)) {
      data.forEach(item => this.extractElementorText(item, textContent, replacementMap));
    } else if (data && typeof data === 'object') {
      // Look for text content in common Elementor fields
      const textFields = ['title', 'content', 'text', 'description', 'caption'];
      
      textFields.forEach(field => {
        if (data[field] && typeof data[field] === 'string' && data[field].length > 0) {
          const placeholder = `__PLACEHOLDER_${textContent.length}__`;
          textContent.push(data[field]);
          replacementMap.set(placeholder, data[field]);
          data[field] = placeholder; // Temporarily replace with placeholder
        }
      });
      
      // Recursively process nested objects
      Object.values(data).forEach(value => {
        if (value && (typeof value === 'object' || Array.isArray(value))) {
          this.extractElementorText(value, textContent, replacementMap);
        }
      });
    }
  }

  /**
   * Parse Gutenberg content
   */
  private static parseGutenbergContent(content: string): {
    textContent: string[];
    preservedStructure: any;
    replacementMap: Map<string, string>;
  } {
    const textContent: string[] = [];
    const replacementMap = new Map<string, string>();
    
    // Extract text from Gutenberg blocks while preserving structure
    const blockRegex = /<!-- wp:([a-z]+\/)?([a-z-]+)(\s+({.*?}))? -->(.*?)<!-- \/wp:\1?\2 -->/g;
    let preservedContent = content;
    
    let match;
    while ((match = blockRegex.exec(content)) !== null) {
      const blockContent = match[5];
      if (blockContent && blockContent.trim().length > 0) {
        // Extract plain text from HTML
        const textOnly = blockContent.replace(/<[^>]+>/g, '').trim();
        if (textOnly.length > 0) {
          const placeholder = `__PLACEHOLDER_${textContent.length}__`;
          textContent.push(textOnly);
          replacementMap.set(placeholder, textOnly);
          preservedContent = preservedContent.replace(textOnly, placeholder);
        }
      }
    }
    
    return {
      textContent,
      preservedStructure: { originalContent: content, processedContent: preservedContent },
      replacementMap
    };
  }

  /**
   * Parse Beaver Builder content
   */
  private static parseBeaverBuilderContent(content: string): {
    textContent: string[];
    preservedStructure: any;
    replacementMap: Map<string, string>;
  } {
    const textContent: string[] = [];
    const replacementMap = new Map<string, string>();
    
    try {
      // Beaver Builder uses serialized PHP data, convert to object for processing
      const builderData = this.unserializePHP(content);
      this.extractBeaverBuilderText(builderData, textContent, replacementMap);
      
      return {
        textContent,
        preservedStructure: builderData,
        replacementMap
      };
    } catch {
      return this.parseGenericContent(content);
    }
  }

  /**
   * Basic PHP unserialization (simplified for demo)
   */
  private static unserializePHP(serialized: string): any {
    // This is a simplified implementation
    // In production, use proper PHP unserialization library
    try {
      return JSON.parse(serialized.replace(/;$/, ''));
    } catch {
      return {};
    }
  }

  /**
   * Extract text from Beaver Builder structure
   */
  private static extractBeaverBuilderText(data: any, textContent: string[], replacementMap: Map<string, string>): void {
    if (Array.isArray(data)) {
      data.forEach(item => this.extractBeaverBuilderText(item, textContent, replacementMap));
    } else if (data && typeof data === 'object') {
      // Common Beaver Builder text fields
      const textFields = ['text', 'title', 'content', 'heading'];
      
      textFields.forEach(field => {
        if (data[field] && typeof data[field] === 'string' && data[field].length > 0) {
          const placeholder = `__PLACEHOLDER_${textContent.length}__`;
          textContent.push(data[field]);
          replacementMap.set(placeholder, data[field]);
          data[field] = placeholder;
        }
      });
      
      Object.values(data).forEach(value => {
        if (value && (typeof value === 'object' || Array.isArray(value))) {
          this.extractBeaverBuilderText(value, textContent, replacementMap);
        }
      });
    }
  }

  /**
   * Parse generic content
   */
  private static parseGenericContent(content: string): {
    textContent: string[];
    preservedStructure: any;
    replacementMap: Map<string, string>;
  } {
    const textContent: string[] = [];
    const replacementMap = new Map<string, string>();
    
    // Extract text from HTML while preserving structure
    const textNodes = content.match(/>[^<]+</g) || [];
    
    textNodes.forEach(node => {
      const text = node.slice(1, -1).trim();
      if (text.length > 0 && !text.match(/^\s*$/)) {
        const placeholder = `__PLACEHOLDER_${textContent.length}__`;
        textContent.push(text);
        replacementMap.set(placeholder, text);
      }
    });
    
    return {
      textContent,
      preservedStructure: { originalContent: content },
      replacementMap
    };
  }

  /**
   * Rebuild content with replaced text
   */
  static rebuildContent(
    preservedStructure: any, 
    replacementMap: Map<string, string>, 
    newTextContent: string[], 
    builderType: string
  ): string {
    let rebuiltContent: string;
    
    switch (builderType) {
      case 'elementor':
        rebuiltContent = this.rebuildElementorContent(preservedStructure, replacementMap, newTextContent);
        break;
      case 'gutenberg':
        rebuiltContent = this.rebuildGutenbergContent(preservedStructure, replacementMap, newTextContent);
        break;
      case 'beaver-builder':
        rebuiltContent = this.rebuildBeaverBuilderContent(preservedStructure, replacementMap, newTextContent);
        break;
      default:
        rebuiltContent = this.rebuildGenericContent(preservedStructure, replacementMap, newTextContent);
        break;
    }
    
    return rebuiltContent;
  }

  /**
   * Rebuild Elementor content
   */
  private static rebuildElementorContent(
    structure: any, 
    replacementMap: Map<string, string>, 
    newTextContent: string[]
  ): string {
    // Replace placeholders with new content
    let index = 0;
    this.replaceElementorPlaceholders(structure, () => {
      return index < newTextContent.length ? newTextContent[index++] : '';
    });
    
    return JSON.stringify(structure);
  }

  /**
   * Replace placeholders in Elementor structure
   */
  private static replaceElementorPlaceholders(data: any, getNewText: () => string): void {
    if (Array.isArray(data)) {
      data.forEach(item => this.replaceElementorPlaceholders(item, getNewText));
    } else if (data && typeof data === 'object') {
      Object.keys(data).forEach(key => {
        if (typeof data[key] === 'string' && data[key].startsWith('__PLACEHOLDER_')) {
          data[key] = getNewText();
        } else if (data[key] && (typeof data[key] === 'object' || Array.isArray(data[key]))) {
          this.replaceElementorPlaceholders(data[key], getNewText);
        }
      });
    }
  }

  /**
   * Rebuild Gutenberg content
   */
  private static rebuildGutenbergContent(
    structure: any, 
    replacementMap: Map<string, string>, 
    newTextContent: string[]
  ): string {
    let rebuiltContent = structure.processedContent || structure.originalContent;
    
    let index = 0;
    replacementMap.forEach((originalText, placeholder) => {
      if (index < newTextContent.length) {
        rebuiltContent = rebuiltContent.replace(placeholder, newTextContent[index++]);
      }
    });
    
    return rebuiltContent;
  }

  /**
   * Rebuild Beaver Builder content
   */
  private static rebuildBeaverBuilderContent(
    structure: any, 
    replacementMap: Map<string, string>, 
    newTextContent: string[]
  ): string {
    let index = 0;
    this.replaceElementorPlaceholders(structure, () => {
      return index < newTextContent.length ? newTextContent[index++] : '';
    });
    
    // In production, this would serialize back to PHP format
    return JSON.stringify(structure);
  }

  /**
   * Rebuild generic content
   */
  private static rebuildGenericContent(
    structure: any, 
    replacementMap: Map<string, string>, 
    newTextContent: string[]
  ): string {
    let rebuiltContent = structure.originalContent;
    
    let index = 0;
    replacementMap.forEach((originalText, placeholder) => {
      if (index < newTextContent.length) {
        rebuiltContent = rebuiltContent.replace(originalText, newTextContent[index++]);
      }
    });
    
    return rebuiltContent;
  }
}

// SEO plugin integration
export class SEOIntegration {
  private static supportedSeoPlugins = [
    'yoast-seo',
    'rankmath',
    'all-in-one-seo',
    'seo-framework'
  ];

  /**
   * Detect active SEO plugins
   */
  static detectActiveSEOPlugins(): {
    detected: string[];
    supported: string[];
    compatibility: Record<string, boolean>;
  } {
    // Mock detection - in WordPress, this would check active plugins
    const detected = ['yoast-seo'];
    
    const compatibility: Record<string, boolean> = {};
    detected.forEach(plugin => {
      compatibility[plugin] = this.supportedSeoPlugins.includes(plugin);
    });
    
    return {
      detected,
      supported: this.supportedSeoPlugins,
      compatibility
    };
  }

  /**
   * Extract SEO metadata
   */
  static extractSEOData(postId: number): {
    title?: string;
    description?: string;
    keywords?: string[];
    focusKeyword?: string;
  } {
    // In WordPress, this would query post meta
    return {
      title: 'Sample SEO Title',
      description: 'Sample meta description',
      keywords: ['keyword1', 'keyword2'],
      focusKeyword: 'main keyword'
    };
  }

  /**
   * Update SEO metadata
   */
  static updateSEOData(postId: number, seoData: {
    title?: string;
    description?: string;
    keywords?: string[];
    focusKeyword?: string;
  }): boolean {
    // In WordPress, this would update post meta
    console.log(`Updating SEO data for post ${postId}:`, seoData);
    return true;
  }

  /**
   * Generate SEO-optimized content
   */
  static optimizeContentForSEO(content: string, focusKeyword: string, businessProfile: any): {
    optimizedContent: string;
    seoScore: number;
    recommendations: string[];
  } {
    let optimizedContent = content;
    const recommendations: string[] = [];
    let seoScore = 100;

    // Keyword density check
    const keywordCount = (content.toLowerCase().match(new RegExp(focusKeyword.toLowerCase(), 'g')) || []).length;
    const wordCount = content.split(/\s+/).length;
    const keywordDensity = (keywordCount / wordCount) * 100;

    if (keywordDensity < 0.5) {
      recommendations.push('Consider adding the focus keyword more frequently (target: 0.5-2.5%)');
      seoScore -= 10;
    } else if (keywordDensity > 3) {
      recommendations.push('Reduce keyword density to avoid over-optimization (target: 0.5-2.5%)');
      seoScore -= 15;
    }

    // Content length check
    if (wordCount < 300) {
      recommendations.push('Content is quite short. Consider adding more detailed information.');
      seoScore -= 20;
    }

    // Business-specific optimization
    if (businessProfile) {
      // Add business location for local SEO
      if (businessProfile.location && !content.toLowerCase().includes(businessProfile.location.toLowerCase())) {
        optimizedContent += ` Located in ${businessProfile.location}.`;
        recommendations.push('Added location information for local SEO');
      }

      // Add business keywords
      if (businessProfile.keywords && Array.isArray(businessProfile.keywords)) {
         const missingKeywords = businessProfile.keywords.filter(
          (keyword: string) => !content.toLowerCase().includes(keyword.toLowerCase())
        );
        
        if (missingKeywords.length > 0) {
          recommendations.push(`Consider incorporating these business keywords: ${missingKeywords.slice(0, 3).join(', ')}`);
        }
      }
    }

    return {
      optimizedContent,
      seoScore,
      recommendations
    };
  }
}

// Theme compatibility checker
export class ThemeCompatibility {
  /**
   * Check theme compatibility
   */
  static checkThemeCompatibility(themeName: string): {
    compatible: boolean;
    recommendations: string[];
    potentialIssues: string[];
  } {
    const knownIssues: Record<string, string[]> = {
      'twentytwentyone': [],
      'astra': [],
      'generatepress': [],
      'oceanwp': [],
      'customtheme': ['Custom themes may require additional testing', 'Check for custom post type compatibility']
    };

    const compatible = !['problematic-theme'].includes(themeName.toLowerCase());
    const potentialIssues = knownIssues[themeName.toLowerCase()] || [];
    
    const recommendations: string[] = [];
    
    if (!compatible) {
      recommendations.push('This theme is known to have compatibility issues');
      recommendations.push('Consider switching to a supported theme');
    }
    
    if (potentialIssues.length > 0) {
      recommendations.push('Test thoroughly with this theme');
    }

    return {
      compatible,
      recommendations,
      potentialIssues
    };
  }
}

export default {
  WordPressCompatibility,
  PageBuilderIntegration,
  SEOIntegration,
  ThemeCompatibility
};