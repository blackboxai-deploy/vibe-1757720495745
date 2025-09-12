/**
 * Comprehensive Error Handling System
 * For AI Content Replacer Pro - WordPress Plugin
 */

// Error types definition
export enum ErrorType {
  VALIDATION = 'VALIDATION',
  API = 'API',
  NETWORK = 'NETWORK',
  SECURITY = 'SECURITY',
  PERMISSION = 'PERMISSION',
  RATE_LIMIT = 'RATE_LIMIT',
  CONTENT = 'CONTENT',
  DATABASE = 'DATABASE',
  SYSTEM = 'SYSTEM'
}

export enum ErrorSeverity {
  LOW = 'LOW',
  MEDIUM = 'MEDIUM',
  HIGH = 'HIGH',
  CRITICAL = 'CRITICAL'
}

// Custom error class
export class PluginError extends Error {
  public readonly type: ErrorType;
  public readonly severity: ErrorSeverity;
  public readonly code: string;
  public readonly timestamp: number;
  public readonly context?: any;
  public readonly userMessage: string;
  public readonly suggestions: string[];

  constructor(
    message: string,
    type: ErrorType,
    severity: ErrorSeverity,
    code: string,
    userMessage: string,
    suggestions: string[] = [],
    context?: any
  ) {
    super(message);
    this.name = 'PluginError';
    this.type = type;
    this.severity = severity;
    this.code = code;
    this.timestamp = Date.now();
    this.userMessage = userMessage;
    this.suggestions = suggestions;
    this.context = context;
  }
}

// Error handler class
export class ErrorHandler {
  private static errorLog: PluginError[] = [];
  private static maxLogSize = 1000;

  /**
   * Handle and log errors
   */
  static handle(error: PluginError | Error): PluginError {
    let pluginError: PluginError;

    if (error instanceof PluginError) {
      pluginError = error;
    } else {
      // Convert generic error to plugin error
      pluginError = new PluginError(
        error.message || 'Unknown error occurred',
        ErrorType.SYSTEM,
        ErrorSeverity.MEDIUM,
        'SYS001',
        'An unexpected error occurred. Please try again.',
        ['Check your internet connection', 'Refresh the page', 'Contact support if the issue persists']
      );
    }

    // Log the error
    this.logError(pluginError);

    // Report critical errors
    if (pluginError.severity === ErrorSeverity.CRITICAL) {
      this.reportCriticalError(pluginError);
    }

    return pluginError;
  }

  /**
   * Log error to internal storage
   */
  private static logError(error: PluginError): void {
    this.errorLog.unshift(error);

    // Maintain log size limit
    if (this.errorLog.length > this.maxLogSize) {
      this.errorLog = this.errorLog.slice(0, this.maxLogSize);
    }

    // Console logging based on severity
    switch (error.severity) {
      case ErrorSeverity.CRITICAL:
        console.error('CRITICAL ERROR:', error);
        break;
      case ErrorSeverity.HIGH:
        console.error('HIGH SEVERITY ERROR:', error);
        break;
      case ErrorSeverity.MEDIUM:
        console.warn('MEDIUM SEVERITY ERROR:', error);
        break;
      case ErrorSeverity.LOW:
        console.info('LOW SEVERITY ERROR:', error);
        break;
    }
  }

  /**
   * Report critical errors for immediate attention
   */
  private static reportCriticalError(error: PluginError): void {
    // In production, this would send to error tracking service
    console.error('CRITICAL ERROR REPORTED:', {
      timestamp: new Date(error.timestamp).toISOString(),
      type: error.type,
      code: error.code,
      message: error.message,
      context: error.context
    });
  }

