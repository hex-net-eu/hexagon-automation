import { useState, useEffect } from 'react';
import api from '@/lib/api';

interface UseAPIOptions {
  autoFetch?: boolean;
  dependencies?: any[];
}

export function useAPI<T = any>(
  apiCall: () => Promise<any>,
  options: UseAPIOptions = {}
) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { autoFetch = true, dependencies = [] } = options;

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiCall();
      
      if (response.success) {
        setData(response.data);
      } else {
        setError(response.message || 'API call failed');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (autoFetch) {
      fetchData();
    }
  }, dependencies);

  return {
    data,
    loading,
    error,
    refetch: fetchData,
    setData
  };
}

// Specific hooks for common API calls
export function useDashboardData() {
  return useAPI(() => api.getDashboardData());
}

export function useSystemStatus() {
  return useAPI(() => api.getSystemStatus());
}

export function useSettings() {
  return useAPI(() => api.getSettings());
}

export function useAIStats() {
  return useAPI(() => api.getAIStats());
}

export function useSocialStats() {
  return useAPI(() => api.getSocialStats());
}

export function useLogs(params?: { level?: string; limit?: number; offset?: number }) {
  return useAPI(() => api.getLogs(params), {
    dependencies: [params?.level, params?.limit, params?.offset]
  });
}