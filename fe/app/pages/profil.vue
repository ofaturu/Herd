<template>
  <div class="profil-page">
    <div class="page-header">
      <NuxtLink to="/mahasiswa" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Kembali ke Dashboard
      </NuxtLink>
      <h1 class="page-title">Data Diri Mahasiswa</h1>
      <p class="page-desc">Perbarui nama tampilan, nomor HP, dan foto profil Anda.</p>
    </div>

    <div v-if="pageLoading" class="state-box">
      <div class="spinner spinner-lg" aria-hidden="true"></div>
      <p>Memuat data profil...</p>
    </div>

    <div v-else class="profil-grid">
      <!-- Photo card -->
      <div class="card photo-card">
        <h2 class="section-heading">Foto Profil</h2>
        <div class="avatar-preview-wrap">
          <div class="avatar-preview">
            <img v-if="fotoPreview || currentFotoUrl" :src="fotoPreview || currentFotoUrl || ''" alt="Foto profil" class="avatar-img-lg" />
            <span v-else class="avatar-initials-lg">{{ user?.name?.charAt(0) || 'M' }}</span>
          </div>
          <label for="foto-input" class="btn btn-secondary btn-sm" style="cursor:pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
            Pilih Foto
          </label>
          <input
            id="foto-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="visually-hidden"
            @change="onFotoSelected"
          />
        </div>
        <p class="photo-hint">JPG, PNG, atau WebP. Maks 2 MB.</p>

        <div v-if="fotoFile" class="photo-actions">
          <button
            id="btn-upload-foto"
            class="btn btn-primary btn-sm"
            :disabled="uploadingFoto"
            @click="uploadFoto"
          >
            <span v-if="uploadingFoto" class="spinner" aria-hidden="true"></span>
            <span>{{ uploadingFoto ? 'Mengunggah...' : 'Simpan Foto' }}</span>
          </button>
          <button type="button" class="btn btn-secondary btn-sm" @click="cancelFoto">Batal</button>
        </div>

        <div v-if="fotoSuccess" class="alert-success" role="status">Foto profil berhasil diperbarui.</div>
        <div v-if="fotoError" class="alert-error-sm" role="alert">{{ fotoError }}</div>
      </div>

      <!-- Data form card -->
      <div class="card form-card">
        <h2 class="section-heading">Informasi Akun</h2>

        <div class="info-readonly">
          <div class="readonly-item">
            <span class="readonly-label">Email</span>
            <span class="readonly-value">{{ user?.email || '-' }}</span>
          </div>
          <div class="readonly-item">
            <span class="readonly-label">Role</span>
            <span class="readonly-value capitalize">{{ user?.role || '-' }}</span>
          </div>
        </div>

        <hr class="divider" />

        <h3 class="sub-heading">Data yang Dapat Diubah</h3>

        <div v-if="saveSuccess" class="alert-success" role="status">Perubahan berhasil disimpan.</div>
        <div v-if="saveError" class="alert-error-sm" role="alert">{{ saveError }}</div>

        <form @submit.prevent="saveProfile" class="profile-form" novalidate>
          <div class="form-group">
            <label for="f-nim" class="form-label">NIM</label>
            <input
              id="f-nim"
              v-model="form.nim"
              type="text"
              class="form-input"
              placeholder="Nomor Induk Mahasiswa"
              maxlength="30"
            />
          </div>
          <div class="form-group">
            <label for="f-name" class="form-label">Nama Lengkap</label>
            <input
              id="f-name"
              v-model="form.name"
              type="text"
              class="form-input"
              placeholder="Nama sesuai KTM"
              maxlength="255"
            />
          </div>
          <div class="form-group">
            <label for="f-prodi" class="form-label">Program Studi</label>
            <input
              id="f-prodi"
              v-model="form.prodi"
              type="text"
              class="form-input"
              placeholder="Contoh: Teknik Informatika"
              maxlength="100"
            />
          </div>
          <div class="form-group">
            <label for="f-nohp" class="form-label">Nomor HP</label>
            <input
              id="f-nohp"
              v-model="form.no_hp"
              type="tel"
              class="form-input"
              placeholder="08xxxxxxxxxx"
              maxlength="20"
            />
          </div>

          <button
            id="btn-save-profile"
            type="submit"
            class="btn btn-primary"
            :disabled="saving"
          >
            <span v-if="saving" class="spinner" aria-hidden="true"></span>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRuntimeConfig } from '#app'
import { useAuth } from '@/composables/useAuth'
import { useApi } from '@/composables/useApi'

const router = useRouter()
const { user, isAuthenticated, fetchUser } = useAuth()
const api    = useApi()
const config = useRuntimeConfig()
const storageBase = `${(config.public.apiBaseUrl as string || 'http://localhost:8000').replace(/\/$/, '')}/storage`

