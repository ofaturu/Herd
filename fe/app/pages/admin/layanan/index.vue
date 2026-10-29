<template>
  <div class="admin-layanan-page">
    <div class="page-top-bar">
      <div>
        <div class="breadcrumb">
          <NuxtLink to="/admin" class="crumb-link">← Kembali ke Dashboard Admin</NuxtLink>
        </div>
        <h1 class="page-title">Master Data Layanan</h1>
        <p class="page-subtitle">Kelola daftar layanan akademik yang dapat diakses oleh mahasiswa</p>
      </div>

      <button @click="openCreateModal" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        <span>Tambah Layanan Baru</span>
      </button>
    </div>

    <!-- Alert Notification -->
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
      <p>Hanya akun Administrator yang memiliki wewenang untuk mengelola katalog layanan.</p>
      <NuxtLink to="/mahasiswa" class="btn btn-primary" style="margin-top: 1rem;">Kembali</NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="state-container">
      <div class="spinner-large"></div>
      <p>Memuat daftar layanan...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="services.length === 0" class="state-container empty-box">
      <div class="empty-icon">📁</div>
      <h3>Belum Ada Layanan Terdaftar</h3>
      <p>Klik tombol di atas untuk menambahkan layanan akademik pertama.</p>
    </div>

    <!-- Table of Services -->
    <div v-else class="table-container">
      <table class="services-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Layanan</th>
            <th>Deskripsi Singkat</th>
            <th>Estimasi</th>
            <th>Persyaratan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="svc in services" :key="svc.id" :class="{ 'row-inactive': !svc.is_aktif }">
            <td>
              <span class="code-pill">{{ svc.kode }}</span>
            </td>
            <td>
              <strong class="svc-name">{{ svc.nama }}</strong>
            </td>
            <td>
              <span class="desc-text" :title="svc.deskripsi">
                {{ svc.deskripsi || '-' }}
              </span>
            </td>
            <td>
              <span class="est-tag">⏱️ {{ svc.estimasi_hari }} Hari</span>
            </td>
            <td>
              <span class="req-count">
                {{ Array.isArray(svc.persyaratan) ? `${svc.persyaratan.length} syarat` : (svc.persyaratan ? '1 syarat' : 'Tanpa syarat') }}
              </span>
            </td>
            <td>
              <span class="status-badge" :class="svc.is_aktif ? 'badge-selesai' : 'badge-ditolak'">
                {{ svc.is_aktif ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="openEditModal(svc)" class="btn-icon btn-edit" title="Edit Layanan">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  <span>Edit</span>
                </button>

                <button
                  v-if="svc.is_aktif"
                  @click="handleDeactivate(svc)"
                  class="btn-icon btn-deactivate"
                  title="Nonaktifkan Layanan"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" x2="19.07" y1="4.93" y2="19.07"/></svg>
                  <span>Nonaktifkan</span>
                </button>

                <button
                  v-else
                  @click="handleReactivate(svc)"
                  class="btn-icon btn-activate"
                  title="Aktifkan Kembali Layanan"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <span>Aktifkan</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Form: Create / Edit Layanan -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ editingService ? 'Ubah Data Layanan' : 'Tambah Layanan Baru' }}</h3>
          <button @click="closeModal" class="btn-modal-close">✕</button>
        </div>

        <form @submit.prevent="saveService" class="modal-form">
          <div v-if="modalError" class="alert-box alert-error">
            <span>{{ modalError }}</span>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="kode" class="form-label">Kode Layanan <span class="required">*</span></label>
              <input
                id="kode"
                v-model="form.kode"
                type="text"
                placeholder="Misal: SK-AKTIF, TRANSKRIP"
                required
                class="form-input"
                :class="{ 'input-invalid': fieldErrors.kode }"
              />
              <span v-if="fieldErrors.kode" class="field-error">{{ fieldErrors.kode[0] }}</span>
            </div>

            <div class="form-group">
              <label for="estimasi_hari" class="form-label">Estimasi Hari Kerja <span class="required">*</span></label>
              <input
                id="estimasi_hari"
                v-model.number="form.estimasi_hari"
                type="number"
                min="0"
                placeholder="3"
                required
                class="form-input"
                :class="{ 'input-invalid': fieldErrors.estimasi_hari }"
              />
              <span v-if="fieldErrors.estimasi_hari" class="field-error">{{ fieldErrors.estimasi_hari[0] }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="nama" class="form-label">Nama Layanan <span class="required">*</span></label>
            <input
              id="nama"
              v-model="form.nama"
              type="text"
              placeholder="Misal: Surat Keterangan Mahasiswa Aktif"
              required
              class="form-input"
              :class="{ 'input-invalid': fieldErrors.nama }"
            />
            <span v-if="fieldErrors.nama" class="field-error">{{ fieldErrors.nama[0] }}</span>
          </div>

          <div class="form-group">
            <label for="deskripsi" class="form-label">Deskripsi Layanan</label>
            <textarea
              id="deskripsi"
              v-model="form.deskripsi"
              rows="3"
              placeholder="Penjelasan fungsi layanan dan kegunaan dokumen ini..."
              class="form-textarea"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="persyaratan" class="form-label">
              Persyaratan Dokumen (Pisahkan dengan baris baru)
            </label>
            <textarea
              id="persyaratan"
              v-model="persyaratanText"
              rows="3"
              placeholder="KRS Semester Berjalan&#10;Bukti Lunas Pembayaran UKT&#10;Surat Pengantar Jurusan"
              class="form-textarea"
            ></textarea>
            <span class="field-help">Setiap baris akan disimpan sebagai satu butir syarat dokumen.</span>
          </div>

          <div class="form-group-checkbox">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.is_aktif" class="checkbox-input" />
              <span>Layanan Langsung Aktif (Dapat diajukan mahasiswa)</span>
            </label>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">Batal</button>
            <button type="submit" :disabled="saving" class="btn btn-primary">
              <span v-if="saving" class="spinner"></span>
              <span>{{ saving ? 'Menyimpan...' : (editingService ? 'Simpan Perubahan' : 'Buat Layanan') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const router = useRouter()
const { fetchUser, isAuthenticated } = useAuth()
const api = useApi()

const services = ref<any[]>([])
const loading = ref(true)
const accessDenied = ref(false)
const successToast = ref('')
const errorToast = ref('')

// Modal state
const showModal = ref(false)
const editingService = ref<any>(null)
const saving = ref(false)
const modalError = ref('')
const fieldErrors = ref<Record<string, string[]>>({})
const persyaratanText = ref('')

const form = reactive({
  kode: '',
  nama: '',
  deskripsi: '',
  estimasi_hari: 3,
  is_aktif: true
})

const loadServices = async () => {
  loading.value = true
  accessDenied.value = false

  try {
    if (!isAuthenticated()) {
      router.push('/login')
      return
    }

    const currentUser = await fetchUser()
    if (!currentUser || currentUser.role !== 'admin') {
      accessDenied.value = true
      loading.value = false
      return
    }

    // Call /api/layanan?all=1 so admin can see both active & inactive services
    const res = await api.get('/layanan?all=1&per_page=100')
    if (res.success && res.data) {
      services.value = Array.isArray(res.data) ? res.data : []
    } else {
      errorToast.value = res.message || 'Gagal memuat master layanan.'
    }
  } catch (err: any) {
    if (err.status === 403) {
      accessDenied.value = true
    } else {
      errorToast.value = err.message || 'Terjadi kesalahan sistem saat memuat layanan.'
    }
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  editingService.value = null
  form.kode = ''
  form.nama = ''
  form.deskripsi = ''
  form.estimasi_hari = 3
  form.is_aktif = true
  persyaratanText.value = ''
  modalError.value = ''
  fieldErrors.value = {}
  showModal.value = true
}

const openEditModal = (svc: any) => {
  editingService.value = svc
  form.kode = svc.kode || ''
  form.nama = svc.nama || ''
  form.deskripsi = svc.deskripsi || ''
  form.estimasi_hari = svc.estimasi_hari || 0
  form.is_aktif = !!svc.is_aktif

  if (Array.isArray(svc.persyaratan)) {
    persyaratanText.value = svc.persyaratan.join('\n')
  } else if (typeof svc.persyaratan === 'string') {
    persyaratanText.value = svc.persyaratan
  } else {
    persyaratanText.value = ''
  }

  modalError.value = ''
  fieldErrors.value = {}
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingService.value = null
}

const saveService = async () => {
  saving.value = true
  modalError.value = ''
  fieldErrors.value = {}

  // Parse persyaratan string into array
  const parsedReq = persyaratanText.value
    .split('\n')
    .map(s => s.trim())
    .filter(s => s.length > 0)

  const payload: any = {
    kode: form.kode,
    nama: form.nama,
    deskripsi: form.deskripsi || null,
    estimasi_hari: form.estimasi_hari,
    persyaratan: parsedReq.length > 0 ? parsedReq : null,
    is_aktif: form.is_aktif
  }

  try {
    let res
    if (editingService.value) {
      res = await api.put(`/layanan/${editingService.value.id}`, payload)
    } else {
      res = await api.post('/layanan', payload)
    }

    if (res.success) {
      successToast.value = editingService.value
        ? `Layanan "${form.nama}" berhasil diperbarui.`
        : `Layanan "${form.nama}" berhasil ditambahkan.`
      closeModal()
      await loadServices()
    } else {
      modalError.value = res.message || 'Gagal menyimpan data layanan.'
      if (res.errors) {
        fieldErrors.value = res.errors
      }
    }
  } catch (err: any) {
    modalError.value = err.message || 'Terjadi kesalahan jaringan saat menyimpan layanan.'
  } finally {
    saving.value = false
  }
}

const handleDeactivate = async (svc: any) => {
  if (!confirm(`Apakah Anda yakin ingin menonaktifkan layanan "${svc.nama}"? Layanan ini tidak akan lagi muncul di katalog mahasiswa.`)) {
    return
  }

  try {
    const res = await api.delete(`/layanan/${svc.id}`)
    if (res.success) {
      successToast.value = `Layanan "${svc.nama}" berhasil dinonaktifkan.`
      await loadServices()
    } else {
      errorToast.value = res.message || 'Gagal menonaktifkan layanan.'
    }
  } catch (err: any) {
    errorToast.value = err.message || 'Terjadi kesalahan sistem saat menonaktifkan layanan.'
  }
}

const handleReactivate = async (svc: any) => {
  try {
    const res = await api.put(`/layanan/${svc.id}`, { is_aktif: true })
    if (res.success) {
      successToast.value = `Layanan "${svc.nama}" berhasil diaktifkan kembali.`
      await loadServices()
    } else {
      errorToast.value = res.message || 'Gagal mengaktifkan layanan.'
    }
  } catch (err: any) {
    errorToast.value = err.message || 'Terjadi kesalahan sistem.'
  }
}

onMounted(() => {
  loadServices()
})
</script>

<style scoped>
.admin-layanan-page {
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
  margin-bottom: 0.25rem;
}

.page-subtitle {
  font-size: 0.95rem;
  color: var(--text-muted);
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

.table-container {
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  overflow-x: auto;
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.35);
}

.services-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.services-table th {
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-dim);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid var(--border-color);
}

.services-table td {
  padding: 1rem 1.15rem;
  border-bottom: 1px solid var(--border-subtle);
  color: var(--text-main);
  vertical-align: middle;
}

.services-table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.row-inactive td {
  opacity: 0.6;
}

.code-pill {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--primary-light);
  background: rgba(59, 130, 246, 0.1);
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.svc-name {
  color: var(--text);
  font-size: 0.95rem;
}

.desc-text {
  max-width: 260px;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.82rem;
  color: var(--text-muted);
}

.est-tag {
  font-size: 0.82rem;
  color: var(--text-muted);
  white-space: nowrap;
}

.req-count {
  font-size: 0.8rem;
  color: var(--text-dim);
}

.action-buttons {
  display: flex;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-edit {
  background: rgba(59, 130, 246, 0.1);
  color: var(--primary-light);
  border-color: rgba(59, 130, 246, 0.25);
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.2);
}

.btn-deactivate {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.25);
}

.btn-deactivate:hover {
  background: rgba(239, 68, 68, 0.2);
}

.btn-activate {
  background: rgba(16, 185, 129, 0.1);
  color: #34d399;
  border-color: rgba(16, 185, 129, 0.25);
}

.btn-activate:hover {
  background: rgba(16, 185, 129, 0.2);
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-card {
  width: 100%;
  max-width: 580px;
  background: var(--surface);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--border-subtle);
}

.modal-header h3 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text);
}

.btn-modal-close {
  background: transparent;
  border: none;
  color: var(--text-dim);
  font-size: 1.1rem;
  cursor: pointer;
}

.btn-modal-close:hover {
  color: var(--text);
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1rem;
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
  color: var(--danger);
}

.form-input, .form-textarea {
  width: 100%;
  padding: 0.65rem 0.85rem;
  background: var(--bg-subtle);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  color: var(--text);
  font-size: 0.88rem;
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

.field-help {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.field-error {
  font-size: 0.75rem;
  color: #f87171;
}

.form-group-checkbox {
  margin-top: 0.25rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.85rem;
  color: var(--text-main);
  cursor: pointer;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  accent-color: var(--primary);
  cursor: pointer;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.85rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-subtle);
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

.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  display: inline-block;
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .form-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
