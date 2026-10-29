<template>
  <div class="submission-detail-page">
    <div class="breadcrumb">
      <NuxtLink to="/pengajuan" class="crumb-link">← Kembali ke Riwayat Pengajuan</NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat rincian berkas pengajuan...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>⚠️ {{ errorMessage }}</p>
      <NuxtLink to="/pengajuan" class="btn btn-secondary">Kembali ke Riwayat</NuxtLink>
    </div>

    <!-- Detail Content -->
    <div v-else-if="pengajuan" class="detail-container">
      <!-- Revision Warning Banner (if status is perlu_perbaikan) -->
      <div v-if="pengajuan.status === 'perlu_perbaikan'" class="revision-banner">
        <div class="rev-icon">⚠️</div>
        <div class="rev-content">
          <h3>Pengajuan Memerlukan Perbaikan / Revisi</h3>
          <p class="rev-desc">
            Petugas akademik telah meninjau berkas Anda dan meminta perbaikan berikut sebelum pengajuan dapat disetujui:
          </p>
          <div class="rev-note-box">
            <strong>Instruksi Petugas:</strong>
            <p>{{ pengajuan.catatan_admin || 'Silakan hubungi BAAK untuk kelengkapan berkas Anda.' }}</p>
          </div>
        </div>
      </div>

      <div class="layout-grid">
        <!-- Main Info Card -->
        <div class="main-card">
          <div class="card-header-bar">
            <div>
              <span class="ticket-tag">{{ pengajuan.nomor_pengajuan }}</span>
              <h1 class="service-heading">{{ pengajuan.layanan?.nama || 'Pengajuan Layanan' }}</h1>
            </div>
            <span class="status-badge-lg" :class="getStatusClass(pengajuan.status)">
              {{ formatStatus(pengajuan.status) }}
            </span>
          </div>

          <div class="info-section">
            <h4 class="section-title">Informasi Pengajuan</h4>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Tanggal Pengajuan</span>
                <span class="info-value">{{ formatDate(pengajuan.tanggal_pengajuan || pengajuan.created_at) }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Kode Layanan</span>
                <span class="info-value">{{ pengajuan.layanan?.kode || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Estimasi Pengerjaan</span>
                <span class="info-value">{{ pengajuan.layanan?.estimasi_hari ? `${pengajuan.layanan.estimasi_hari} Hari Kerja` : '1-3 Hari Kerja' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Terakhir Diperbarui</span>
                <span class="info-value">{{ formatDate(pengajuan.updated_at) }}</span>
              </div>
            </div>
          </div>

          <div class="info-section">
            <h4 class="section-title">Keterangan / Alasan Pemohon</h4>
            <p class="reason-text">{{ pengajuan.keterangan || 'Tidak ada keterangan tambahan.' }}</p>
          </div>

          <div v-if="Array.isArray(pengajuan.dokumen_pendukung) && pengajuan.dokumen_pendukung.length > 0" class="info-section">
            <h4 class="section-title">Dokumen Pendukung yang Dilampirkan</h4>
            <ul class="doc-list">
              <li v-for="(doc, idx) in pengajuan.dokumen_pendukung" :key="idx">
                <a
                  :href="String(doc).startsWith('http') ? String(doc) : `${storageBaseUrl}/${String(doc)}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="doc-link"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  <span>{{ String(doc).split('/').pop() || `Lampiran ${Number(idx) + 1}` }}</span>
                </a>
              </li>
            </ul>
          </div>

          <div v-if="pengajuan.catatan_admin && pengajuan.status !== 'perlu_perbaikan'" class="info-section">
            <h4 class="section-title">Catatan Petugas Layanan</h4>
            <div class="admin-note-box">
              <p>{{ pengajuan.catatan_admin }}</p>
            </div>
          </div>
        </div>

        <!-- Right Side: Timeline History -->
        <div class="timeline-card">
          <h3 class="timeline-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Riwayat Perubahan Status
          </h3>

          <div v-if="!pengajuan.riwayat_status || pengajuan.riwayat_status.length === 0" class="timeline-empty">
            <p>Belum ada perubahan status riwayat.</p>
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
                <span v-if="hist.user?.name" class="hist-actor">
                  Oleh: {{ hist.user.name }}
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
import { useRoute, useRouter, useRuntimeConfig } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const router = useRouter()
const { isAuthenticated, fetchUser } = useAuth()
const api = useApi()

const config = useRuntimeConfig()
const storageBaseUrl = `${(config.public.apiBaseUrl as string || 'http://localhost:8000').replace(/\/$/, '')}/storage`

const pengajuan = ref<any>(null)
const loading = ref(true)
const errorMessage = ref('')

const loadDetail = async () => {
  loading.value = true
  errorMessage.value = ''
  const id = route.params.id as string

  try {
    if (!isAuthenticated()) {
      router.push('/login')
      return
    }
    await fetchUser()

    const res = await api.get(`/pengajuan/mine/${id}`)
    if (res.success && res.data) {
      pengajuan.value = res.data
    } else {
      errorMessage.value = res.message || 'Pengajuan tidak ditemukan atau Anda tidak memiliki akses.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan sistem saat memuat berkas pengajuan.'
  } finally {
    loading.value = false
  }
}

const formatStatus = (st: string) => {
  switch (st) {
    case 'diajukan': return 'Diajukan'
    case 'diproses': return 'Sedang Diproses'
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
.submission-detail-page {
  padding: 1rem 0 3rem;
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

.revision-banner {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.4);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  gap: 1.25rem;
  margin-bottom: 2rem;
  align-items: flex-start;
}

.rev-icon {
  font-size: 2rem;
  line-height: 1;
}

.rev-content h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--alert-warning-title);
  margin-bottom: 0.35rem;
}

.rev-desc {
  font-size: 0.9rem;
  color: var(--alert-warning-text);
  margin-bottom: 0.85rem;
}

.rev-note-box {
  background: var(--alert-warning-box);
  border-left: 3px solid var(--warning);
  border-radius: 0 8px 8px 0;
  padding: 0.75rem 1rem;
}

.rev-note-box strong {
  display: block;
  font-size: 0.78rem;
  color: var(--alert-warning-title);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.25rem;
}

.rev-note-box p {
  font-size: 0.9rem;
  color: var(--text);
}

.layout-grid {
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 2rem;
  align-items: start;
}

.main-card, .timeline-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.25rem;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.4);
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

.service-heading {
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

.info-section {
  margin-bottom: 1.75rem;
}

.section-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 1.1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.info-value {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-main);
}

.reason-text {
  font-size: 0.92rem;
  color: var(--text-muted);
  line-height: 1.6;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 1rem;
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

.admin-note-box {
  background: rgba(255, 255, 255, 0.03);
  border-left: 3px solid var(--primary);
  border-radius: 0 8px 8px 0;
  padding: 0.85rem 1rem;
  font-size: 0.9rem;
  color: var(--text-main);
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

.point-diajukan { background: var(--info); }
.point-diproses { background: var(--primary); }
.point-perbaikan { background: var(--warning); }
.point-selesai { background: var(--success); }
.point-ditolak { background: var(--danger); }

.timeline-content {
  background: var(--bg-subtle);
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
  color: var(--alert-warning-title);
  font-style: italic;
  margin-bottom: 0.35rem;
}

.hist-actor {
  font-size: 0.72rem;
  color: var(--text-dim);
  display: block;
}

/* Status Badges */
.badge-diajukan {
  background: var(--badge-diajukan-bg);
  color: var(--badge-diajukan-text);
  border: 1px solid var(--badge-diajukan-border);
}

.badge-diproses {
  background: var(--badge-diproses-bg);
  color: var(--badge-diproses-text);
  border: 1px solid var(--badge-diproses-border);
}

.badge-perbaikan {
  background: var(--badge-perbaikan-bg);
  color: var(--badge-perbaikan-text);
  border: 1px solid var(--badge-perbaikan-border);
}

.badge-selesai,
.badge-disetujui {
  background: var(--badge-selesai-bg);
  color: var(--badge-selesai-text);
  border: 1px solid var(--badge-selesai-border);
}

.badge-ditolak {
  background: var(--badge-ditolak-bg);
  color: var(--badge-ditolak-text);
  border: 1px solid var(--badge-ditolak-border);
}

.state-container {
  text-align: center;
  padding: 4rem 1rem;
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 860px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }
}
</style>
