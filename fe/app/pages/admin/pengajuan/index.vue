<template>
  <div class="admin-pengajuan-page">
    <div class="page-top-bar">
      <div>
        <div class="breadcrumb">
          <NuxtLink to="/admin" class="crumb-link">← Kembali ke Dashboard Admin</NuxtLink>
        </div>
        <h1 class="page-title">Kelola Seluruh Pengajuan</h1>
        <p class="page-subtitle">Verifikasi dan perbarui status permohonan layanan akademik mahasiswa</p>
      </div>

      <div class="header-stat-tag">
        <span>Total: <strong>{{ totalItems }}</strong> Permohonan</span>
      </div>
    </div>

    <!-- Access Denied -->
    <div v-if="accessDenied" class="state-container access-denied-box">
      <div class="state-icon">⛔</div>
      <h2>Akses Ditolak (403 Forbidden)</h2>
      <p>Hanya akun Administrator yang memiliki wewenang untuk meninjau pengajuan mahasiswa.</p>
      <NuxtLink to="/mahasiswa" class="btn btn-primary" style="margin-top: 1rem;">Kembali</NuxtLink>
    </div>

    <template v-else>
      <!-- Search & Filters -->
      <div class="filters-card">
        <div class="filter-tabs">
          <button
            v-for="tab in filterTabs"
            :key="tab.value"
            class="tab-btn"
            :class="{ 'tab-active': selectedStatus === tab.value }"
            @click="changeStatusFilter(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="search-box">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Cari tiket, NIM, nama mahasiswa..."
            class="search-input"
          />
          <button v-if="searchQuery" @click="clearSearch" class="btn-clear-search">✕</button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="state-container">
        <div class="spinner-large"></div>
        <p>Memuat daftar pengajuan...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="items.length === 0" class="state-container empty-box">
        <div class="empty-icon">📋</div>
        <h3>Tidak Ada Pengajuan Ditemukan</h3>
        <p v-if="selectedStatus || searchQuery">
          Tidak ada berkas yang cocok dengan filter atau kata kunci "{{ searchQuery }}".
        </p>
        <p v-else>
          Belum ada berkas pengajuan masuk dari mahasiswa.
        </p>
      </div>

      <!-- Table View -->
      <div v-else class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nomor Tiket</th>
              <th>Pemohon (Mahasiswa)</th>
              <th>Layanan Diajukan</th>
              <th>Tanggal Masuk</th>
              <th>Status</th>
              <th>Catatan Admin</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in items"
              :key="item.id"
              :class="{
                'row-need-attention': item.status === 'diajukan',
                'row-revision': item.status === 'perlu_perbaikan'
              }"
            >
              <td>
                <span class="ticket-pill">{{ item.nomor_pengajuan }}</span>
              </td>
              <td>
                <div class="student-cell">
                  <strong class="st-name">{{ item.user?.name || 'Mahasiswa #' + item.user_id }}</strong>
                  <span class="st-meta">NIM: {{ item.user?.nim || '-' }} • {{ item.user?.prodi || '-' }}</span>
                </div>
              </td>
              <td>
                <div class="svc-cell">
                  <span class="svc-name">{{ item.layanan?.nama || 'Layanan #' + item.layanan_id }}</span>
                  <span class="svc-code">{{ item.layanan?.kode || '-' }}</span>
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
                <span class="note-text" :title="item.catatan_admin">
                  {{ item.catatan_admin || '-' }}
                </span>
              </td>
              <td>
                <NuxtLink :to="`/admin/pengajuan/${item.id}`" class="btn btn-sm btn-primary">
                  Tinjau & Tindak →
                </NuxtLink>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-footer">
          <div class="page-info">
            Halaman <strong>{{ currentPage }}</strong> dari <strong>{{ lastPage }}</strong> (Total: {{ totalItems }} data)
          </div>

          <div class="pagination-buttons">
            <button
              :disabled="currentPage <= 1 || loading"
              @click="goToPage(currentPage - 1)"
              class="btn-page"
            >
              ← Sebelumnya
            </button>
            <button
              :disabled="currentPage >= lastPage || loading"
              @click="goToPage(currentPage + 1)"
              class="btn-page"
            >
              Selanjutnya →
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const router = useRouter()
const { fetchUser, isAuthenticated } = useAuth()
const api = useApi()

