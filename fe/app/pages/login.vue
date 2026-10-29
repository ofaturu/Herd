<template>
  <div class="login-wrapper">
    <div class="login-card">
      <!-- Card Header -->
      <div class="card-head">
        <div class="logo-block" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <h1 class="card-title">{{ step === 'otp' ? 'Verifikasi Kode Masuk' : 'Masuk ke Portal' }}</h1>
        <p class="card-desc">
          {{ step === 'otp' ? `Masukkan 6 digit kode yang dikirim ke ${maskedEmail}` : 'Ajukan dan pantau layanan akademik Anda' }}
        </p>
      </div>

      <!-- Dev hint when demo OTP is available (OTP step only) -->
      <div v-if="step === 'otp' && devOtp" class="alert-info" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>Akun demo terdeteksi. Kode OTP: <strong>{{ devOtp }}</strong> (Email juga telah dikirim ke Gmail).</span>
      </div>

      <!-- Global Error Alert -->
      <div v-if="errorMessage" class="alert-error" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Global Success Alert -->
      <div v-if="successMessage" class="alert-success" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>{{ successMessage }}</span>
      </div>

      <!-- Step 1: Credentials Form -->
      <form v-if="step === 'credentials'" @submit.prevent="handleSubmitLogin" class="login-form" novalidate>
        <!-- Email Input Group -->
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <div class="input-wrap">
            <span class="input-icon-box" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </span>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="nama@mahasiswa.test"
              autocomplete="email"
              required
              class="form-input"
              :class="{ 'input-error': fieldErrors.email }"
            />
          </div>
          <span v-if="fieldErrors.email" class="field-err">{{ fieldErrors.email[0] }}</span>
        </div>

        <!-- Password Input Group -->
        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi</label>
          <div class="input-wrap">
            <span class="input-icon-box" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••••••"
              autocomplete="current-password"
              required
              class="form-input form-input-password"
              :class="{ 'input-error': fieldErrors.password }"
            />
            <button
              type="button"
              class="btn-toggle-password"
              :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
              @click="showPassword = !showPassword"
            >
              {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
            </button>
          </div>
          <span v-if="fieldErrors.password" class="field-err">{{ fieldErrors.password[0] }}</span>
        </div>

        <!-- Submit Button -->
        <button id="btn-login" type="submit" :disabled="loading" class="btn btn-primary btn-submit">
          <span v-if="loading" class="spinner" aria-hidden="true"></span>
          <span>{{ loading ? 'Mengirim Kode OTP...' : 'Masuk' }}</span>
        </button>
      </form>

      <!-- Step 2: OTP Verification Form -->
      <form v-else @submit.prevent="handleSubmitOtp" class="login-form" novalidate>
        <div class="form-group">
          <label for="otp-input" class="form-label text-center">Kode Verifikasi (6 Digit)</label>
          <input
            id="otp-input"
            v-model="otpCode"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="6"
            placeholder="000000"
            required
            autocomplete="one-time-code"
            autofocus
            class="form-input otp-field"
            :disabled="loading"
          />
          <span class="otp-hint">Periksa kotak masuk atau spam pada email Anda.</span>
        </div>

        <button id="btn-verify-otp" type="submit" :disabled="loading || otpCode.length < 6" class="btn btn-primary btn-submit">
          <span v-if="loading" class="spinner" aria-hidden="true"></span>
          <span>{{ loading ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}</span>
        </button>

        <div class="otp-action-row">
          <button
            type="button"
            class="btn-text-action"
            :disabled="resendLoading || resendCountdown > 0"
            @click="handleResendOtp"
          >
            {{ resendCountdown > 0 ? `Kirim ulang (${resendCountdown}d)` : 'Kirim Ulang Kode' }}
          </button>
          <button
            type="button"
            class="btn-text-action"
            @click="resetToCredentials"
          >
            Ganti Akun
          </button>
        </div>
      </form>

      <!-- Card Footer Links -->
      <div class="card-links">
        <NuxtLink to="/daftar" class="link-minor">Belum punya akun? Daftar di sini</NuxtLink>
        <span class="link-separator" aria-hidden="true">·</span>
        <span class="link-minor">Hubungi BAAK jika ada kendala</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { login, verifyOtp, resendOtp, isAuthenticated, fetchUser } = useAuth()

const step            = ref<'credentials' | 'otp'>('credentials')
const email           = ref('')
const password        = ref('')
const showPassword    = ref(false)
const loading         = ref(false)
const errorMessage    = ref('')
const successMessage  = ref('')
const fieldErrors     = ref<Record<string, string[]>>({})

// OTP State
const sessionToken    = ref('')
const maskedEmail     = ref('')
const devOtp          = ref<string | null>(null)
const otpCode         = ref('')
const resendLoading   = ref(false)
const resendCountdown = ref(0)
let timer: any = null

onMounted(async () => {
  if (isAuthenticated()) {
    const user = await fetchUser()
    if (user?.role === 'admin') router.push('/admin')
    else if (user) router.push('/mahasiswa')
  }
})

const handleSubmitLogin = async () => {
  loading.value        = true
  errorMessage.value   = ''
  successMessage.value = ''
  fieldErrors.value    = {}

  try {
    const res: any = await login(email.value, password.value)
    if (res.success && (res.requires_otp || res.data?.session_token)) {
      sessionToken.value = res.data?.session_token || ''
      maskedEmail.value  = res.data?.masked_email || email.value
      devOtp.value       = res.data?.dev_otp || null
      step.value         = 'otp'
      startCountdown()
    } else if (res.success && res.data?.token) {
      // Direct login fallback if no OTP required
      const user = res.data?.user
      if (user?.role === 'admin') router.push('/admin')
      else router.push('/mahasiswa')
    } else {
      errorMessage.value = res.message || 'Email atau kata sandi tidak sesuai.'
      if (res.errors) fieldErrors.value = res.errors
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat mencoba masuk.'
  } finally {
    loading.value = false
  }
}

const handleSubmitOtp = async () => {
  if (otpCode.value.length < 6) {
    errorMessage.value = 'Masukkan 6 digit kode verifikasi.'
    return
  }

  loading.value        = true
  errorMessage.value   = ''
  successMessage.value = ''

  try {
    const res = await verifyOtp(sessionToken.value, otpCode.value.trim())
    if (res.success) {
      const user = res.data?.user
      if (user?.role === 'admin') router.push('/admin')
      else router.push('/mahasiswa')
    } else {
      errorMessage.value = res.message || 'Kode verifikasi salah atau telah kedaluwarsa.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Gagal memverifikasi kode OTP.'
  } finally {
    loading.value = false
  }
}

const handleResendOtp = async () => {
  if (resendCountdown.value > 0 || resendLoading.value) return

  resendLoading.value  = true
  errorMessage.value   = ''
  successMessage.value = ''

  try {
    const res = await resendOtp(sessionToken.value)
    if (res.success) {
      successMessage.value = res.message || 'Kode verifikasi baru telah dikirimkan.'
      if (res.data?.dev_otp) devOtp.value = res.data.dev_otp
      startCountdown()
    } else {
      errorMessage.value = res.message || 'Gagal mengirim ulang kode OTP.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat mengirim ulang OTP.'
  } finally {
    resendLoading.value = false
  }
}

const startCountdown = () => {
  resendCountdown.value = 60
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    if (resendCountdown.value > 0) {
      resendCountdown.value--
    } else {
      clearInterval(timer)
    }
  }, 1000)
}

const resetToCredentials = () => {
  step.value           = 'credentials'
  otpCode.value        = ''
  errorMessage.value   = ''
  successMessage.value = ''
  if (timer) clearInterval(timer)
}
</script>

<style scoped>
/* ─────────────────────────────────────────────
   Page Layout & Wrapper
───────────────────────────────────────────── */
.login-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 2.5rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 430px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 32px;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(12px);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* ─────────────────────────────────────────────
   Card Header
───────────────────────────────────────────── */
.card-head {
  text-align: center;
  margin-bottom: 24px;
}

.logo-block {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 8px 18px -4px rgba(59, 130, 246, 0.4);
}

.card-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
  letter-spacing: -0.02em;
}

.card-desc {
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.45;
  margin: 0;
}

/* ─────────────────────────────────────────────
   Alerts
───────────────────────────────────────────── */
.alert-error {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.75rem 0.9rem;
  border-radius: 8px;
  background: color-mix(in srgb, var(--danger) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--danger) 32%, transparent);
  color: var(--danger);
  font-size: 0.85rem;
  line-height: 1.4;
  margin-bottom: 20px;
}

.alert-error svg {
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-info {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.75rem 0.9rem;
  border-radius: 8px;
  background: color-mix(in srgb, var(--primary) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--primary) 30%, transparent);
  color: var(--primary-text);
  font-size: 0.82rem;
  line-height: 1.45;
  margin-bottom: 20px;
}

.alert-info svg {
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-success {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 0.9rem;
  border-radius: 8px;
  background: color-mix(in srgb, var(--success) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--success) 32%, transparent);
  color: var(--success);
  font-size: 0.85rem;
  line-height: 1.4;
  margin-bottom: 20px;
}

.alert-success svg {
  flex-shrink: 0;
}

/* ─────────────────────────────────────────────
   Form Structure & Spacing
───────────────────────────────────────────── */
.login-form {
  display: flex;
  flex-direction: column;
}

.form-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 8px;
  line-height: 1.2;
}

