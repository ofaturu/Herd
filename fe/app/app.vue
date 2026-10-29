<template>
  <div class="app-layout" :data-theme="theme">
    <header class="app-navbar">
      <div class="nav-container">
        <NuxtLink to="/" class="nav-brand">
          <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
              <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
          </div>
          <div class="brand-text">
            <span class="brand-title">Portal Mahasiswa</span>
            <span class="brand-sub">Layanan Terpadu</span>
          </div>
        </NuxtLink>

        <nav class="nav-links">
          <template v-if="user?.role === 'admin'">
            <NuxtLink to="/admin" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              Dashboard
            </NuxtLink>
            <NuxtLink to="/admin/layanan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Kelola Layanan
            </NuxtLink>
            <NuxtLink to="/admin/pengajuan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Pengajuan
            </NuxtLink>
          </template>

          <template v-else-if="user?.role === 'mahasiswa'">
            <NuxtLink to="/mahasiswa" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              Dashboard
            </NuxtLink>
            <NuxtLink to="/layanan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Daftar Layanan
            </NuxtLink>
            <NuxtLink to="/pengajuan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Riwayat
            </NuxtLink>
          </template>

          <template v-else>
            <NuxtLink to="/layanan" class="nav-link" active-class="nav-link-active">Layanan</NuxtLink>
          </template>
        </nav>

        <div class="nav-right">
          <!-- Theme Toggle -->
          <button
            id="btn-theme-toggle"
            class="btn-icon"
            :title="theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
            @click="toggleTheme"
            aria-label="Toggle tema"
          >
            <svg v-if="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
          </button>

          <template v-if="user">
            <!-- Profile button (mahasiswa only) -->
            <NuxtLink
              v-if="user.role === 'mahasiswa'"
              id="btn-profile"
              to="/profil"
              class="user-avatar-btn"
              title="Ubah data diri"
              aria-label="Halaman profil"
            >
              <img
                v-if="userFotoUrl"
                :src="userFotoUrl"
                class="avatar-img"
                alt="Foto profil"
              />
              <span v-else class="avatar-initials" :class="{ 'avatar-admin': user.role === 'admin' }">
                {{ user.name?.charAt(0) || 'M' }}
              </span>
            </NuxtLink>
            <div v-else class="user-avatar-btn no-link">
              <span class="avatar-initials avatar-admin">{{ user.name?.charAt(0) || 'A' }}</span>
            </div>

            <div class="user-info-block">
              <span class="user-name">{{ user.name }}</span>
              <span class="user-meta">{{ user.role === 'admin' ? 'Administrator' : (user.nim || 'Mahasiswa') }}</span>
            </div>

            <button
              id="btn-logout"
              class="btn btn-outline-danger"
              title="Keluar dari sistem"
              @click="handleLogout"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
              <span class="btn-label">Keluar</span>
            </button>
          </template>
          <template v-else>
            <NuxtLink to="/login" class="btn btn-primary">Masuk</NuxtLink>
          </template>
        </div>
      </div>
    </header>

    <main class="page-body">
      <NuxtPage />
    </main>

    <footer class="app-footer">
      <div class="footer-inner">
        <span>© 2026 Portal Layanan Mahasiswa</span>
        <div class="footer-status">
          <span class="status-dot" aria-hidden="true"></span>
          <span>Sistem Aktif</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useRuntimeConfig } from '#app'

const { user, fetchUser, logout } = useAuth()
const config = useRuntimeConfig()
const storageBase = `${(config.public.apiBaseUrl as string || 'http://localhost:8000').replace(/\/$/, '')}/storage`

const theme = ref<'dark' | 'light'>('dark')

onMounted(async () => {
  const saved = localStorage.getItem('portal-theme') as 'dark' | 'light' | null
  if (saved === 'light' || saved === 'dark') theme.value = saved
  await fetchUser()
})

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  localStorage.setItem('portal-theme', theme.value)
}

const userFotoUrl = computed(() => {
  if (!user.value?.foto_profil) return null
  const p = user.value.foto_profil as string
  return p.startsWith('http') ? p : `${storageBase}/${p}`
})

