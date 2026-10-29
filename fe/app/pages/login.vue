<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="card-head">
        <div class="logo-block">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <h1 class="card-title">{{ step === 'otp' ? 'Verifikasi Kode Masuk' : 'Masuk ke Portal' }}</h1>
        <p class="card-desc">
          {{ step === 'otp' ? `Masukkan 6 digit kode yang dikirim ke ${maskedEmail}` : 'Ajukan dan pantau layanan akademik Anda' }}
        </p>
      </div>

      <!-- Quick accounts (only in credentials step) -->
      <div v-if="step === 'credentials'" class="quick-accounts" role="region" aria-label="Akun uji coba">
        <p class="quick-label">Uji Coba Cepat</p>
        <div class="quick-grid">
          <button
            id="btn-quick-budi"
            type="button"
            class="btn-quick"
            @click="fillCredentials('budi.santoso@mahasiswa.test', 'password123')"
          >Budi Santoso</button>
          <button
            id="btn-quick-siti"
            type="button"
            class="btn-quick"
            @click="fillCredentials('siti.aminah@mahasiswa.test', 'password123')"
          >Siti Aminah</button>
          <button
            id="btn-quick-admin"
            type="button"
            class="btn-quick btn-quick-admin"
            @click="fillCredentials('admin@portal.test', 'password123')"
          >Administrator</button>
        </div>
      </div>

      <!-- Dev hint when demo OTP is available -->
      <div v-if="step === 'otp' && devOtp" class="alert-info">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>Akun demo terdeteksi. Kode OTP: <strong>{{ devOtp }}</strong> (Email juga telah dikirim ke Gmail).</span>
      </div>

      <div v-if="errorMessage" class="alert-error" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ errorMessage }}</span>
      </div>

      <div v-if="successMessage" class="alert-success" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>{{ successMessage }}</span>
      </div>

      <!-- Step 1: Credentials Form -->
      <form v-if="step === 'credentials'" @submit.prevent="handleSubmitLogin" class="login-form" novalidate>
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="nama@mahasiswa.test"
              autocomplete="email"
              required
              class="form-input input-with-icon"
              :class="{ 'input-error': fieldErrors.email }"
            />
          </div>
          <span v-if="fieldErrors.email" class="field-err">{{ fieldErrors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              required
              class="form-input input-with-icon"
              :class="{ 'input-error': fieldErrors.password }"
            />
            <button
              type="button"
              class="btn-eye"
              :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
              @click="showPassword = !showPassword"
            >{{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}</button>
          </div>
          <span v-if="fieldErrors.password" class="field-err">{{ fieldErrors.password[0] }}</span>
        </div>

        <button id="btn-login" type="submit" :disabled="loading" class="btn btn-primary btn-block">
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

        <button id="btn-verify-otp" type="submit" :disabled="loading || otpCode.length < 6" class="btn btn-primary btn-block">
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

const fillCredentials = (em: string, pw: string) => {
  email.value        = em
  password.value     = pw
  errorMessage.value = ''
  fieldErrors.value  = {}
}

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
.login-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 160px);
  padding: 2rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2.25rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

/* Header */
.card-head { text-align: center; margin-bottom: 1.5rem; }

.logo-block {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--primary);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.card-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.35rem;
}

.card-desc {
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.45;
}

/* Quick accounts */
.quick-accounts {
  background: color-mix(in srgb, var(--primary) 6%, transparent);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.85rem;
  margin-bottom: 1.25rem;
}

.quick-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.5rem;
}

.quick-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
}

.btn-quick {
  background: var(--bg-subtle, var(--border-subtle));
  border: 1px solid var(--border);
  color: var(--text-muted);
  font-size: 0.78rem;
  font-weight: 500;
  padding: 0.45rem 0.5rem;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-family: inherit;
  transition: background 0.15s, color 0.15s;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-quick:hover {
  background: var(--border);
  color: var(--text);
}

.btn-quick-admin {
  grid-column: span 2;
}

/* Alerts */
.alert-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 0.85rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--danger) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--danger) 30%, transparent);
  color: var(--danger);
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.alert-info {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.7rem 0.85rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
  color: var(--primary-text);
  font-size: 0.82rem;
  margin-bottom: 1.25rem;
  line-height: 1.4;
}

.alert-success {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 0.85rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--success) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--success) 30%, transparent);
  color: var(--success);
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

/* Form */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.form-group { display: flex; flex-direction: column; gap: 0.35rem; }

.text-center { text-align: center; }

.input-wrap { position: relative; display: flex; align-items: center; }

.input-icon {
  position: absolute;
  left: 0.75rem;
  color: var(--text-dim);
  pointer-events: none;
  flex-shrink: 0;
}

.input-with-icon { padding-left: 2.25rem; }

.form-input {
  width: 100%;
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
}

.input-error {
  border-color: var(--danger) !important;
}

.field-err { font-size: 0.75rem; color: var(--danger); }

.otp-field {
  font-size: 1.85rem !important;
  font-weight: 800;
  letter-spacing: 0.45em;
  text-align: center;
  padding: 0.75rem 0.5rem !important;
  font-family: monospace;
}

.otp-hint {
  font-size: 0.75rem;
  color: var(--text-dim);
  text-align: center;
  margin-top: 0.25rem;
}

.btn-eye {
  position: absolute;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: var(--text-dim);
  font-size: 0.72rem;
  font-family: inherit;
  cursor: pointer;
  padding: 0.2rem 0.3rem;
  border-radius: 4px;
  transition: color 0.15s;
}
.btn-eye:hover { color: var(--text); }

.btn-block {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.25rem;
  font-size: 0.92rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.otp-action-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.5rem;
}

.btn-text-action {
  background: transparent;
  border: none;
  color: var(--primary-text);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0.25rem 0.4rem;
  border-radius: 4px;
  transition: opacity 0.15s;
}

.btn-text-action:disabled {
  color: var(--text-dim);
  cursor: not-allowed;
}

/* Footer links */
.card-links {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 1.5rem;
  font-size: 0.78rem;
}

.link-minor {
  color: var(--text-dim);
  text-decoration: none;
}
a.link-minor:hover { color: var(--primary); text-decoration: underline; }

.link-separator { color: var(--text-dim); }
</style>
