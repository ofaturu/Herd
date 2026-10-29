<template>
  <div class="admin-dashboard-page">
    <!-- Loading State -->
    <div v-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat ringkasan data admin...</p>
    </div>

    <!-- Access Denied State (Role not admin) -->
    <div v-else-if="accessDenied" class="state-container access-denied-box">
      <div class="state-icon">⛔</div>
      <h2>Akses Ditolak (403 Forbidden)</h2>
      <p>Halaman ini khusus diperuntukkan bagi Administrator Layanan. Akun Anda saat ini terdaftar sebagai Mahasiswa.</p>
      <div class="denied-actions">
        <NuxtLink to="/mahasiswa" class="btn btn-primary">Kembali ke Dashboard Mahasiswa</NuxtLink>
        <button @click="logout" class="btn btn-secondary">Logout</button>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>⚠️ {{ errorMessage }}</p>
      <button @click="loadDashboard" class="btn btn-secondary">Coba Muat Ulang</button>
    </div>

    <!-- Main Admin Dashboard Content -->
    <div v-else class="admin-content">
      <!-- Admin Hero Card -->
      <section class="admin-hero">
        <div class="hero-left">
          <div class="admin-badge">Panel Administrator</div>
          <h1 class="hero-title">Pusat Kendali Layanan Akademik</h1>
          <p class="hero-subtitle">
            Selamat datang, <strong>{{ user?.name }}</strong> ({{ user?.email }}). Pantau permohonan berkas dan kelola layanan akademik mahasiswa.
          </p>

          <div class="hero-shortcuts">
            <NuxtLink to="/admin/pengajuan" class="btn btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
              <span>Verifikasi Pengajuan ({{ stats.diajukan + stats.diproses }})</span>
            </NuxtLink>
            <NuxtLink to="/admin/layanan" class="btn btn-secondary">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              <span>Kelola Master Layanan</span>
            </NuxtLink>
          </div>
        </div>

        <div class="hero-quick-status">
          <div class="status-summary-item">
            <span class="sum-label">Total Layanan</span>
            <span class="sum-val">{{ stats.total_layanan }} ({{ stats.layanan_aktif }} Aktif)</span>
          </div>
          <div class="status-summary-item">
            <span class="sum-label">Total Mahasiswa</span>
            <span class="sum-val">{{ stats.total_mahasiswa }} Akun</span>
          </div>
        </div>
      </section>

      <!-- Key Statistics Grid -->
      <section class="stats-overview-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-name">Total Pengajuan</span>
            <span class="stat-icon">📑</span>
          </div>
          <div class="stat-number">{{ stats.total_pengajuan }}</div>
          <span class="stat-meta">Seluruh berkas masuk</span>
        </div>

        <div class="stat-card card-warning" :class="{ 'card-pulse': stats.diajukan > 0 }">
          <div class="stat-header">
            <span class="stat-name">Menunggu Diproses</span>
            <span class="stat-icon">⏳</span>
          </div>
          <div class="stat-number text-warning">{{ stats.diajukan }}</div>
          <span class="stat-meta">Butuh verifikasi awal</span>
        </div>

        <div class="stat-card card-info">
          <div class="stat-header">
            <span class="stat-name">Sedang Diproses</span>
            <span class="stat-icon">⚙️</span>
          </div>
          <div class="stat-number text-info">{{ stats.diproses }}</div>
          <span class="stat-meta">Dalam pengerjaan</span>
        </div>

        <div class="stat-card card-revision">
          <div class="stat-header">
            <span class="stat-name">Perlu Perbaikan</span>
            <span class="stat-icon">✍️</span>
          </div>
          <div class="stat-number text-revision">{{ stats.perlu_perbaikan }}</div>
          <span class="stat-meta">Menunggu revisi mhs</span>
        </div>

        <div class="stat-card card-success">
          <div class="stat-header">
            <span class="stat-name">Disetujui / Selesai</span>
            <span class="stat-icon">✅</span>
          </div>
          <div class="stat-number text-success">{{ stats.disetujui }}</div>
          <span class="stat-meta">Telah disahkan</span>
        </div>

        <div class="stat-card card-danger">
          <div class="stat-header">
            <span class="stat-name">Ditolak</span>
            <span class="stat-icon">❌</span>
          </div>
          <div class="stat-number text-danger">{{ stats.ditolak }}</div>
          <span class="stat-meta">Permohonan ditolak</span>
        </div>
      </section>

      <!-- Recent Pending Submissions for Review -->
      <section class="pending-section">
        <div class="section-title-row">
          <div>
            <h2>Pengajuan Perlu Ditinjau Segera</h2>
            <p>Daftar permohonan dengan status "Diajukan" atau "Diproses" yang memerlukan tindakan</p>
          </div>
          <NuxtLink to="/admin/pengajuan" class="btn btn-secondary btn-sm">
            Buka Semua Pengajuan →
          </NuxtLink>
        </div>

        <div v-if="pendingList.length === 0" class="empty-pending">
          <div class="empty-icon">🎉</div>
          <h3>Semua Pengajuan Telah Ditinjau!</h3>
          <p>Tidak ada pengajuan yang tertunda saat ini. Kerja bagus!</p>
        </div>

        <div v-else class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nomor Tiket</th>
                <th>Mahasiswa</th>
                <th>Layanan</th>
                <th>Tanggal Masuk</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in pendingList" :key="item.id">
                <td>
                  <span class="ticket-code">{{ item.nomor_pengajuan }}</span>
                </td>
                <td>
                  <div class="student-info-col">
                    <span class="st-name">{{ item.user?.name || 'Mahasiswa #' + item.user_id }}</span>
                    <span class="st-nim">NIM: {{ item.user?.nim || '-' }} • {{ item.user?.prodi || '-' }}</span>
                  </div>
                </td>
                <td>
                  <span class="service-name-text">{{ item.layanan?.nama || 'Layanan #' + item.layanan_id }}</span>
                </td>
                <td>
                  <span class="date-col">{{ formatDate(item.tanggal_pengajuan || item.created_at) }}</span>
                </td>
                <td>
                  <span class="status-badge" :class="getStatusClass(item.status)">
                    {{ formatStatus(item.status) }}
                  </span>
                </td>
                <td>
                  <NuxtLink :to="`/admin/pengajuan/${item.id}`" class="btn btn-sm btn-primary">
                    Tinjau Berkas →
                  </NuxtLink>
                </td>
              </tr>
            </tbody>
          </table>
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
const { user, fetchUser, isAuthenticated, logout } = useAuth()
const api = useApi()