  /**
   * Get error statistics
   */
  static getErrorStats(): {
    total: number;
    byType: Record<string, number>;
    bySeverity: Record<string, number>;
    recent: PluginError[];
  } {
    const stats = {
      total: this.errorLog.length,
      byType: {} as Record<string, number>,
      bySeverity: {} as Record<string, number>,
      recent: this.errorLog.slice(0, 10)
    };

    this.errorLog.forEach(error => {
      stats.byType[error.type] = (stats.byType[error.type] || 0) + 1;
      stats.bySeverity[error.severity] = (stats.bySeverity[error.severity] || 0) + 1;
    });

    return stats;
  }

  /**
   * Clear error log
   */
  static clearErrorLog(): void {
    this.errorLog = [];
  }
}

// Specific error creators
export class ErrorFactory {
  static createValidationError(field: string, value: any, expectedFormat: string): PluginError {
    return new PluginError(
      `Validation failed for field "${field}": expected ${expectedFormat}, got ${typeof value}`,
      ErrorType.VALIDATION,
      ErrorSeverity.MEDIUM,
      'VAL001',
      `Please check the ${field} field and ensure it meets the required format.`,
      [
        `Verify that ${field} is not empty`,
        `Check the format requirements for ${field}`,
        'Clear the field and try again'
      ],
      { field, value, expectedFormat }
    );
  }

  static createApiError(provider: string, statusCode: number, message: string): PluginError {
    const suggestions = [
      'Check your API key is valid and has sufficient quota',
      'Verify your internet connection',
      'Try again in a few minutes'
    ];

    if (statusCode === 401) {
      suggestions.unshift('Your API key appears to be invalid or expired');
    } else if (statusCode === 429) {
      suggestions.unshift('API rate limit reached - please wait before trying again');
    }

    return new PluginError(
      `API Error from ${provider}: ${message} (Status: ${statusCode})`,
      ErrorType.API,
      statusCode >= 500 ? ErrorSeverity.HIGH : ErrorSeverity.MEDIUM,
      `API${String(statusCode).padStart(3, '0')}`,
      `Failed to connect to ${provider} AI service. ${message}`,
      suggestions,
      { provider, statusCode }
    );
  }

  static createNetworkError(url: string, timeout?: boolean): PluginError {
    return new PluginError(
      `Network error accessing ${url}${timeout ? ' (timeout)' : ''}`,
      ErrorType.NETWORK,
      ErrorSeverity.MEDIUM,
      timeout ? 'NET002' : 'NET001',
      'Unable to connect to the AI service. Please check your internet connection.',
      [
        'Check your internet connection',
        'Try again in a few moments',
        'Contact your network administrator if the problem persists'
      ],
      { url, timeout }
    );
  }

  static createSecurityError(reason: string, severity: ErrorSeverity = ErrorSeverity.HIGH): PluginError {
    return new PluginError(
      `Security violation: ${reason}`,
      ErrorType.SECURITY,
      severity,
      'SEC001',
      'A security issue was detected. Access has been restricted.',
      [
        'Ensure you have proper permissions',
        'Check for any suspicious activity',
        'Contact administrator if you believe this is an error'
      ],
      { reason }
    );
  }

  static createPermissionError(action: string, requiredRole: string): PluginError {
    return new PluginError(
      `Insufficient permissions to perform "${action}". Required role: ${requiredRole}`,
      ErrorType.PERMISSION,
      ErrorSeverity.MEDIUM,
      'PERM001',
      'You do not have permission to perform this action.',
      [
        `Ensure you have "${requiredRole}" permissions`,
        'Contact your administrator to request access',
        'Log out and log back in to refresh permissions'
      ],
      { action, requiredRole }
    );
  }

  static createRateLimitError(limit: number, resetTime: number): PluginError {
    const resetDate = new Date(resetTime);
    return new PluginError(
      `Rate limit exceeded: ${limit} requests per hour`,
      ErrorType.RATE_LIMIT,
      ErrorSeverity.MEDIUM,
      'RATE001',
      'You have exceeded the rate limit for API requests.',
      [
        `Wait until ${resetDate.toLocaleTimeString()} before trying again`,
        'Consider upgrading your plan for higher limits',
        'Reduce the frequency of your requests'
      ],
      { limit, resetTime }
    );
  }

