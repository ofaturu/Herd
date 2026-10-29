<template>
  <div class="history-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Riwayat Pengajuan Layanan</h1>
        <p class="page-subtitle">Pantau seluruh status berkas dan permohonan yang pernah Anda ajukan</p>
      </div>

      <NuxtLink to="/layanan" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        <span>Ajukan Layanan Baru</span>
      </NuxtLink>
    </div>

    <!-- Filter & Search Controls -->
    <div class="controls-card">
      <div class="filter-tabs">
        <button
          v-for="tab in filterTabs"
          :key="tab.value"
          class="tab-btn"
          :class="{ 'tab-active': selectedStatus === tab.value }"
          @click="selectedStatus = tab.value"
        >
          {{ tab.label }}
          <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor pengajuan atau nama layanan..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat riwayat pengajuan...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>⚠️ {{ errorMessage }}</p>
      <button @click="loadHistory" class="btn btn-secondary">Coba Muat Ulang</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredList.length === 0" class="state-container empty-box">
      <div class="empty-icon">📋</div>
      <h3>Tidak Ada Pengajuan Ditemukan</h3>
      <p v-if="selectedStatus !== 'all' || searchQuery">
        Tidak ada data yang sesuai dengan filter atau kata kunci pencarian.
      </p>
      <p v-else>
        Anda belum memiliki riwayat pengajuan layanan.
      </p>
      <NuxtLink to="/layanan" class="btn btn-primary" style="margin-top: 1rem;">
        Lihat Katalog Layanan
      </NuxtLink>
    </div>

    <!-- Table / List of Submissions -->
    <div v-else class="table-container">
      <table class="pengajuan-table">
        <thead>
          <tr>
            <th>Nomor Tiket</th>
            <th>Layanan</th>
            <th>Tanggal Pengajuan</th>
            <th>Status</th>
            <th>Catatan Admin</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in filteredList"
            :key="item.id"
            :class="{ 'row-warning': item.status === 'perlu_perbaikan' }"
          >
            <td>
              <span class="ticket-pill">{{ item.nomor_pengajuan }}</span>
            </td>
            <td>
              <div class="service-cell">
                <span class="cell-title">{{ item.layanan?.nama || 'Layanan #' + item.layanan_id }}</span>
                <span v-if="item.layanan?.kode" class="cell-sub">{{ item.layanan.kode }}</span>
              </div>
            </td>
            <td>
              <span class="date-text">{{ formatDate(item.tanggal_pengajuan || item.created_at) }}</span>
            </td>
            <td>
              <span class="status-badge" :class="getStatusClass(item.status)">
                {{ formatStatus(item.status) }}
              </span>
            </td>
            <td>
              <div v-if="item.catatan_admin" class="note-snippet" :title="item.catatan_admin">
                {{ item.catatan_admin }}
              </div>
              <span v-else class="text-dim">-</span>
            </td>
            <td>
              <NuxtLink :to="`/pengajuan/${item.id}`" class="btn-action">
                Detail →
              </NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const router = useRouter()
const { isAuthenticated, fetchUser } = useAuth()
const api = useApi()

const items = ref<any[]>([])
const loading = ref(true)
const errorMessage = ref('')
const selectedStatus = ref('all')
const searchQuery = ref('')

const loadHistory = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    if (!isAuthenticated()) {
      router.push('/login')
      return
    }
    await fetchUser()

    const res = await api.get('/pengajuan/mine?per_page=50')
    if (res.success && res.data) {
      items.value = Array.isArray(res.data) ? res.data : []
    } else {
      errorMessage.value = res.message || 'Gagal memuat data pengajuan.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan sistem saat memuat data.'
  } finally {
    loading.value = false
  }
}

const filterTabs = computed(() => [
  { label: 'Semua', value: 'all', count: items.value.length },
  { label: 'Diajukan', value: 'diajukan', count: items.value.filter(i => i.status === 'diajukan').length },
  { label: 'Diproses', value: 'diproses', count: items.value.filter(i => i.status === 'diproses').length },
  { label: 'Perlu Perbaikan', value: 'perlu_perbaikan', count: items.value.filter(i => i.status === 'perlu_perbaikan').length },
  { label: 'Disetujui', value: 'disetujui', count: items.value.filter(i => i.status === 'disetujui' || i.status === 'selesai').length },
  { label: 'Ditolak', value: 'ditolak', count: items.value.filter(i => i.status === 'ditolak').length },
])

const filteredList = computed(() => {
  return items.value.filter(item => {
    // Status filter
    if (selectedStatus.value !== 'all') {
      if (selectedStatus.value === 'disetujui') {
        if (item.status !== 'disetujui' && item.status !== 'selesai') return false
      } else if (item.status !== selectedStatus.value) {
        return false
      }
    }

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const ticketMatch = item.nomor_pengajuan && item.nomor_pengajuan.toLowerCase().includes(q)
      const serviceMatch = item.layanan?.nama && item.layanan.nama.toLowerCase().includes(q)
      const noteMatch = item.catatan_admin && item.catatan_admin.toLowerCase().includes(q)
      return ticketMatch || serviceMatch || noteMatch
    }

    return true
  })
})

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
  loadHistory()
})
</script>

<style scoped>
.history-page {
  padding: 1rem 0 2rem;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.35rem;
}

.page-subtitle {
  font-size: 0.95rem;
  color: var(--text-muted);
}

.controls-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-tabs {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.tab-btn {
  background: transparent;
  border: 1px solid transparent;
  color: var(--text-muted);
  font-size: 0.82rem;
  font-weight: 600;
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-main);
}

.tab-active {
  background: rgba(59, 130, 246, 0.15) !important;
  color: var(--primary-light) !important;
  border-color: rgba(59, 130, 246, 0.3) !important;
}

.tab-count {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.1rem 0.4rem;
  border-radius: 9999px;
  font-size: 0.72rem;
}

.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 280px;
}

.search-wrap svg {
  position: absolute;
  left: 0.75rem;
  color: var(--text-dim);
}

.search-input {
  width: 100%;
  padding: 0.55rem 0.75rem 0.55rem 2.2rem;
  background: var(--bg-subtle);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  color: var(--text);
  font-size: 0.85rem;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
}

.table-container {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  overflow-x: auto;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.35);
}

.pengajuan-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.pengajuan-table th {
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-dim);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-color);
}

.pengajuan-table td {
  padding: 1.1rem 1.25rem;
  border-bottom: 1px solid var(--border-subtle);
  color: var(--text-main);
}

.pengajuan-table tr:hover td {
  background: var(--surface-hover);
}

.row-warning td {
  background: color-mix(in srgb, var(--warning) 6%, transparent);
}

.ticket-pill {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--primary-light);
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.service-cell {
  display: flex;
  flex-direction: column;
}

.cell-title {
  font-weight: 600;
  color: var(--text);
}

.cell-sub {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.date-text {
  font-size: 0.82rem;
  color: var(--text-muted);
}

.note-snippet {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.8rem;
  color: var(--alert-warning-title);
}

.text-dim {
  color: var(--text-dim);
}

.btn-action {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--primary-light);
  text-decoration: none;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
  transition: all 0.2s ease;
}

.btn-action:hover {
  background: color-mix(in srgb, var(--primary) 20%, transparent);
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

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
