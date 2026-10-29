<template>
  <div class="dashboard-page">
    <!-- Loading State -->
    <div v-if="loading" class="state-container">
      <div class="loader-spinner"></div>
      <p>Memuat profil dan ringkasan pengajuan...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container">
      <div class="state-icon error-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <h2>Gagal Memuat Data</h2>
      <p>{{ errorMessage }}</p>
      <button @click="loadData" class="btn btn-primary">Coba Lagi</button>
    </div>

    <!-- Main Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Welcome Hero Banner -->
      <section class="welcome-card">
        <div class="welcome-left">
          <div class="user-badge">Mahasiswa Aktif</div>
          <h1 class="welcome-title">Halo, {{ user?.name || 'Mahasiswa' }}</h1>
          <p class="welcome-desc">
            NIM: <strong>{{ user?.nim || '-' }}</strong> • Program Studi: <strong>{{ user?.prodi || 'Akademik' }}</strong>
          </p>
          <div class="quick-cta">
            <NuxtLink to="/layanan" class="btn btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
              Ajukan Layanan Baru
            </NuxtLink>
            <NuxtLink to="/pengajuan" class="btn btn-secondary">
              Lihat Semua Pengajuan
            </NuxtLink>
          </div>
        </div>

        <div class="welcome-stats">
          <div class="stat-pill">
            <span class="stat-label">Total Diajukan</span>
            <span class="stat-value">{{ stats.total }}</span>
          </div>
          <div class="stat-pill">
            <span class="stat-label">Sedang Diproses</span>
            <span class="stat-value text-info">{{ stats.inProgress }}</span>
          </div>
          <div class="stat-pill" :class="{ 'stat-highlight-warn': stats.needRevision > 0 }">
            <span class="stat-label">Perlu Perbaikan</span>
            <span class="stat-value text-warning">{{ stats.needRevision }}</span>
          </div>
          <div class="stat-pill">
            <span class="stat-label">Disetujui / Selesai</span>
            <span class="stat-value text-success">{{ stats.approved }}</span>
          </div>
        </div>
      </section>

      <!-- Warning Alert if any need revision -->
      <div v-if="stats.needRevision > 0" class="action-alert">
        <div class="alert-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="alert-body">
          <h4>Perhatian: Ada Pengajuan Perlu Perbaikan</h4>
          <p>Terdapat {{ stats.needRevision }} pengajuan yang membutuhkan perbaikan dokumen atau catatan dari admin. Silakan periksa detailnya.</p>
        </div>
        <NuxtLink to="/pengajuan" class="btn btn-warning-action">Periksa Sekarang</NuxtLink>
      </div>

      <!-- Recent Pengajuan Section -->
      <section class="recent-section">
        <div class="section-header">
          <div class="header-left">
            <h2>Pengajuan Terbaru</h2>
            <p>Daftar berkas permohonan layanan yang baru saja Anda ajukan</p>
          </div>
          <NuxtLink to="/pengajuan" class="header-link">Lihat Semua →</NuxtLink>
        </div>

        <div v-if="recentPengajuan.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
          </div>
          <h3>Belum Ada Pengajuan Layanan</h3>
          <p>Anda belum pernah mengajukan layanan akademik. Pilih layanan yang Anda butuhkan sekarang.</p>
          <NuxtLink to="/layanan" class="btn btn-primary" style="margin-top: 1rem;">
            Buka Katalog Layanan
          </NuxtLink>
        </div>

        <div v-else class="pengajuan-grid">
          <div v-for="item in recentPengajuan" :key="item.id" class="pengajuan-card">
            <div class="card-top">
              <span class="ticket-code">{{ item.nomor_pengajuan }}</span>
              <span class="status-badge" :class="getStatusClass(item.status)">
                {{ formatStatus(item.status) }}
              </span>
            </div>

            <h3 class="card-service-title">
              {{ item.layanan?.nama || 'Layanan Akademik #' + item.layanan_id }}
            </h3>

            <p class="card-date">
              Diajukan: {{ formatDate(item.tanggal_pengajuan || item.created_at) }}
            </p>

            <div v-if="item.catatan_admin" class="admin-note-preview">
              <span class="note-label">Catatan Admin:</span>
              <p class="note-text">{{ item.catatan_admin }}</p>
            </div>

            <div class="card-actions">
              <NuxtLink :to="`/pengajuan/${item.id}`" class="btn-card-detail">
                Lihat Detail & Riwayat →
              </NuxtLink>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const router = useRouter()