  static createContentError(contentType: string, issue: string): PluginError {
    return new PluginError(
      `Content processing error for ${contentType}: ${issue}`,
      ErrorType.CONTENT,
      ErrorSeverity.MEDIUM,
      'CONT001',
      `Unable to process the ${contentType} content. ${issue}`,
      [
        'Check the content format and structure',
        'Reduce content size if it is too large',
        'Remove any unsupported characters or formatting'
      ],
      { contentType, issue }
    );
  }

  static createDatabaseError(operation: string, details: string): PluginError {
    return new PluginError(
      `Database error during ${operation}: ${details}`,
      ErrorType.DATABASE,
      ErrorSeverity.HIGH,
      'DB001',
      'A database error occurred. Your changes may not have been saved.',
      [
        'Try the operation again',
        'Check your database connection',
        'Contact support if the issue persists'
      ],
      { operation, details }
    );
  }
}

// Error boundary for React components
export class ErrorBoundary {
  static async handleAsyncError<T>(
    operation: () => Promise<T>,
    context: string
  ): Promise<{ success: true; data: T } | { success: false; error: PluginError }> {
    try {
      const data = await operation();
      return { success: true, data };
    } catch (error) {
      const pluginError = ErrorHandler.handle(
        error instanceof PluginError ? error : 
        new PluginError(
          `Error in ${context}: ${error instanceof Error ? error.message : 'Unknown error'}`,
          ErrorType.SYSTEM,
          ErrorSeverity.MEDIUM,
          'SYS002',
          `An error occurred while ${context}. Please try again.`,
          ['Refresh the page', 'Check your internet connection', 'Try again in a few minutes']
        )
      );
      
      return { success: false, error: pluginError };
    }
  }

  static handleSyncError<T>(
    operation: () => T,
    context: string
  ): { success: true; data: T } | { success: false; error: PluginError } {
    try {
      const data = operation();
      return { success: true, data };
    } catch (error) {
      const pluginError = ErrorHandler.handle(
        error instanceof PluginError ? error :
        new PluginError(
          `Error in ${context}: ${error instanceof Error ? error.message : 'Unknown error'}`,
          ErrorType.SYSTEM,
          ErrorSeverity.MEDIUM,
          'SYS003',
          `An error occurred while ${context}. Please try again.`,
          ['Refresh the page', 'Clear browser cache', 'Try again in a few minutes']
        )
      );
      
      return { success: false, error: pluginError };
    }
  }
}

// Validation utilities
export class Validator {
  static validateBusinessProfile(profile: any): PluginError[] {
    const errors: PluginError[] = [];

    // Required fields validation
    if (!profile.businessName || profile.businessName.trim().length < 2) {
      errors.push(ErrorFactory.createValidationError('businessName', profile.businessName, 'string with minimum 2 characters'));
    }

    if (!profile.businessType || profile.businessType.trim().length === 0) {
      errors.push(ErrorFactory.createValidationError('businessType', profile.businessType, 'non-empty string'));
    }

    if (!profile.description || profile.description.trim().length < 10) {
      errors.push(ErrorFactory.createValidationError('description', profile.description, 'string with minimum 10 characters'));
    }

    // Email validation
    if (profile.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(profile.email)) {
      errors.push(ErrorFactory.createValidationError('email', profile.email, 'valid email format'));
    }

    // URL validation
    if (profile.website && profile.website.length > 0) {
      try {
        new URL(profile.website);
      } catch {
        errors.push(ErrorFactory.createValidationError('website', profile.website, 'valid URL'));
      }
    }

    // Keywords validation
    if (profile.keywords && Array.isArray(profile.keywords)) {
      if (profile.keywords.length > 20) {
        errors.push(ErrorFactory.createValidationError('keywords', profile.keywords, 'maximum 20 keywords'));
      }
    }

    return errors;
  }

