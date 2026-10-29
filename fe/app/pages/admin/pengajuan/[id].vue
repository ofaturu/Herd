<template>
  <div class="admin-detail-page">
    <div class="breadcrumb">
      <NuxtLink to="/admin/pengajuan" class="crumb-link">← Kembali ke Daftar Pengajuan</NuxtLink>
    </div>

    <!-- Alert Notifications -->
    <div v-if="successToast" class="alert-banner alert-success">
      <span>{{ successToast }}</span>
      <button @click="successToast = ''" class="btn-close-toast">✕</button>
    </div>

    <div v-if="errorToast" class="alert-banner alert-error">
      <span>{{ errorToast }}</span>
      <button @click="errorToast = ''" class="btn-close-toast">✕</button>
    </div>

    <!-- Access Denied -->
    <div v-if="accessDenied" class="state-container access-denied-box">
      <div class="state-icon">⛔</div>
      <h2>Akses Ditolak (403 Forbidden)</h2>
      <p>Hanya akun Administrator yang memiliki wewenang untuk meninjau detail pengajuan ini.</p>
      <NuxtLink to="/mahasiswa" class="btn btn-primary" style="margin-top: 1rem;">Kembali</NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat rincian pengajuan mahasiswa...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>⚠️ {{ errorMessage }}</p>
      <NuxtLink to="/admin/pengajuan" class="btn btn-secondary">Kembali</NuxtLink>
    </div>

    <!-- Detail Layout -->
    <div v-else-if="pengajuan" class="detail-layout">
      <!-- Left Column: Details & Action -->
      <div class="left-col">
        <!-- Main Application Card -->
        <div class="card detail-card">
          <div class="card-header-bar">
            <div>
              <span class="ticket-tag">{{ pengajuan.nomor_pengajuan }}</span>
              <h1 class="service-title">{{ pengajuan.layanan?.nama || 'Layanan #' + pengajuan.layanan_id }}</h1>
            </div>
            <span class="status-badge-lg" :class="getStatusClass(pengajuan.status)">
              {{ formatStatus(pengajuan.status) }}
            </span>
          </div>

          <!-- Student Profile Section -->
          <div class="section-block">
            <h3 class="section-heading">Data Pemohon (Mahasiswa)</h3>
            <div class="profile-grid">
              <div class="prof-item">
                <span class="prof-label">Nama Lengkap</span>
                <span class="prof-value">{{ pengajuan.user?.name || '-' }}</span>
              </div>
              <div class="prof-item">
                <span class="prof-label">NIM</span>
                <span class="prof-value font-mono">{{ pengajuan.user?.nim || '-' }}</span>
              </div>
              <div class="prof-item">
                <span class="prof-label">Program Studi</span>
                <span class="prof-value">{{ pengajuan.user?.prodi || '-' }}</span>
              </div>
              <div class="prof-item">
                <span class="prof-label">Email Mahasiswa</span>
                <span class="prof-value">{{ pengajuan.user?.email || '-' }}</span>
              </div>
              <div class="prof-item">
                <span class="prof-label">Nomor WhatsApp / HP</span>
                <span class="prof-value">{{ pengajuan.user?.no_hp || '-' }}</span>
              </div>
              <div class="prof-item">
                <span class="prof-label">Tanggal Masuk</span>
                <span class="prof-value">{{ formatDate(pengajuan.tanggal_pengajuan || pengajuan.created_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Application Reason / Note -->
          <div class="section-block">
            <h3 class="section-heading">Keterangan / Alasan Pengajuan</h3>
            <div class="reason-box">
              <p>{{ pengajuan.keterangan || 'Tidak ada keterangan tambahan dari mahasiswa.' }}</p>
            </div>
          </div>

          <!-- Supporting Documents -->
          <div v-if="pengajuan.dokumen_pendukung && pengajuan.dokumen_pendukung.length > 0" class="section-block">
            <h3 class="section-heading">Dokumen / Tautan Lampiran Pendukung</h3>
            <ul class="doc-list">
              <li v-for="(doc, idx) in pengajuan.dokumen_pendukung" :key="idx">
                <a :href="doc" target="_blank" rel="noopener noreferrer" class="doc-link">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  <span>Buka Berkas Lampiran {{ idx + 1 }}</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- Existing Admin Note -->
          <div v-if="pengajuan.catatan_admin" class="section-block">
            <h3 class="section-heading">Catatan Admin Terkini</h3>
            <div class="admin-current-note">
              <p>{{ pengajuan.catatan_admin }}</p>
            </div>
          </div>
        </div>

        <!-- Action Card: Update Status & Notes -->
        <div class="card action-card">
          <h2 class="action-card-title">Tindakan Verifikasi & Perubahan Status</h2>
          <p class="action-card-subtitle">Pilih status baru dan sertakan catatan instruksi untuk mahasiswa jika diperlukan</p>

          <form @submit.prevent="submitStatusChange" class="action-form">
            <div class="status-options">
              <label
                class="status-radio-label"
                :class="{ 'status-selected': formStatus === 'diproses' }"
              >
                <input type="radio" v-model="formStatus" value="diproses" class="sr-only" />
                <span class="radio-badge point-diproses">⚙️ Diproses</span>
                <span class="radio-desc">Berkas lengkap, sedang dalam pengerjaan</span>
              </label>

              <label
                class="status-radio-label"
                :class="{ 'status-selected': formStatus === 'perlu_perbaikan' }"
              >
                <input type="radio" v-model="formStatus" value="perlu_perbaikan" class="sr-only" />
                <span class="radio-badge point-perbaikan">⚠️ Perlu Perbaikan</span>
                <span class="radio-desc">Ada syarat yang kurang/salah, minta revisi</span>
              </label>

              <label
                class="status-radio-label"
                :class="{ 'status-selected': formStatus === 'disetujui' }"
              >
                <input type="radio" v-model="formStatus" value="disetujui" class="sr-only" />
                <span class="radio-badge point-selesai">✅ Disetujui</span>
                <span class="radio-desc">Permohonan selesai dan dokumen diterbitkan</span>
              </label>

              <label
                class="status-radio-label"
                :class="{ 'status-selected': formStatus === 'ditolak' }"
              >
                <input type="radio" v-model="formStatus" value="ditolak" class="sr-only" />
                <span class="radio-badge point-ditolak">❌ Ditolak</span>
                <span class="radio-desc">Permohonan tidak memenuhi syarat</span>
              </label>
            </div>

            <div class="form-group" style="margin-top: 1.25rem;">
              <label for="catatan" class="form-label">
                Catatan Petugas Admin
                <span v-if="formStatus === 'perlu_perbaikan' || formStatus === 'ditolak'" class="required">* (Wajib diisi untuk perbaikan/penolakan)</span>
              </label>
              <textarea
                id="catatan"
                v-model="catatan"
                rows="3"
                placeholder="Tuliskan catatan, instruksi berkas yang kurang, atau alasan keputusan..."
                class="form-textarea"
                :required="formStatus === 'perlu_perbaikan' || formStatus === 'ditolak'"
              ></textarea>
            </div>

            <button type="submit" :disabled="submitting || formStatus === pengajuan.status" class="btn btn-primary btn-block">
              <span v-if="submitting" class="spinner"></span>
              <span>{{ submitting ? 'Memperbarui Status...' : `Simpan Status: ${formatStatus(formStatus)}` }}</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column: Audit Timeline -->
      <div class="right-col">
        <div class="card timeline-card">
          <h3 class="timeline-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Riwayat Audit Perubahan Status
          </h3>

          <div v-if="!pengajuan.riwayat_status || pengajuan.riwayat_status.length === 0" class="timeline-empty">
            <p>Belum ada riwayat perubahan status.</p>
          </div>

          <div v-else class="timeline-list">
            <div
              v-for="(hist, idx) in pengajuan.riwayat_status"
              :key="idx"
              class="timeline-item"
            >
              <div class="timeline-point" :class="getStatusPointClass(hist.status_baru)"></div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="hist-status" :class="getStatusClass(hist.status_baru)">
                    {{ formatStatus(hist.status_baru) }}
                  </span>
                  <span class="hist-time">{{ formatDateTime(hist.waktu || hist.created_at) }}</span>
                </div>
                <p v-if="hist.status_sebelumnya" class="hist-prev">
                  Sebelumnya: {{ formatStatus(hist.status_sebelumnya) }}
                </p>
                <p v-if="hist.catatan" class="hist-note">
                  "{{ hist.catatan }}"
                </p>
                <span class="hist-actor">
                  Oleh: <strong>{{ hist.user?.name || 'Administrator' }}</strong>
                </span>
              </div>
            </div>
          </div>
        </div>
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
const { fetchUser, isAuthenticated } = useAuth()
const api = useApi()

const pengajuan = ref<any>(null)
const loading = ref(true)
const accessDenied = ref(false)
const errorMessage = ref('')

const formStatus = ref('diproses')
const catatan = ref('')
const submitting = ref(false)
const successToast = ref('')
const errorToast = ref('')

const loadDetail = async () => {
  loading.value = true
  errorMessage.value = ''
  accessDenied.value = false

  try {
    if (!isAuthenticated()) {
      router.push('/login')
      return
    }

    const user = await fetchUser()
    if (!user || user.role !== 'admin') {
      accessDenied.value = true
      loading.value = false
      return
    }

    const id = route.params.id as string
    const res = await api.get(`/pengajuan/${id}`)
    if (res.success && res.data) {
      pengajuan.value = res.data
      formStatus.value = res.data.status || 'diproses'
      catatan.value = res.data.catatan_admin || ''
    } else {
      errorMessage.value = res.message || 'Pengajuan tidak ditemukan.'
    }
  } catch (err: any) {
    if (err.status === 403) {
      accessDenied.value = true
    } else {
      errorMessage.value = err.message || 'Terjadi kesalahan sistem saat memuat data.'
    }
  } finally {
    loading.value = false
  }
}

const submitStatusChange = async () => {
  if (formStatus.value === pengajuan.value.status && !catatan.value.trim()) {
    return
  }

  // Confirmation dialog
  const promptMessage = `Apakah Anda yakin ingin mengubah status pengajuan ini menjadi "${formatStatus(formStatus.value)}"?`
  if (!confirm(promptMessage)) {
    return
  }

  submitting.value = true
  successToast.value = ''
  errorToast.value = ''

  try {
    const id = pengajuan.value.id
    const payload = {
      status_baru: formStatus.value,
      catatan: catatan.value.trim() || null
    }

    const res = await api.post(`/pengajuan/${id}/status`, payload)
    if (res.success) {
      successToast.value = `Status pengajuan berhasil diubah menjadi "${formatStatus(formStatus.value)}".`
      await loadDetail()
    } else {
      errorToast.value = res.message || 'Gagal mengubah status pengajuan.'
    }
  } catch (err: any) {
    errorToast.value = err.message || 'Terjadi kesalahan jaringan saat memperbarui status.'
  } finally {
    submitting.value = false
  }
}

const formatStatus = (st: string) => {
  switch (st) {
    case 'diajukan': return 'Diajukan'
    case 'diproses': return 'Diproses'
    case 'perlu_perbaikan': return 'Perlu Perbaikan'
    case 'disetujui':
    case 'selesai': return 'Disetujui'
    case 'ditolak': return 'Ditolak'
    default: return st
  }
}

const getStatusClass = (st: string) => {
  switch (st) {
    case 'diajukan': return 'badge-diajukan'
    case 'diproses': return 'badge-diproses'
    case 'perlu_perbaikan': return 'badge-perbaikan'
    case 'disetujui':
    case 'selesai': return 'badge-selesai'
    case 'ditolak': return 'badge-ditolak'
    default: return 'badge-default'
  }
}

const getStatusPointClass = (st: string) => {
  switch (st) {
    case 'diajukan': return 'point-diajukan'
    case 'diproses': return 'point-diproses'
    case 'perlu_perbaikan': return 'point-perbaikan'
    case 'disetujui':
    case 'selesai': return 'point-selesai'
    case 'ditolak': return 'point-ditolak'
    default: return 'point-default'
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
  } catch {
    return dateStr
  }
}

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateStr
  }
}

onMounted(() => {
  loadDetail()
})
</script>

<style scoped>
.admin-detail-page {
  padding: 1rem 0 3rem;
}

.breadcrumb {
  margin-bottom: 1.5rem;
}

.crumb-link {
  color: var(--primary-light);
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
}

.crumb-link:hover {
  text-decoration: underline;
}

.alert-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.alert-success {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #6ee7b7;
}

.alert-error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #fca5a5;
}