.text-center {
  text-align: center;
}

/* ─────────────────────────────────────────────
   Input Wrapper & Icon Alignment
───────────────────────────────────────────── */
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.input-icon-box {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-dim);
  pointer-events: none;
  z-index: 2;
  transition: color 0.15s ease;
}

.input-wrap:focus-within .input-icon-box {
  color: var(--primary);
}

.form-input {
  width: 100%;
  height: 46px;
  padding: 0 14px 0 44px;
  background-color: var(--bg-subtle);
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.92rem;
  color: var(--text);
  box-sizing: border-box;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.form-input::placeholder {
  color: var(--text-dim);
  opacity: 0.85;
}

.form-input:hover:not(:focus):not(:disabled) {
  border-color: rgba(255, 255, 255, 0.18);
}

.form-input:focus {
  outline: none;
  background-color: var(--surface);
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 22%, transparent);
}

.form-input.input-error {
  border-color: var(--danger) !important;
}

.form-input.input-error:focus {
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--danger) 22%, transparent) !important;
}

.form-input-password {
  padding-right: 92px;
}

/* ─────────────────────────────────────────────
   Password Toggle Button
───────────────────────────────────────────── */
.btn-toggle-password {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 0.8125rem;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 6px;
  transition: color 0.15s ease, background-color 0.15s ease;
  z-index: 2;
  user-select: none;
  line-height: 1;
}