const { user, fetchUser, isAuthenticated } = useAuth()
const api = useApi()

const loading = ref(true)
const errorMessage = ref('')
const recentPengajuan = ref<any[]>([])

const stats = reactive({
  total: 0,
  inProgress: 0,
  needRevision: 0,
  approved: 0
})

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isAuthenticated()) {
      router.push('/login')
      return
    }

    const currentUser = await fetchUser()
    if (!currentUser) {
      router.push('/login')
      return
    }

    // Load recent pengajuan
    const res = await api.get('/pengajuan/mine?per_page=6')
    if (res.success && res.data) {
      const list = Array.isArray(res.data) ? res.data : []
      recentPengajuan.value = list

      stats.total = res.meta?.total ?? list.length
      stats.inProgress = list.filter((i: any) => i.status === 'diajukan' || i.status === 'diproses').length
      stats.needRevision = list.filter((i: any) => i.status === 'perlu_perbaikan').length
      stats.approved = list.filter((i: any) => i.status === 'disetujui' || i.status === 'selesai').length
    } else {
      errorMessage.value = res.message || 'Gagal memuat daftar riwayat pengajuan.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan sistem saat memuat data.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

const formatStatus = (st: string) => {
  switch (st) {
    case 'diajukan': return 'Diajukan'
    case 'diproses': return 'Sedang Diproses'
    case 'perlu_perbaikan': return 'Perlu Perbaikan'
    case 'disetujui':
    case 'selesai': return 'Disetujui / Selesai'
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

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return dateStr
  }
}
</script>

<style scoped>
.dashboard-page {
  padding: 1rem 0;
}

.state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 1rem;
  text-align: center;
}

.loader-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1.25rem;
}

.welcome-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  flex-wrap: wrap;
  box-shadow: 0 16px 32px -10px rgba(0, 0, 0, 0.35);
  margin-bottom: 2rem;
}

.user-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--success);
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  margin-bottom: 0.75rem;
}

.welcome-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.5rem;
}

.welcome-desc {
  font-size: 0.95rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.quick-cta {
  display: flex;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.welcome-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  min-width: 320px;
}

.stat-pill {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
}

.stat-highlight-warn {
  border-color: rgba(245, 158, 11, 0.4);
  background: rgba(245, 158, 11, 0.08);
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.35rem;
}

.stat-value {
  font-size: 1.85rem;
  font-weight: 800;
  color: var(--text-main);
  line-height: 1;
}

.text-info { color: var(--info); }
.text-warning { color: var(--warning); }
.text-success { color: var(--success); }

.action-alert {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.alert-icon {
  font-size: 1.75rem;
}

.alert-body {
  flex: 1;
}

.alert-body h4 {
  font-size: 1rem;
  font-weight: 700;
  color: #fbbf24;
  margin-bottom: 0.25rem;
}

.alert-body p {
  font-size: 0.875rem;
  color: #fef3c7;
}

.btn-warning-action {
  background: #f59e0b;
  color: #000;
  padding: 0.55rem 1.1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-warning-action:hover {
  background: #d97706;
}

.recent-section {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2rem;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.section-header h2 {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text);
}

.section-header p {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.header-link {
  color: var(--primary-light);
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
}

.header-link:hover {
  text-decoration: underline;
}

.pengajuan-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.pengajuan-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 1.35rem;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
}

.pengajuan-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.ticket-code {
  font-family: monospace;
  font-size: 0.78rem;
  color: var(--text-dim);
  background: rgba(255, 255, 255, 0.05);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
}

.card-service-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.4rem;
}

.card-date {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.admin-note-preview {
  background: rgba(245, 158, 11, 0.1);
  border-left: 3px solid var(--warning);
  padding: 0.5rem 0.75rem;
  border-radius: 0 6px 6px 0;
  margin-bottom: 1rem;
}

.note-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #fbbf24;
  display: block;
}

.note-text {
  font-size: 0.8rem;
  color: #fde68a;
}

.card-actions {
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border-subtle);
}

.btn-card-detail {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--primary-light);
  text-decoration: none;
}

.btn-card-detail:hover {
  text-decoration: underline;
}

/* Status Badges */
.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  text-transform: capitalize;
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

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .welcome-card {
    padding: 1.5rem;
  }
  .welcome-stats {
    width: 100%;
    min-width: unset;
  }
}
</style>
