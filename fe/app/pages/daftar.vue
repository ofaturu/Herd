<template>
  <div class="register-page">
    <div class="register-card">
      <div class="card-header">
        <div class="badge">Pendaftaran Akun</div>
        <h1 class="card-title">Daftar Akun Mahasiswa</h1>
        <p class="card-subtitle">Buat akun untuk mengakses layanan dan permohonan surat akademik</p>
      </div>

      <div v-if="errorMessage" class="alert alert-error" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ errorMessage }}</span>
      </div>

      <form @submit.prevent="handleRegister" class="register-form">
        <div class="form-row">
          <div class="form-group">
            <label for="reg-name" class="form-label">Nama Lengkap</label>
            <input
              id="reg-name"
              v-model="form.name"
              type="text"
              required
              autocomplete="name"
              placeholder="Contoh: Muhammad Fajar"
              class="form-control"
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="reg-nim" class="form-label">NIM (Nomor Induk Mahasiswa)</label>
            <input
              id="reg-nim"
              v-model="form.nim"
              type="text"
              required
              placeholder="Contoh: 202410370311001"
              class="form-control"
              :disabled="loading"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="reg-prodi" class="form-label">Program Studi</label>
            <select
              id="reg-prodi"
              v-model="form.prodi"
              required
              class="form-control"
              :disabled="loading"
            >
              <option value="" disabled>Pilih Program Studi</option>
              <option value="Informatika">Informatika</option>
              <option value="Sistem Informasi">Sistem Informasi</option>
              <option value="Teknik Elektro">Teknik Elektro</option>
              <option value="Teknik Industri">Teknik Industri</option>
              <option value="Teknik Mesin">Teknik Mesin</option>
              <option value="Teknik Sipil">Teknik Sipil</option>
            </select>
          </div>

          <div class="form-group">
            <label for="reg-hp" class="form-label">Nomor HP / WhatsApp</label>
            <input
              id="reg-hp"
              v-model="form.no_hp"
              type="tel"
              autocomplete="tel"
              placeholder="Contoh: 081234567890"
              class="form-control"
              :disabled="loading"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="reg-email" class="form-label">Alamat Email</label>
          <input
            id="reg-email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="nama@student.ac.id"
            class="form-control"
            :disabled="loading"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="reg-password" class="form-label">Kata Sandi (minimal 6 karakter)</label>
            <input
              id="reg-password"
              v-model="form.password"
              type="password"
              required
              autocomplete="new-password"
              placeholder="••••••••"
              class="form-control"
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="reg-password-confirm" class="form-label">Konfirmasi Kata Sandi</label>
            <input
              id="reg-password-confirm"
              v-model="form.password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              placeholder="••••••••"
              class="form-control"
              :disabled="loading"
            />
          </div>
        </div>

        <button
          id="btn-submit-register"
          type="submit"
          class="btn btn-primary btn-block"
          :disabled="loading"
        >
          <span v-if="loading" class="spinner-small" aria-hidden="true"></span>
          <span>{{ loading ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}</span>
        </button>
      </form>

      <div class="card-footer">
        <span>Sudah memiliki akun?</span>
        <NuxtLink to="/login" class="link-action">Masuk ke Portal</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'

const { register } = useAuth()
const router = useRouter()

const form = reactive({
  name: '',
  nim: '',
  prodi: '',
  no_hp: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const errorMessage = ref('')

const handleRegister = async () => {
  errorMessage.value = ''

  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  if (form.password.length < 6) {
    errorMessage.value = 'Kata sandi minimal 6 karakter.'
    return
  }

  loading.value = true
  try {
    const res = await register({
      name: form.name.trim(),
      nim: form.nim.trim(),
      prodi: form.prodi,
      no_hp: form.no_hp.trim() || undefined,
      email: form.email.trim(),
      password: form.password,
    })

    if (res.success) {
      router.push('/mahasiswa')
    } else {
      errorMessage.value = res.message || 'Pendaftaran gagal. Periksa kembali data Anda.'
    }
  } catch (err: any) {
    errorMessage.value = err?.message || 'Terjadi kesalahan pada server saat mendaftar.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-page {
  min-height: calc(100vh - 120px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;
}

.register-card {
  width: 100%;
  max-width: 600px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.card-header {
  margin-bottom: 2rem;
  text-align: center;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: color-mix(in srgb, var(--primary) 12%, transparent);
  color: var(--primary-text);
  margin-bottom: 0.75rem;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.card-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.4rem;
}

.card-subtitle {
  font-size: 0.88rem;
  color: var(--text-muted);
}

.register-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 580px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text);
}

.form-control {
  width: 100%;
  padding: 0.65rem 0.9rem;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
}

.btn-block {
  width: 100%;
  padding: 0.75rem;
  font-size: 0.92rem;
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert-error {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--danger) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--danger) 30%, transparent);
  color: var(--danger);
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.card-footer {
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.link-action {
  color: var(--primary-text);
  font-weight: 600;
  text-decoration: none;
}

.link-action:hover {
  text-decoration: underline;
}
</style>
