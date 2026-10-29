<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="card-header">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <h1 class="card-title">Portal Mahasiswa</h1>
        <p class="card-subtitle">Masuk untuk mengajukan dan memantau layanan akademik Anda</p>
      </div>

      <!-- Quick Test Accounts Helper -->
      <div class="quick-accounts">
        <span class="quick-title">Gunakan Akun Uji Coba:</span>
        <div class="quick-buttons">
          <button
            type="button"
            class="btn-quick"
            @click="fillCredentials('budi.santoso@mahasiswa.test', 'password123')"
          >
            👤 Budi (Mhs)
          </button>
          <button
            type="button"
            class="btn-quick"
            @click="fillCredentials('siti.aminah@mahasiswa.test', 'password123')"
          >
            👤 Siti (Mhs)
          </button>
          <button
            type="button"
            class="btn-quick btn-quick-admin"
            @click="fillCredentials('admin@portal.test', 'password123')"
          >
            👑 Admin Layanan
          </button>
        </div>
      </div>

      <div v-if="errorMessage" class="alert-box alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <span>{{ errorMessage }}</span>
      </div>

      <form @submit.prevent="handleSubmit" class="login-form">
        <div class="form-group">
          <label for="email" class="form-label">Email Mahasiswa</label>
          <div class="input-container">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="nama@mahasiswa.test"
              required
              class="form-input"
              :class="{ 'input-invalid': fieldErrors.email }"
            />
          </div>
          <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi</label>
          <div class="input-container">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
              class="form-input"
              :class="{ 'input-invalid': fieldErrors.password }"
            />
            <button type="button" class="btn-toggle-eye" @click="showPassword = !showPassword">
              {{ showPassword ? 'Sembunyikan' : 'Lihat' }}
            </button>
          </div>
          <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</span>
        </div>

        <button type="submit" :disabled="loading" class="btn btn-primary btn-block">
          <span v-if="loading" class="spinner"></span>
          <span>{{ loading ? 'Memverifikasi...' : 'Masuk Sekarang' }}</span>
        </button>
      </form>

      <div class="card-footer">
        <p>Butuh bantuan akun? Hubungi Bagian Kemahasiswaan & BAAK.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { login, isAuthenticated, fetchUser } = useAuth()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

onMounted(async () => {
  if (isAuthenticated()) {
    const user = await fetchUser()
    if (user) {
      if (user.role === 'admin') {
        router.push('/admin')
      } else {
        router.push('/mahasiswa')
      }
    }
  }
})

const fillCredentials = (em: string, pw: string) => {
  email.value = em
  password.value = pw
  errorMessage.value = ''
  fieldErrors.value = {}
}

const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  try {
    const res = await login(email.value, password.value)
    if (res.success) {
      const user = res.data?.user
      if (user?.role === 'admin') {
        router.push('/admin')
      } else {
        router.push('/mahasiswa')
      }
    } else {
      errorMessage.value = res.message || 'Login gagal. Periksa kembali email dan password.'
      if (res.errors) {
        fieldErrors.value = res.errors
      }
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan sistem saat mencoba login.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 200px);
  padding: 1.5rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.25rem;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(16px);
}

.card-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.logo-icon {
  width: 52px;
  height: 52px;
  margin: 0 auto 1rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #2563eb, #38bdf8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
}

.card-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 0.35rem;
}

.card-subtitle {
  font-size: 0.88rem;
  color: var(--text-muted);
  line-height: 1.4;
}

.quick-accounts {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 0.85rem;
  margin-bottom: 1.25rem;
}

.quick-title {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.quick-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.btn-quick {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.25);
  color: var(--primary-light);
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.45rem 0.5rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-quick:hover {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
}

.btn-quick-admin {
  background: rgba(168, 85, 247, 0.12) !important;
  border-color: rgba(168, 85, 247, 0.35) !important;
  color: #d8b4fe !important;
  grid-column: span 2;
}

.btn-quick-admin:hover {
  background: rgba(168, 85, 247, 0.22) !important;
}

.alert-box {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.alert-error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-main);
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 0.85rem;
  color: var(--text-dim);
  pointer-events: none;
}

.form-input {
  width: 100%;
  padding: 0.7rem 0.85rem 0.7rem 2.4rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  color: #fff;
  font-size: 0.9rem;
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
  background: rgba(15, 23, 42, 0.85);
}

.input-invalid {
  border-color: var(--danger) !important;
}

.field-error {
  font-size: 0.75rem;
  color: #f87171;
}

.btn-toggle-eye {
  position: absolute;
  right: 0.65rem;
  background: transparent;
  border: none;
  color: var(--text-dim);
  font-size: 0.75rem;
  cursor: pointer;
  padding: 0.2rem 0.4rem;
}

.btn-toggle-eye:hover {
  color: var(--text-main);
}

.btn-block {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.card-footer {
  margin-top: 1.75rem;
  text-align: center;
  font-size: 0.78rem;
  color: var(--text-dim);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
