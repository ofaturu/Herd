// app/composables/useAuth.ts
import { ref } from 'vue'
import { useCookie, useRouter } from '#app'
import { useApi, type ApiResponse } from './useApi'

export interface UserProfile {
  id: number
  name: string
  email: string
  role: 'admin' | 'mahasiswa' | string
  nim?: string | null
  prodi?: string | null
}

const currentUser = ref<UserProfile | null>(null)
const authToken = ref<string | null>(null)
const isInitialized = ref(false)

export function useAuth() {
  const api = useApi()
  const router = useRouter()
  const authCookie = useCookie<string | null>('access_token')

  const loadStoredToken = () => {
    if (authCookie.value) {
      authToken.value = authCookie.value
      api.setAuthHeader(authCookie.value)
    } else {
      authToken.value = null
      currentUser.value = null
    }
  }

  const fetchUser = async (): Promise<UserProfile | null> => {
    loadStoredToken()
    if (!authToken.value) {
      currentUser.value = null
      return null
    }

    const res = await api.get<UserProfile>('/user/me')
    if (res.success && res.data) {
      currentUser.value = res.data
      return res.data
    } else {
      // Token might be invalid
      currentUser.value = null
      authToken.value = null
      authCookie.value = null
      return null
    }
  }

  const login = async (email: string, password: string): Promise<ApiResponse<any>> => {
    const res = await api.post('/auth/login', { email, password })
    if (res.success && res.data?.token) {
      authToken.value = res.data.token
      authCookie.value = res.data.token
      api.setAuthHeader(res.data.token)

      if (res.data.user) {
        currentUser.value = res.data.user
      } else {
        await fetchUser()
      }
    }
    return res
  }

  const logout = async () => {
    try {
      if (authToken.value) {
        await api.post('/auth/logout')
      }
    } catch {
      // ignore network errors on logout
    } finally {
      authToken.value = null
      currentUser.value = null
      authCookie.value = null
      api.clearAuthHeader()
      router.push('/login')
    }
  }

  const isAuthenticated = () => {
    loadStoredToken()
    return !!authToken.value
  }

  return {
    user: currentUser,
    token: authToken,
    isInitialized,
    login,
    logout,
    fetchUser,
    isAuthenticated,
    loadStoredToken
  }
}