  static validateApiConfiguration(config: any): PluginError[] {
    const errors: PluginError[] = [];

    if (!config.providers || !Array.isArray(config.providers)) {
      errors.push(ErrorFactory.createValidationError('providers', config.providers, 'array of provider configurations'));
      return errors;
    }

    config.providers.forEach((provider: any, index: number) => {
      if (!provider.id || typeof provider.id !== 'string') {
        errors.push(ErrorFactory.createValidationError(`providers[${index}].id`, provider.id, 'string identifier'));
      }

      if (provider.enabled && (!provider.apiKey || provider.apiKey.length < 10)) {
        errors.push(ErrorFactory.createValidationError(`providers[${index}].apiKey`, provider.apiKey, 'valid API key (minimum 10 characters)'));
      }

      if (provider.priority && (typeof provider.priority !== 'number' || provider.priority < 1 || provider.priority > 10)) {
        errors.push(ErrorFactory.createValidationError(`providers[${index}].priority`, provider.priority, 'number between 1 and 10'));
      }

      if (provider.dailyLimit && (typeof provider.dailyLimit !== 'number' || provider.dailyLimit < 1)) {
        errors.push(ErrorFactory.createValidationError(`providers[${index}].dailyLimit`, provider.dailyLimit, 'positive number'));
      }
    });

    return errors;
  }
}

// Recovery suggestions
export class RecoveryManager {
  static getRecoveryActions(error: PluginError): string[] {
    const baseActions = [...error.suggestions];

    // Add type-specific recovery actions
    switch (error.type) {
      case ErrorType.API:
        baseActions.push('Check API service status page');
        baseActions.push('Try using a different AI provider');
        break;
      case ErrorType.NETWORK:
        baseActions.push('Check firewall settings');
        baseActions.push('Try using a VPN if available');
        break;
      case ErrorType.RATE_LIMIT:
        baseActions.push('Upgrade to a higher plan');
        baseActions.push('Implement request queuing');
        break;
      case ErrorType.VALIDATION:
        baseActions.push('Use the field validation hints');
        baseActions.push('Check example values provided');
        break;
      case ErrorType.PERMISSION:
        baseActions.push('Review user role assignments');
        baseActions.push('Check WordPress capabilities');
        break;
    }

    return baseActions;
  }

  static createRecoveryPlan(errors: PluginError[]): {
    immediate: string[];
    shortTerm: string[];
    longTerm: string[];
  } {
    const plan = {
      immediate: [] as string[],
      shortTerm: [] as string[],
      longTerm: [] as string[]
    };

    const criticalErrors = errors.filter(e => e.severity === ErrorSeverity.CRITICAL);
    const highErrors = errors.filter(e => e.severity === ErrorSeverity.HIGH);
    const mediumErrors = errors.filter(e => e.severity === ErrorSeverity.MEDIUM);
    const lowErrors = errors.filter(e => e.severity === ErrorSeverity.LOW);

    // Immediate actions for critical and high severity errors
    [...criticalErrors, ...highErrors].forEach(error => {
      plan.immediate.push(...this.getRecoveryActions(error).slice(0, 2));
    });

    // Short-term actions for medium severity errors
    mediumErrors.forEach(error => {
      plan.shortTerm.push(...this.getRecoveryActions(error).slice(0, 2));
    });

    // Long-term actions for low severity errors
    lowErrors.forEach(error => {
      plan.longTerm.push(...this.getRecoveryActions(error).slice(0, 1));
    });

    // Remove duplicates
    plan.immediate = [...new Set(plan.immediate)];
    plan.shortTerm = [...new Set(plan.shortTerm)];
    plan.longTerm = [...new Set(plan.longTerm)];

    return plan;
  }
}

export default {
  ErrorType,
  ErrorSeverity,
  PluginError,
  ErrorHandler,
  ErrorFactory,
  ErrorBoundary,
  Validator,
  RecoveryManager
};