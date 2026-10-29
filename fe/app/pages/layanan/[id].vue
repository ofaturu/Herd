<template>
  <div class="service-detail-page">
    <div class="breadcrumb">
      <NuxtLink to="/layanan" class="crumb-link">← Kembali ke Katalog Layanan</NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat rincian layanan...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>{{ errorMessage }}</p>
      <NuxtLink to="/layanan" class="btn btn-secondary">Kembali ke Daftar Layanan</NuxtLink>
    </div>

    <!-- Service Detail Content -->
    <div v-else-if="service" class="detail-grid">
      <!-- Left Column: Information -->
      <div class="info-card">
        <div class="card-badge-row">
          <span class="service-code">{{ service.kode || 'LAYANAN-' + service.id }}</span>
          <span class="status-pill status-active">Layanan Aktif</span>
        </div>

        <h1 class="service-title">{{ service.nama }}</h1>
        <p class="service-desc">{{ service.deskripsi }}</p>

        <div class="meta-stats">
          <div class="meta-item">
            <span class="meta-label">Estimasi Pengerjaan</span>
            <span class="meta-value">
              {{ service.estimasi_hari ? `${service.estimasi_hari} Hari Kerja` : '1-3 Hari Kerja' }}
            </span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Biaya Layanan</span>
            <span class="meta-value text-success">Gratis / Rp 0</span>
          </div>
        </div>

        <div class="req-box">
          <h3 class="box-heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
            Persyaratan & Ketentuan
          </h3>
          <ol v-if="parseReqs(service.persyaratan).length > 0" class="req-list">
            <li v-for="(item, i) in parseReqs(service.persyaratan)" :key="i">{{ item }}</li>
          </ol>
          <p v-else class="req-body">Tidak ada persyaratan khusus.</p>
        </div>
      </div>

      <!-- Right Column: Submission Form -->
      <div class="form-card">
        <h2 class="form-title">Formulir Pengajuan</h2>
        <p class="form-subtitle">Lengkapi keterangan di bawah ini untuk memulai proses pengajuan</p>

        <div v-if="!user" class="guest-notice">
          <p>Anda belum masuk ke sistem. Silakan login terlebih dahulu untuk mengajukan layanan ini.</p>
          <NuxtLink to="/login" class="btn btn-primary" style="margin-top: 0.75rem;">
            Login Mahasiswa
          </NuxtLink>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="apply-form">
          <div v-if="formError" class="alert-box alert-error">
            <span>{{ formError }}</span>
          </div>

          <div v-if="successMessage" class="alert-box alert-success">
            <span>{{ successMessage }}</span>
          </div>

          <div class="applicant-summary">
            <div class="summary-row">
              <span class="s-label">Pemohon:</span>
              <span class="s-val">{{ user.name }}</span>
            </div>
            <div class="summary-row">
              <span class="s-label">NIM:</span>
              <span class="s-val">{{ user.nim || '-' }}</span>
            </div>
            <div class="summary-row">
              <span class="s-label">Program Studi:</span>
              <span class="s-val">{{ user.prodi || '-' }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="keterangan" class="form-label">
              Keterangan / Alasan Pengajuan <span class="required">*</span>
            </label>
            <textarea
              id="keterangan"
              v-model="keterangan"
              rows="4"
              placeholder="Jelaskan kebutuhan pengajuan Anda secara rinci (misal: untuk keperluan beasiswa, magang, dll)..."
              required
              class="form-textarea"
            ></textarea>
            <span class="field-help">Berikan penjelasan lengkap agar permohonan dapat segera diverifikasi.</span>
          </div>

          <div class="form-group">
            <label for="dokumenLink" class="form-label">
              Tautan Dokumen Pendukung (Opsional)
            </label>
            <input
              id="dokumenLink"
              v-model="dokumenLink"
              type="url"
              placeholder="https://drive.google.com/... (Google Drive / Cloud storage)"
              class="form-input"
            />
            <span class="field-help">Tautan berkas PDF/Google Drive jika layanan mensyaratkan lampiran dokumen.</span>
          </div>

          <button type="submit" :disabled="submitting" class="btn btn-primary btn-block">
            <span v-if="submitting" class="spinner"></span>
            <span>{{ submitting ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan Sekarang' }}</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const router = useRouter()
const { user, fetchUser } = useAuth()
const api = useApi()

const service = ref<any>(null)
const loading = ref(true)
const errorMessage = ref('')

const keterangan = ref('')
const dokumenLink = ref('')
const submitting = ref(false)
const formError = ref('')
const successMessage = ref('')

const parseReqs = (val: any): string[] => {
  if (!val) return []
  if (Array.isArray(val)) {
    return val.map((s: string) => String(s).replace(/^\d+\.\s*/, '').trim()).filter(Boolean)
  }
  if (typeof val === 'string') {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) {
        return parsed.map((s: string) => String(s).replace(/^\d+\.\s*/, '').trim()).filter(Boolean)
      }
    } catch {
      return val.split('\n').map((s: string) => s.replace(/^\d+\.\s*/, '').trim()).filter(Boolean)
    }
    return [val.trim()]
  }
  return []
}

const loadDetail = async () => {
  loading.value = true
  errorMessage.value = ''
  const id = route.params.id as string

  try {
    await fetchUser()
    const res = await api.get(`/layanan/${id}`)
    if (res.success && res.data) {
      service.value = res.data
    } else {
      errorMessage.value = res.message || 'Layanan tidak ditemukan atau sudah tidak aktif.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat memuat detail layanan.'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  if (!keterangan.value.trim()) {
    formError.value = 'Keterangan pengajuan wajib diisi.'
    return
  }

  submitting.value = true
  formError.value = ''
  successMessage.value = ''

  try {
    const payload: any = {
      layanan_id: service.value.id,
      keterangan: keterangan.value
    }
    if (dokumenLink.value.trim()) {
      payload.dokumen_pendukung = [dokumenLink.value.trim()]
    }

    const res = await api.post('/pengajuan', payload)
    if (res.success) {
      successMessage.value = 'Pengajuan berhasil dikirim! Mengalihkan ke riwayat...'
      setTimeout(() => {
        if (res.data?.id) {
          router.push(`/pengajuan/${res.data.id}`)
        } else {
          router.push('/pengajuan')
        }
      }, 1200)
    } else {
      formError.value = res.message || 'Gagal mengirim pengajuan. Pastikan layanan masih aktif.'
    }
  } catch (err: any) {
    formError.value = err.message || 'Terjadi kesalahan jaringan saat mengirim pengajuan.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
.service-detail-page {
  padding: 1rem 0 2rem;
}

.breadcrumb {
  margin-bottom: 1.5rem;
}

.crumb-link {
  color: var(--primary-light);
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
}

.crumb-link:hover {
  text-decoration: underline;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: 2rem;
  align-items: start;
}

.info-card, .form-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.25rem;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.4);
}

.card-badge-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.service-code {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--primary-light);
  background: rgba(59, 130, 246, 0.12);
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.25);
}

.status-active {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--success);
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
}

