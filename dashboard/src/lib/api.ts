// API client for Hexagon Automation WordPress plugin
const API_BASE = '/wp-json/hexagon/v1';

interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
}

class HexagonAPI {
  private apiKey: string | null = null;
  private baseUrl: string;

  constructor() {
    // Get WordPress site URL from meta tag or window object
    this.baseUrl = (window as any).hexagonConfig?.apiUrl || '/wp-json/hexagon/v1';
  }

  setApiKey(key: string) {
    this.apiKey = key;
    localStorage.setItem('hexagon_api_key', key);
  }

  getApiKey(): string | null {
    if (!this.apiKey) {
      this.apiKey = localStorage.getItem('hexagon_api_key');
    }
    return this.apiKey;
  }

  private async request<T = any>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    const apiKey = this.getApiKey();
    if (apiKey) {
      headers['X-API-Key'] = apiKey;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      return { success: true, data };
    } catch (error) {
      console.error('API Request failed:', error);
      return {
        success: false,
        message: error instanceof Error ? error.message : 'Unknown error'
      };
    }
  }

  // Authentication
  async authenticate(username: string, password: string): Promise<ApiResponse<{api_key: string, user: any}>> {
    return this.request('/auth', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    });
  }

  // Dashboard data
  async getDashboardData(): Promise<ApiResponse> {
    return this.request('/dashboard');
  }

  // System status
  async getSystemStatus(): Promise<ApiResponse> {
    return this.request('/status');
  }

  // AI endpoints
  async generateAIContent(data: {
    provider: string;
    content_type: string;
    prompt: string;
    language?: string;
  }): Promise<ApiResponse> {
    return this.request('/ai/generate', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async testAIConnection(provider: string): Promise<ApiResponse> {
    return this.request('/ai/test', {
      method: 'POST',
      body: JSON.stringify({ provider }),
    });
  }

  async getAIStats(): Promise<ApiResponse> {
    return this.request('/ai/stats');
  }

  // Social Media endpoints
  async postToSocial(data: {
    platform: string;
    message: string;
    image_url?: string;
    link_url?: string;
  }): Promise<ApiResponse> {
    return this.request('/social/post', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async scheduleSocialPost(data: {
    platform: string;
    message: string;
    schedule_time: string;
    image_url?: string;
    link_url?: string;
  }): Promise<ApiResponse> {
    return this.request('/social/schedule', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async testSocialConnection(platform: string): Promise<ApiResponse> {
    return this.request('/social/test', {
      method: 'POST',
      body: JSON.stringify({ platform }),
    });
  }

  async getSocialStats(): Promise<ApiResponse> {
    return this.request('/social/stats');
  }

  // Email endpoints
  async sendTestEmail(email: string): Promise<ApiResponse> {
    return this.request('/email/test', {
      method: 'POST',
      body: JSON.stringify({ test_email: email }),
    });
  }

  async sendEmail(data: {
    to: string;
    subject: string;
    message: string;
  }): Promise<ApiResponse> {
    return this.request('/email/send', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  // Settings endpoints
  async getSettings(): Promise<ApiResponse> {
    return this.request('/settings');
  }

  async updateSettings(settings: any): Promise<ApiResponse> {
    return this.request('/settings', {
      method: 'POST',
      body: JSON.stringify({ settings }),
    });
  }

  // Logs endpoints
  async getLogs(params?: {
    level?: string;
    limit?: number;
    offset?: number;
  }): Promise<ApiResponse> {
    const queryParams = new URLSearchParams();
    if (params?.level) queryParams.append('level', params.level);
    if (params?.limit) queryParams.append('limit', params.limit.toString());
    if (params?.offset) queryParams.append('offset', params.offset.toString());
    
    const query = queryParams.toString();
    return this.request(`/logs${query ? '?' + query : ''}`);
  }

  async clearLogs(): Promise<ApiResponse> {
    return this.request('/logs/clear', {
      method: 'DELETE',
    });
  }
}

export const api = new HexagonAPI();
export default api;