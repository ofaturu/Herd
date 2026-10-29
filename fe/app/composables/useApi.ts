// app/composables/useApi.ts
import { useRuntimeConfig, useCookie } from '#app'

export interface ApiResponse<T = any> {
  success: boolean
  message?: string
  data?: T
  errors?: Record<string, string[]>
  meta?: any
}

export const useApi = () => {
  const config = useRuntimeConfig()
  const baseUrl = (config.public.apiBaseUrl as string) || 'http://localhost:8000'
  const tokenCookie = useCookie<string | null>('access_token', {
    maxAge: 60 * 60 * 24 * 7, // 7 days
    sameSite: 'lax',
    path: '/'
  })

  const buildUrl = (endpoint: string) => {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`
    const apiPath = cleanEndpoint.startsWith('/api') ? cleanEndpoint : `/api${cleanEndpoint}`
    return `${baseUrl.replace(/\/$/, '')}${apiPath}`
  }

  const getHeaders = (customHeaders?: HeadersInit) => {
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
    if (tokenCookie.value) {
      headers['Authorization'] = `Bearer ${tokenCookie.value}`
    }
    if (customHeaders) {
      if (customHeaders instanceof Headers) {
        customHeaders.forEach((val, key) => { headers[key] = val })
      } else if (Array.isArray(customHeaders)) {
        customHeaders.forEach(([k, v]) => { headers[k] = v })
      } else {
        Object.assign(headers, customHeaders)
      }
    }
    return headers
  }

  const request = async <T = any>(endpoint: string, options: any = {}): Promise<ApiResponse<T>> => {
    const url = buildUrl(endpoint)
    const headers = getHeaders(options.headers)

    try {
      const response = await $fetch<any>(url, {
        ...options,
        headers
      })
      // If backend returns standard { success: true, data: ..., message: ... }
      if (typeof response === 'object' && response !== null && 'success' in response) {
        return response as ApiResponse<T>
      }
      return {
        success: true,
        data: response
      }
    } catch (err: any) {
      const errorData = err.response?._data || err.data
      const status = err.response?.status || err.status

      if (status === 401) {
        // Token expired or invalid
        tokenCookie.value = null
      }

      return {
        success: false,
        message: errorData?.message || err.message || 'Terjadi kesalahan saat menghubungi server.',
        errors: errorData?.errors || undefined,
        data: undefined
      }
    }
  }

  return {
    fetch: request,
    get: <T = any>(endpoint: string, options: any = {}) => request<T>(endpoint, { method: 'GET', ...options }),
    post: <T = any>(endpoint: string, body?: any, options: any = {}) => request<T>(endpoint, { method: 'POST', body, ...options }),
    put: <T = any>(endpoint: string, body?: any, options: any = {}) => request<T>(endpoint, { method: 'PUT', body, ...options }),
    patch: <T = any>(endpoint: string, body?: any, options: any = {}) => request<T>(endpoint, { method: 'PATCH', body, ...options }),
    delete: <T = any>(endpoint: string, options: any = {}) => request<T>(endpoint, { method: 'DELETE', ...options }),
    setAuthHeader: (token: string) => { tokenCookie.value = token },
    clearAuthHeader: () => { tokenCookie.value = null },
    getToken: () => tokenCookie.value,
    postForm: async <T = any>(endpoint: string, formData: FormData): Promise<ApiResponse<T>> => {
      const url = buildUrl(endpoint)
      // Omit Content-Type so fetch sets multipart boundary automatically
      const headers: Record<string, string> = { 'Accept': 'application/json' }
      if (tokenCookie.value) headers['Authorization'] = `Bearer ${tokenCookie.value}`
      try {
        const response = await $fetch<any>(url, { method: 'POST', body: formData, headers })
        if (typeof response === 'object' && response !== null && 'success' in response) {
          return response as ApiResponse<T>
        }
        return { success: true, data: response }
      } catch (err: any) {
        const errorData = err.response?._data || err.data
        return {
          success: false,
          message: errorData?.message || err.message || 'Gagal mengunggah file.',
          errors: errorData?.errors
        }
      }
    }
  }
}
