import { useRuntimeConfig, useState } from 'nuxt/app'

interface LoginPayload {
  username: string
  password: string
  remember?: boolean
}

interface RegisterProfile {
  fullName?: string
  phone?: string
  country?: string
  company?: string
  marketingOptIn?: boolean
  notes?: string
}

interface RegisterPayload {
  username: string
  email: string
  password: string
  profile?: RegisterProfile
}

interface AuthUser {
  id?: number
  username?: string
  email?: string
  display_name?: string
  profile?: RegisterProfile
  loyalty?: Record<string, unknown>
  [key: string]: unknown
}

type MaybeJson = Record<string, unknown> | string | null

const defaultCredentials: RequestCredentials = 'include'

const readResponse = async (response: Response): Promise<MaybeJson> => {
  const text = await response.text()
  if (!text) {
    return null
  }
  try {
    return JSON.parse(text)
  } catch (_) {
    return text
  }
}

const extractMessage = (payload: MaybeJson, fallback: string) => {
  if (!payload) {
    return fallback
  }
  if (typeof payload === 'string') {
    return payload || fallback
  }
  const message = payload?.message
  return typeof message === 'string' && message.trim().length > 0 ? message : fallback
}

export function useAuth() {
  const config = useRuntimeConfig()
  const baseURL = config.public?.wpApiBase as string | undefined

  const user = useState<AuthUser | null>('auth-user', () => null)
  const loading = useState<boolean>('auth-loading', () => false)
  const error = useState<string | null>('auth-error', () => null)
  const initialized = useState<boolean>('auth-initialized', () => false)

  const request = async <T = MaybeJson>(path: string, init: RequestInit = {}, fallbackMessage = 'Request failed'): Promise<T> => {
    if (!baseURL) {
      throw new Error('Missing runtimeConfig.public.wpApiBase for authentication requests')
    }

    const headers = new Headers(init.headers || undefined)
    if (config.public?.wpNonce && !headers.has('X-WP-Nonce')) {
      headers.set('X-WP-Nonce', String(config.public.wpNonce))
    }

    const finalInit: RequestInit = {
      credentials: defaultCredentials,
      ...init,
      headers
    }

    const response = await fetch(`${baseURL}${path}`, finalInit)
    const payload = await readResponse(response)

    if (!response.ok) {
      throw new Error(extractMessage(payload, fallbackMessage))
    }

    return payload as T
  }

  const ensureSession = async (force = false) => {
    if (!baseURL) {
      initialized.value = true
      return null
    }
    if (initialized.value && !force) {
      return user.value
    }

    initialized.value = true
    try {
      const data = await request<AuthUser>('/mytheme/v1/auth/me', { headers: { 'Accept': 'application/json' } }, 'Unable to fetch session')
      user.value = data
      error.value = null
      return data
    } catch (_) {
      user.value = null
      return null
    }
  }

  const login = async (payload: LoginPayload) => {
    loading.value = true
    error.value = null

    try {
      const data = await request<AuthUser>(
        '/mytheme/v1/auth/login',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        },
        'Login failed'
      )
      user.value = data
      return data
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Login failed'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  const register = async (payload: RegisterPayload) => {
    loading.value = true
    error.value = null

    try {
      const data = await request<AuthUser>(
        '/mytheme/v1/auth/register',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        },
        'Registration failed'
      )
      user.value = data
      return data
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Registration failed'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    if (!baseURL) {
      user.value = null
      return
    }

    try {
      await request('/mytheme/v1/auth/logout', { method: 'POST' }, 'Logout failed')
    } catch (err) {
      console.warn('Logout request failed:', err)
    } finally {
      user.value = null
    }
  }

  return {
    user,
    loading,
    error,
    initialized,
    ensureSession,
    login,
    register,
    logout
  }
}