.service-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: var(--text);
  line-height: 1.25;
  margin-bottom: 0.85rem;
}

.service-desc {
  font-size: 0.95rem;
  color: var(--text-muted);
  line-height: 1.65;
  margin-bottom: 1.75rem;
}

.meta-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.75rem;
}

.meta-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-dim);
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.meta-value {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-main);
}

.text-success {
  color: var(--success);
}

.req-box {
  background: rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 14px;
  padding: 1.25rem;
}

.box-heading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--primary-light);
  margin-bottom: 0.5rem;
}

.req-body {
  font-size: 0.88rem;
  color: var(--text-muted);
  line-height: 1.6;
}

.req-list {
  margin: 0;
  padding-left: 1.2rem;
  list-style: decimal;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.req-list li {
  font-size: 0.88rem;
  color: var(--text);
  line-height: 1.5;
}

/* Form Styles */
.form-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.35rem;
}

.form-subtitle {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.guest-notice {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
}

.applicant-summary {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 0.85rem 1rem;
  margin-bottom: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
}

.s-label {
  color: var(--text-dim);
}

.s-val {
  font-weight: 600;
  color: var(--text-main);
}

.apply-form {
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

.required {
  color: var(--danger);
}

.form-textarea, .form-input {
  width: 100%;
  padding: 0.75rem 0.9rem;
  background: var(--bg-subtle);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  color: var(--text);
  font-size: 0.88rem;
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-textarea:focus, .form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 22%, transparent);
}

.field-help {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.btn-block {
  width: 100%;
  padding: 0.8rem;
  margin-top: 0.5rem;
}

.alert-box {
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
}

.alert-error {
  background: color-mix(in srgb, var(--danger) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--danger) 30%, transparent);
  color: var(--danger);
}

.alert-success {
  background: color-mix(in srgb, var(--success) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--success) 30%, transparent);
  color: var(--success);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-large {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

.state-container {
  text-align: center;
  padding: 4rem 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 860px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