.btn-close-toast {
  background: transparent;
  border: none;
  color: inherit;
  font-size: 1rem;
  cursor: pointer;
}

.detail-layout {
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 2rem;
  align-items: start;
}

.card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.25rem;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.4);
}

.left-col {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.card-header-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-subtle);
  margin-bottom: 1.75rem;
}

.ticket-tag {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--primary-light);
  background: rgba(59, 130, 246, 0.1);
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.25);
  display: inline-block;
  margin-bottom: 0.5rem;
}

.service-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text);
  line-height: 1.25;
}

.status-badge-lg {
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.4rem 0.9rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.section-block {
  margin-bottom: 1.75rem;
}

.section-heading {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.85rem;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 1.25rem;
}

.prof-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.prof-label {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.prof-value {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-main);
}

.font-mono {
  font-family: monospace;
}

.reason-box {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 1rem;
  font-size: 0.92rem;
  color: var(--text-muted);
  line-height: 1.6;
}

.doc-list {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.doc-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.88rem;
  color: var(--primary-light);
  text-decoration: none;
  background: rgba(59, 130, 246, 0.08);
  border: 1px solid rgba(59, 130, 246, 0.2);
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.doc-link:hover {
  background: rgba(59, 130, 246, 0.16);
}

.admin-current-note {
  background: rgba(245, 158, 11, 0.1);
  border-left: 3px solid var(--warning);
  padding: 0.85rem 1rem;
  border-radius: 0 8px 8px 0;
  color: #fde68a;
  font-size: 0.88rem;
}

/* Action Card */
.action-card {
  border-color: rgba(59, 130, 246, 0.35);
  background: rgba(19, 27, 46, 0.95);
}

.action-card-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.35rem;
}

.action-card-subtitle {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.status-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem;
}

.status-radio-label {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 0.9rem;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  transition: all 0.2s ease;
}

.status-radio-label:hover {
  background: rgba(255, 255, 255, 0.06);
}

.status-selected {
  border-color: var(--primary) !important;
  background: rgba(37, 99, 235, 0.15) !important;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3);
}