const handleLogout = async () => {
  if (confirm('Yakin ingin keluar?')) {
    await logout()
  }
}
</script>

<style>
/* ─────────────────────────────────────────────
   Google Font
───────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ─────────────────────────────────────────────
   Design tokens – dark (default)
───────────────────────────────────────────── */
:root,
[data-theme="dark"] {
  --bg:           #0e1117;
  --bg-subtle:    #161b25;
  --surface:      rgba(22, 27, 37, 0.9);
  --surface-hover:rgba(28, 35, 50, 0.95);
  --border:       rgba(255,255,255,0.09);
  --border-subtle:rgba(255,255,255,0.05);

  --primary:      #3b82f6;
  --primary-dark: #2563eb;
  --primary-text: #93c5fd;

  --text:         #f1f5f9;
  --text-muted:   #94a3b8;
  --text-dim:     #64748b;

  --success:      #22c55e;
  --warning:      #f59e0b;
  --danger:       #ef4444;
  --info:         #06b6d4;

  --radius-sm:    6px;
  --radius:       10px;
  --radius-lg:    16px;
  --font:         'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ─────────────────────────────────────────────
   Design tokens – light
───────────────────────────────────────────── */
[data-theme="light"] {
  --bg:           #f4f6fb;
  --bg-subtle:    #eef1f7;
  --surface:      rgba(255,255,255,0.92);
  --surface-hover:rgba(255,255,255,1);
  --border:       rgba(0,0,0,0.1);
  --border-subtle:rgba(0,0,0,0.06);

  --primary:      #2563eb;
  --primary-dark: #1d4ed8;
  --primary-text: #1e40af;

  --text:         #111827;
  --text-muted:   #4b5563;
  --text-dim:     #9ca3af;
}

/* ─────────────────────────────────────────────
   Compatibility aliases for page-level CSS
───────────────────────────────────────────── */
:root {
  --bg-base:            var(--bg);
  --surface-card:       var(--surface);
  --surface-card-hover: var(--surface-hover);
  --border-color:       var(--border);
  --text-main:          var(--text);
  --primary-light:      var(--primary-text);
}

/* ─────────────────────────────────────────────
   Base reset & typography
───────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: var(--font);
  background-color: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  transition: background-color 0.25s, color 0.25s;
}

h1, h2, h3, h4, h5, h6 {
  color: var(--text);
}

input, select, textarea {
  color: var(--text);
  background-color: var(--bg-subtle);
  border: 1px solid var(--border);
  font-family: inherit;
}

input::placeholder, textarea::placeholder {
  color: var(--text-dim);
}

/* ─────────────────────────────────────────────
   Layout
───────────────────────────────────────────── */
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ─────────────────────────────────────────────
   Navbar
───────────────────────────────────────────── */
.app-navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: var(--surface);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--border);
  transition: background 0.25s;
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 56px;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: inherit;
  flex-shrink: 0;
}

.brand-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.brand-text { display: flex; flex-direction: column; line-height: 1.1; }
.brand-title { font-size: 0.95rem; font-weight: 700; color: var(--text); }
.brand-sub   { font-size: 0.65rem; font-weight: 500; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.06em; }

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex: 1;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
  transition: color 0.15s, background 0.15s;
}

.nav-link:hover { color: var(--text); background: var(--border-subtle); }
.nav-link-active { color: var(--primary); background: color-mix(in srgb, var(--primary) 10%, transparent); font-weight: 600; }

/* Right area */
.nav-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-left: auto;
  flex-shrink: 0;
}

/* Icon-only button (theme toggle) */
.btn-icon {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s, background 0.15s;
}
.btn-icon:hover { color: var(--text); background: var(--border-subtle); }

/* Avatar button (profile link) */
.user-avatar-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  text-decoration: none;
  flex-shrink: 0;
  border: 2px solid var(--border);
  transition: border-color 0.15s;
}
.user-avatar-btn:hover { border-color: var(--primary); }
.user-avatar-btn.no-link { cursor: default; }
.user-avatar-btn.no-link:hover { border-color: var(--border); }

