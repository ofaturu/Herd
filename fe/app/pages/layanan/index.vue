<template>
  <div class="services-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Katalog Layanan Akademik</h1>
        <p class="page-subtitle">Pilih layanan administrasi atau permohonan surat akademik yang Anda butuhkan</p>
      </div>

      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari layanan (misal: Cuti, Keterangan, Transkrip)..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat daftar layanan aktif...</p>
    </div>

    <!-- Error -->
    <div v-else-if="errorMessage" class="state-container error-box">
      <p>{{ errorMessage }}</p>
      <button @click="loadServices" class="btn btn-secondary">Coba Muat Ulang</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredServices.length === 0" class="state-container empty-box">
      <div class="empty-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </div>
      <h3>Tidak Ada Layanan Ditemukan</h3>
      <p>Tidak ada layanan yang sesuai dengan kata kunci "{{ searchQuery }}".</p>
    </div>

    <!-- Services Grid -->
    <div v-else class="services-grid">
      <div v-for="service in filteredServices" :key="service.id" class="service-card">
        <div class="card-badge-row">
          <span class="service-code">{{ service.kode || 'LAYANAN-' + service.id }}</span>
          <span class="est-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>{{ service.estimasi_hari ? `${service.estimasi_hari} Hari Kerja` : 'Estimasi Cepat' }}</span>
          </span>
        </div>

        <h2 class="service-name">{{ service.nama }}</h2>
        <p class="service-desc">{{ service.deskripsi || 'Layanan administrasi mahasiswa untuk keperluan perkuliahan.' }}</p>

        <div class="req-section">
          <span class="req-title">Persyaratan Dokumen</span>
          <ol class="req-list" v-if="parseReqs(service.persyaratan).length > 0">
            <li v-for="(item, i) in parseReqs(service.persyaratan)" :key="i">{{ item }}</li>
          </ol>
          <p v-else class="req-empty">Tidak ada persyaratan khusus.</p>
        </div>

        <div class="card-footer-action">
          <NuxtLink :to="`/layanan/${service.id}`" class="btn btn-primary btn-full">
            <span>Ajukan Sekarang</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const api = useApi()
const services = ref<any[]>([])
const loading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')

const loadServices = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await api.get('/layanan')
    if (res.success && res.data) {
      services.value = Array.isArray(res.data) ? res.data : []
    } else {
      errorMessage.value = res.message || 'Gagal memuat katalog layanan.'
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat memuat data layanan.'
  } finally {
    loading.value = false
  }
}

const filteredServices = computed(() => {
  if (!searchQuery.value.trim()) return services.value
  const q = searchQuery.value.toLowerCase()
  return services.value.filter(s =>
    (s.nama && s.nama.toLowerCase().includes(q)) ||
    (s.deskripsi && s.deskripsi.toLowerCase().includes(q)) ||
    (s.kode && s.kode.toLowerCase().includes(q))
  )
})

/**
 * Parses persyaratan which may be a JSON-encoded array or a plain string.
 * Returns an array of clean strings without the leading "1. ", "2. " numbering.
 */
const parseReqs = (raw: any): string[] => {
  if (!raw) return []
  let arr: string[] = []
  if (Array.isArray(raw)) {
    arr = raw
  } else if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      arr = Array.isArray(parsed) ? parsed : [raw]
    } catch {
      arr = [raw]
    }
  }
  // Strip leading numbering like "1. ", "2. "
  return arr.map((item: string) => String(item).replace(/^\d+\.\s*/, '').trim()).filter(Boolean)
}

onMounted(() => {
  loadServices()
})
</script>

<style scoped>
.services-page {
  padding: 1rem 0;
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

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  max-width: 360px;
}

.search-box svg {
  position: absolute;
  left: 0.85rem;
  color: var(--text-dim);
}

.search-input {
  width: 100%;
  padding: 0.65rem 0.85rem 0.65rem 2.4rem;
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  color: var(--text);
  font-size: 0.88rem;
  font-family: inherit;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

.service-card {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 1.6rem;
  display: flex;
  flex-direction: column;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: var(--shadow-card);
}

.service-card:hover {
  transform: translateY(-3px);
  border-color: color-mix(in srgb, var(--primary) 50%, transparent);
  box-shadow: var(--shadow-dropdown);
}

.card-badge-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.service-code {
  font-family: monospace;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--primary-light);
  background: color-mix(in srgb, var(--primary) 10%, transparent);
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.est-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
}

.service-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.service-desc {
  font-size: 0.88rem;
  color: var(--text-muted);
  line-height: 1.55;
  margin-bottom: 1.25rem;
  flex: 1;
}

.req-section {
  background: var(--bg-subtle);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 0.85rem;
  margin-bottom: 1.25rem;
}

.req-title {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.3rem;
}

.req-list {
  margin: 0;
  padding-left: 1.1rem;
  list-style: decimal;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.req-list li {
  font-size: 0.82rem;
  color: var(--text);
  line-height: 1.45;
}

.req-empty {
  font-size: 0.82rem;
  color: var(--text-dim);
}

.card-footer-action {
  margin-top: auto;
}

.btn-full {
  width: 100%;
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