.btn-toggle-password:hover {
  color: var(--text);
  background-color: color-mix(in srgb, var(--text) 8%, transparent);
}

.btn-toggle-password:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px var(--primary);
}

.btn-toggle-password:active {
  transform: translateY(-50%) scale(0.97);
}

.field-err {
  font-size: 0.75rem;
  color: var(--danger);
  margin-top: 6px;
  line-height: 1.25;
}

/* ─────────────────────────────────────────────
   Submit Button
───────────────────────────────────────────── */
.btn-submit {
  width: 100%;
  height: 46px;
  margin-top: 4px;
  font-size: 0.9375rem;
  font-weight: 600;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  letter-spacing: 0.01em;
  box-shadow: 0 4px 14px -2px rgba(59, 130, 246, 0.4);
  transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.btn-submit:hover:not(:disabled) {
  background-color: var(--primary-dark);
  box-shadow: 0 6px 18px -2px rgba(59, 130, 246, 0.5);
  transform: translateY(-1px);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 2px 8px -1px rgba(59, 130, 246, 0.35);
}

.btn-submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none;
}

/* ─────────────────────────────────────────────
   OTP Field & Actions
───────────────────────────────────────────── */
.otp-field {
  font-size: 1.75rem !important;
  font-weight: 800;
  letter-spacing: 0.4em;
  text-align: center;
  height: 52px;
  padding: 0 1rem !important;
  font-family: monospace;
}

.otp-hint {
  font-size: 0.78rem;
  color: var(--text-dim);
  text-align: center;
  margin-top: 8px;
}

.otp-action-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 14px;
}

.btn-text-action {
  background: transparent;
  border: none;
  color: var(--primary-text);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 4px;
  transition: opacity 0.15s ease, color 0.15s ease;
}

.btn-text-action:hover:not(:disabled) {
  color: var(--text);
  text-decoration: underline;
}

.btn-text-action:disabled {
  color: var(--text-dim);
  cursor: not-allowed;
  text-decoration: none;
}

/* ─────────────────────────────────────────────
   Card Footer Links
───────────────────────────────────────────── */
.card-links {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 24px;
  font-size: 0.8125rem;
}

.link-minor {
  color: var(--text-dim);
  text-decoration: none;
  transition: color 0.15s ease;
}

a.link-minor:hover {
  color: var(--primary);
  text-decoration: underline;
}

.link-separator {
  color: var(--text-dim);
}

/* ─────────────────────────────────────────────
   Responsive Design
───────────────────────────────────────────── */
@media (max-width: 480px) {
  .login-wrapper {
    padding: 1.5rem 1rem;
  }

  .login-card {
    padding: 24px 20px;
    border-radius: 12px;
  }

  .card-head {
    margin-bottom: 20px;
  }

  .logo-block {
    width: 44px;
    height: 44px;
    margin-bottom: 12px;
  }

  .card-title {
    font-size: 1.3rem;
  }

  .card-desc {
    font-size: 0.82rem;
  }

  .form-group {
    margin-bottom: 18px;
  }

  .form-input {
    height: 44px;
    font-size: 0.875rem;
    padding-left: 42px;
  }

  .form-input-password {
    padding-right: 86px;
  }

  .input-icon-box {
    left: 12px;
  }

  .btn-submit {
    height: 44px;
    font-size: 0.9rem;
  }

  .card-links {
    margin-top: 20px;
    font-size: 0.75rem;
    flex-direction: column;
    gap: 0.35rem;
  }

  .link-separator {
    display: none;
  }
}
</style>