const loading = ref(true)
const accessDenied = ref(false)
const errorMessage = ref('')
const pendingList = ref<any[]>([])

const stats = reactive({
  total_pengajuan: 0,
  diajukan: 0,
  diproses: 0,
  perlu_perbaikan: 0,
  disetujui: 0,
  ditolak: 0,
  total_layanan: 0,
  layanan_aktif: 0,
  total_mahasiswa: 0
})

const loadDashboard = async () => {
  loading.value = true
  errorMessage.value = ''
  accessDenied.value = false

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

    // Role verification
    if (currentUser.role !== 'admin') {
      accessDenied.value = true
      loading.value = false
      return
    }

    // Load actual admin dashboard summary
    const dashRes = await api.get('/admin/dashboard')
    if (dashRes.success && dashRes.data) {
      if (dashRes.data.stats) {
        Object.assign(stats, dashRes.data.stats)
      }
    }

    // Load pending pengajuan for action list
    const pengajuanRes = await api.get('/pengajuan?per_page=6')
    if (pengajuanRes.success && pengajuanRes.data) {
      const list = Array.isArray(pengajuanRes.data) ? pengajuanRes.data : []
      pendingList.value = list.filter((i: any) => i.status === 'diajukan' || i.status === 'diproses' || i.status === 'perlu_perbaikan')
    }
  } catch (err: any) {
    if (err.status === 403) {
      accessDenied.value = true
    } else {
      errorMessage.value = err.message || 'Gagal memuat ringkasan dashboard admin.'
    }
  } finally {
    loading.value = false
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

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return dateStr
  }
}

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
.admin-dashboard-page {
  padding: 1rem 0 3rem;
}

.admin-hero {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.4);
  margin-bottom: 2rem;
}

.admin-badge {
  display: inline-block;
  padding: 0.3rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #d8b4fe;
  background: rgba(168, 85, 247, 0.15);
  border: 1px solid rgba(168, 85, 247, 0.35);
  margin-bottom: 0.85rem;
}

.hero-title {
  font-size: 2.1rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.5rem;
  line-height: 1.25;
}

.hero-subtitle {
  font-size: 0.95rem;
  color: var(--text-muted);
  max-width: 620px;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.hero-shortcuts {
  display: flex;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.hero-quick-status {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  background: var(--bg-subtle);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  min-width: 240px;
}

.status-summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.sum-label {
  font-size: 0.75rem;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.sum-val {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-main);
}

/* Statistics Grid */
.stats-overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2.5rem;
}

.stat-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 1.35rem;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  border-color: color-mix(in srgb, var(--primary) 50%, transparent);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.stat-name {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-icon {
  font-size: 1.25rem;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  line-height: 1.1;
  margin-bottom: 0.35rem;
}

.stat-meta {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.card-warning {
  border-color: rgba(245, 158, 11, 0.25);
}

.card-info {
  border-color: rgba(59, 130, 246, 0.25);
}

.card-revision {
  border-color: rgba(251, 146, 60, 0.25);
}

.card-success {
  border-color: rgba(16, 185, 129, 0.25);
}

.card-danger {
  border-color: rgba(239, 68, 68, 0.25);
}

.text-warning { color: #fbbf24; }
.text-info { color: #60a5fa; }
.text-revision { color: #fb923c; }
.text-success { color: #34d399; }
.text-danger { color: #f87171; }

.pending-section {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2rem;
}

.section-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title-row h2 {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.25rem;
}

.section-title-row p {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.btn-sm {
  padding: 0.4rem 0.85rem;
  font-size: 0.82rem;
}

.table-wrap {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.admin-table th {
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-dim);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.9rem 1rem;
  border-bottom: 1px solid var(--border-color);
}

.admin-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--border-subtle);
  color: var(--text-main);
}

.admin-table tr:hover td {
  background: var(--surface-hover);
}

.ticket-code {
  font-family: monospace;
  font-size: 0.78rem;
  color: var(--primary-light);
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.student-info-col {
  display: flex;
  flex-direction: column;
}

.st-name {
  font-weight: 600;
  color: var(--text);
}

.st-nim {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.service-name-text {
  font-weight: 600;
  color: var(--text-main);
}

.date-col {
  font-size: 0.82rem;
  color: var(--text-muted);
}

/* Status Badges */
.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  display: inline-block;
}

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

.empty-pending {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.state-container {
  text-align: center;
  padding: 5rem 1rem;
}

.access-denied-box {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 20px;
  max-width: 520px;
  margin: 3rem auto;
  padding: 3rem 2rem;
}

.access-denied-box .state-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.access-denied-box h2 {
  font-size: 1.5rem;
  color: #fca5a5;
  margin-bottom: 0.75rem;
}

.access-denied-box p {
  font-size: 0.95rem;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.denied-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
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

@media (max-width: 768px) {
  .admin-hero {
    padding: 1.75rem;
  }
}
</style>