.avatar-img { width: 100%; height: 100%; object-fit: cover; }

.avatar-initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  background: var(--primary);
  color: #fff;
}
.avatar-admin { background: #7c3aed; }

.user-info-block {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
}
.user-name { font-size: 0.82rem; font-weight: 600; color: var(--text); }
.user-meta { font-size: 0.69rem; color: var(--text-dim); }

/* ─────────────────────────────────────────────
   Page body
───────────────────────────────────────────── */
.page-body {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 1.75rem 1.5rem 3rem;
}

/* ─────────────────────────────────────────────
   Footer
───────────────────────────────────────────── */
.app-footer {
  border-top: 1px solid var(--border);
  background: var(--surface);
  padding: 0.9rem 1.5rem;
  font-size: 0.78rem;
  color: var(--text-dim);
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.footer-status { display: flex; align-items: center; gap: 0.4rem; }
.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--success);
}

/* ─────────────────────────────────────────────
   Global button utilities
───────────────────────────────────────────── */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 600;
  font-family: inherit;
  text-decoration: none;
  cursor: pointer;
  border: 1px solid transparent;
  transition: background 0.15s, color 0.15s, border-color 0.15s, transform 0.1s;
  white-space: nowrap;
}

.btn-primary {
  background: var(--primary);
  color: #fff;
  border-color: transparent;
}
.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

.btn-secondary {
  background: var(--border-subtle);
  color: var(--text);
  border-color: var(--border);
}
.btn-secondary:hover:not(:disabled) { background: var(--border); }

.btn-outline-danger {
  background: transparent;
  color: var(--danger);
  border-color: color-mix(in srgb, var(--danger) 35%, transparent);
  padding: 0.4rem 0.75rem;
  font-size: 0.82rem;
}
.btn-outline-danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--danger) 10%, transparent);
  border-color: color-mix(in srgb, var(--danger) 55%, transparent);
}

.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

/* ─────────────────────────────────────────────
   Card, form, badge utilities (shared)
───────────────────────────────────────────── */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
}

.form-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 0.35rem;
}
.form-input {
  width: 100%;
  padding: 0.6rem 0.85rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-size: 0.875rem;
  font-family: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent);
}

/* Status badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid transparent;
}
.badge-diajukan  { background: color-mix(in srgb, var(--info) 15%, transparent);    color: var(--info);    border-color: color-mix(in srgb, var(--info) 30%, transparent); }
.badge-diproses  { background: color-mix(in srgb, var(--primary) 15%, transparent); color: var(--primary-text); border-color: color-mix(in srgb, var(--primary) 30%, transparent); }
.badge-perbaikan { background: color-mix(in srgb, var(--warning) 15%, transparent); color: var(--warning); border-color: color-mix(in srgb, var(--warning) 30%, transparent); }
.badge-selesai   { background: color-mix(in srgb, var(--success) 15%, transparent); color: var(--success); border-color: color-mix(in srgb, var(--success) 30%, transparent); }
.badge-ditolak   { background: color-mix(in srgb, var(--danger) 15%, transparent);  color: var(--danger);  border-color: color-mix(in srgb, var(--danger) 30%, transparent); }

/* Spinner */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid color-mix(in srgb, var(--primary) 30%, transparent);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
  flex-shrink: 0;
}
.spinner-lg {
  width: 36px;
  height: 36px;
  border-width: 3px;
  margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Loading / error / empty state container */
.state-box {
  text-align: center;
  padding: 4rem 1rem;
  color: var(--text-muted);
}
.state-box p { margin-top: 0.5rem; font-size: 0.9rem; }

/* ─────────────────────────────────────────────
   Responsive
───────────────────────────────────────────── */
@media (max-width: 768px) {
  .nav-container { flex-wrap: wrap; height: auto; padding: 0.65rem 1rem; gap: 0.65rem; }
  .nav-links { order: 3; width: 100%; overflow-x: auto; gap: 0.15rem; }
  .user-info-block { display: none; }
  .btn-label { display: none; }
}
</style>