const pageLoading   = ref(true)
const saving        = ref(false)
const saveSuccess   = ref(false)
const saveError     = ref('')
const uploadingFoto = ref(false)
const fotoSuccess   = ref(false)
const fotoError     = ref('')
const fotoFile      = ref<File | null>(null)
const fotoPreview   = ref<string | null>(null)

const form = ref({ nim: '', name: '', prodi: '', no_hp: '' })

const currentFotoUrl = computed(() => {
  const foto = user.value?.foto_profil as string | undefined
  if (!foto) return null
  return foto.startsWith('http') ? foto : `${storageBase}/${foto}`
})

onMounted(async () => {
  if (!isAuthenticated()) { router.push('/login'); return }
  const u = await fetchUser()
  if (!u || u.role !== 'mahasiswa') { router.push('/login'); return }
  form.value.nim   = (u as any).nim    || ''
  form.value.name  = u.name   || ''
  form.value.prodi = (u as any).prodi  || ''
  form.value.no_hp = (u as any).no_hp  || ''
  pageLoading.value = false
})

const onFotoSelected = (e: Event) => {
  fotoError.value   = ''
  fotoSuccess.value = false
  const input = e.target as HTMLInputElement
  const file  = input.files?.[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    fotoError.value = 'Ukuran file melebihi 2 MB.'
    return
  }
  fotoFile.value    = file
  fotoPreview.value = URL.createObjectURL(file)
}

const cancelFoto = () => {
  fotoFile.value    = null
  fotoPreview.value = null
  fotoError.value   = ''
}

const uploadFoto = async () => {
  if (!fotoFile.value) return
  uploadingFoto.value = true
  fotoError.value     = ''
  fotoSuccess.value   = false
  try {
    const fd = new FormData()
    fd.append('foto', fotoFile.value)
    const res = await api.postForm('/user/foto', fd)
    if (res.success) {
      fotoSuccess.value = true
      fotoFile.value    = null
      fotoPreview.value = null
      await fetchUser()
    } else {
      fotoError.value = res.message || 'Gagal mengunggah foto.'
    }
  } catch (err: any) {
    fotoError.value = err.message || 'Terjadi kesalahan saat mengunggah.'
  } finally {
    uploadingFoto.value = false
  }
}

const saveProfile = async () => {
  saving.value      = true
  saveSuccess.value = false
  saveError.value   = ''
  try {
    const res = await api.put('/user/profile', {
      nim:   form.value.nim.trim()   || undefined,
      name:  form.value.name.trim()  || undefined,
      prodi: form.value.prodi.trim() || undefined,
      no_hp: form.value.no_hp.trim() || undefined,
    })
    if (res.success) {
      saveSuccess.value = true
      await fetchUser()
      setTimeout(() => { saveSuccess.value = false }, 3500)
    } else {
      saveError.value = res.message || 'Gagal menyimpan perubahan.'
    }
  } catch (err: any) {
    saveError.value = err.message || 'Terjadi kesalahan saat menyimpan.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.profil-page { padding: 0.5rem 0 3rem; }

.page-header { margin-bottom: 2rem; }

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--text-dim);
  text-decoration: none;
  margin-bottom: 1rem;
  transition: color 0.15s;
}
.back-link:hover { color: var(--primary); }

.page-title { font-size: 1.6rem; font-weight: 700; color: var(--text); margin-bottom: 0.3rem; }
.page-desc  { font-size: 0.9rem; color: var(--text-muted); }

.profil-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1.5rem;
  align-items: start;
}

/* Photo card */
.photo-card { display: flex; flex-direction: column; gap: 0.85rem; }

.avatar-preview-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.85rem;
}

.avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-subtle, var(--border-subtle));
}
.avatar-img-lg { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials-lg {
  font-size: 2.5rem;
  font-weight: 700;
  color: #fff;
  background: var(--primary);
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-hint { font-size: 0.75rem; color: var(--text-dim); text-align: center; }

.photo-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
}

/* Form card */
.section-heading {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 1rem;
}

.sub-heading {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 0.85rem;
}

.info-readonly {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  margin-bottom: 1rem;
}
.readonly-item { display: flex; align-items: baseline; gap: 0.75rem; }
.readonly-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-dim);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  min-width: 60px;
}
.readonly-value { font-size: 0.9rem; color: var(--text); font-weight: 500; }
.capitalize { text-transform: capitalize; }

.divider { border: none; border-top: 1px solid var(--border); margin: 1.25rem 0; }

.profile-form { display: flex; flex-direction: column; gap: 1rem; }

.form-group { display: flex; flex-direction: column; gap: 0.3rem; }

.btn-sm { padding: 0.4rem 0.75rem; font-size: 0.82rem; }

/* Alerts */
.alert-success {
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--success) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--success) 30%, transparent);
  color: var(--success);
  font-size: 0.82rem;
}

.alert-error-sm {
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--danger) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--danger) 25%, transparent);
  color: var(--danger);
  font-size: 0.82rem;
}

@media (max-width: 720px) {
  .profil-grid { grid-template-columns: 1fr; }
}
</style>