const items = ref<any[]>([])
const loading = ref(true)
const accessDenied = ref(false)

const selectedStatus = ref('')
const searchQuery = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)

const filterTabs = [
  { label: 'Semua Status', value: '' },
  { label: 'Diajukan', value: 'diajukan' },
  { label: 'Diproses', value: 'diproses' },
  { label: 'Perlu Perbaikan', value: 'perlu_perbaikan' },
  { label: 'Disetujui', value: 'disetujui' },
  { label: 'Ditolak', value: 'ditolak' }
]

const loadPengajuan = async (page = 1) => {
  loading.value = true
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

    let url = `/pengajuan?page=${page}&per_page=15`
    if (selectedStatus.value) {
      url += `&status=${encodeURIComponent(selectedStatus.value)}`
    }
    if (searchQuery.value.trim()) {
      url += `&search=${encodeURIComponent(searchQuery.value.trim())}`
    }

    const res = await api.get(url)
    if (res.success && res.data) {
      items.value = Array.isArray(res.data) ? res.data : []
      if (res.meta) {
        currentPage.value = res.meta.current_page || 1
        lastPage.value = res.meta.last_page || 1
        totalItems.value = res.meta.total || items.value.length
      }
    }
  } catch (err: any) {
    if (err.status === 403) {
      accessDenied.value = true
    }
  } finally {
    loading.value = false
  }
}

const changeStatusFilter = (st: string) => {
  selectedStatus.value = st
  currentPage.value = 1
  loadPengajuan(1)
}

const handleSearch = () => {
  currentPage.value = 1
  loadPengajuan(1)
}

const clearSearch = () => {
  searchQuery.value = ''
  currentPage.value = 1
  loadPengajuan(1)
}

const goToPage = (page: number) => {
  if (page < 1 || page > lastPage.value) return
  loadPengajuan(page)
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
  loadPengajuan(1)
})
</script>

<style scoped>
.admin-pengajuan-page {
  padding: 1rem 0 3rem;
}

.breadcrumb {
  margin-bottom: 0.5rem;
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

.page-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 0.25rem;
}

.page-subtitle {
  font-size: 0.95rem;
  color: var(--text-muted);
}

.header-stat-tag {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 0.5rem 1rem;
  font-size: 0.88rem;
  color: var(--text-muted);
}

.header-stat-tag strong {
  color: var(--text);
}

.filters-card {
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

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 280px;
}

.search-box svg {
  position: absolute;
  left: 0.85rem;
  color: var(--text-dim);
}

.search-input {
  width: 100%;
  padding: 0.55rem 2rem 0.55rem 2.3rem;
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

.btn-clear-search {
  position: absolute;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: var(--text-dim);
  cursor: pointer;
  font-size: 0.85rem;
}

.table-container {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  overflow-x: auto;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.35);
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.admin-table th {
  background: var(--bg-subtle);
  color: var(--text-dim);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid var(--border-color);
}

.admin-table td {
  padding: 1rem 1.15rem;
  border-bottom: 1px solid var(--border-subtle);
  color: var(--text-main);
  vertical-align: middle;
}

.admin-table tr:hover td {
  background: var(--surface-hover);
}

.row-need-attention td {
  background: color-mix(in srgb, var(--warning) 6%, transparent);
}

.row-revision td {
  background: color-mix(in srgb, var(--warning) 8%, transparent);
}

.ticket-pill {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--primary-light);
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.student-cell {
  display: flex;
  flex-direction: column;
}

.st-name {
  color: var(--text);
}

.st-meta {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.svc-cell {
  display: flex;
  flex-direction: column;
}

.svc-name {
  font-weight: 600;
  color: var(--text-main);
}

.svc-code {
  font-size: 0.72rem;
  color: var(--text-dim);
  font-family: monospace;
}

.date-text {
  font-size: 0.82rem;
  color: var(--text-muted);
}

.note-text {
  max-width: 180px;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.8rem;
  color: var(--alert-warning-title);
}

.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.82rem;
}

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.1rem 1.25rem;
  border-top: 1px solid var(--border-subtle);
  flex-wrap: wrap;
  gap: 1rem;
}

.page-info {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.pagination-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-page {
  background: var(--bg-subtle);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-page:hover:not(:disabled) {
  background: var(--surface-hover);
  border-color: color-mix(in srgb, var(--text) 20%, transparent);
}

.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
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
</style>