.radio-badge {
  font-size: 0.82rem;
  font-weight: 700;
}

.radio-desc {
  font-size: 0.72rem;
  color: var(--text-muted);
  line-height: 1.35;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-main);
}

.required {
  color: #f87171;
  font-size: 0.75rem;
}

.form-textarea {
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

.form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

.btn-block {
  width: 100%;
  padding: 0.8rem;
  margin-top: 1.25rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

/* Timeline */
.timeline-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 1.5rem;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--border-subtle);
}

.timeline-list {
  display: flex;
  flex-direction: column;
  position: relative;
  padding-left: 1.25rem;
}

.timeline-list::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 10px;
  bottom: 10px;
  width: 2px;
  background: var(--border-color);
}

.timeline-item {
  position: relative;
  padding-bottom: 1.75rem;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-point {
  position: absolute;
  left: -1.25rem;
  top: 4px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid var(--bg-base);
  box-shadow: 0 0 0 2px var(--border-color);
}

.point-diajukan { background: #06b6d4; }
.point-diproses { background: #3b82f6; }
.point-perbaikan { background: #f59e0b; }
.point-selesai { background: #10b981; }
.point-ditolak { background: #ef4444; }

.timeline-content {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 0.85rem 1rem;
}

.timeline-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.35rem;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.hist-status {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.hist-time {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.hist-prev {
  font-size: 0.78rem;
  color: var(--text-dim);
  margin-bottom: 0.35rem;
}

.hist-note {
  font-size: 0.82rem;
  color: #fde047;
  font-style: italic;
  margin-bottom: 0.35rem;
}

.hist-actor {
  font-size: 0.72rem;
  color: var(--text-dim);
  display: block;
}

/* Status Badges */
.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  display: inline-block;
}

.badge-diajukan {
  background: rgba(6, 182, 212, 0.15);
  color: #67e8f9;
  border: 1px solid rgba(6, 182, 212, 0.3);
}

.badge-diproses {
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.badge-perbaikan {
  background: rgba(245, 158, 11, 0.2);
  color: #fde047;
  border: 1px solid rgba(245, 158, 11, 0.4);
}

.badge-selesai {
  background: rgba(16, 185, 129, 0.15);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge-ditolak {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
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
  .detail-layout {
    grid-template-columns: 1fr;
  }
  .status-options {
    grid-template-columns: 1fr;
  }
}
</style>
