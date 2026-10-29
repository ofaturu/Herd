<template>
  <div class="app-layout">
    <header class="app-navbar">
      <div class="nav-container">
        <NuxtLink to="/" class="nav-brand">
          <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
              <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
          </div>
          <div class="brand-text">
            <span class="brand-title">Portal Mahasiswa</span>
            <span class="brand-subtitle">Layanan Terpadu</span>
          </div>
        </NuxtLink>

        <nav class="nav-links">
          <!-- Admin Navigation -->
          <template v-if="user?.role === 'admin'">
            <NuxtLink to="/admin" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              Dashboard Admin
            </NuxtLink>
            <NuxtLink to="/admin/layanan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Kelola Layanan
            </NuxtLink>
            <NuxtLink to="/admin/pengajuan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
              Kelola Pengajuan
            </NuxtLink>
          </template>

          <!-- Mahasiswa Navigation -->
          <template v-else-if="user?.role === 'mahasiswa'">
            <NuxtLink to="/mahasiswa" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              Dashboard
            </NuxtLink>
            <NuxtLink to="/layanan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Daftar Layanan
            </NuxtLink>
            <NuxtLink to="/pengajuan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
              Riwayat Pengajuan
            </NuxtLink>
          </template>

          <!-- Guest Navigation -->
          <template v-else>
            <NuxtLink to="/layanan" class="nav-link" active-class="nav-link-active">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Daftar Layanan
            </NuxtLink>
          </template>
        </nav>

        <div class="nav-actions">
          <template v-if="user">
            <div class="user-chip">
              <div class="user-avatar" :class="{ 'avatar-admin': user.role === 'admin' }">
                {{ user.name?.charAt(0) || (user.role === 'admin' ? 'A' : 'M') }}
              </div>
              <div class="user-info">
                <span class="user-name">{{ user.name }}</span>
                <span class="user-meta">{{ user.role === 'admin' ? 'Administrator' : (user.nim || 'Mahasiswa') }}</span>
              </div>
            </div>
            <button @click="handleLogout" class="btn btn-outline-danger" title="Logout dari sistem">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
              <span>Logout</span>
            </button>
          </template>
          <template v-else>
            <NuxtLink to="/login" class="btn btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
              <span>Login Portal</span>
            </NuxtLink>
          </template>
        </div>
      </div>
    </header>

    <main class="page-body">
      <NuxtPage />
    </main>

    <footer class="app-footer">
      <div class="footer-container">
        <p>© 2026 Portal Layanan Mahasiswa • Terintegrasi dengan Laravel 12 Sanctum API</p>
        <div class="system-status">
          <span class="status-dot"></span>
          <span>Sistem Aktif & Terhubung</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { user, fetchUser, logout } = useAuth()

onMounted(async () => {
  await fetchUser()
})

const handleLogout = async () => {
  if (confirm('Apakah Anda yakin ingin logout?')) {
    await logout()
  }
}
</script>

<style>
/* Modern CSS Reset & Design Tokens */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

:root {
  --bg-base: #090d16;
  --bg-gradient: radial-gradient(circle at 50% 0%, #151f38 0%, #090d16 65%);
  --surface-card: rgba(19, 27, 46, 0.75);
  --surface-card-hover: rgba(26, 38, 64, 0.85);
  --border-color: rgba(255, 255, 255, 0.1);
  --border-subtle: rgba(255, 255, 255, 0.05);
  --primary: #3b82f6;
  --primary-hover: #2563eb;
  --primary-light: #60a5fa;
  --text-main: #f1f5f9;
  --text-muted: #94a3b8;
  --text-dim: #64748b;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #06b6d4;
  --radius: 12px;
  --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: var(--font-family);
  background-color: var(--bg-base);
  background-image: var(--bg-gradient);
  background-attachment: fixed;
  color: var(--text-main);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.app-navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(9, 13, 22, 0.82);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border-color);
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.85rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: inherit;
}

.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #38bdf8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.01em;
}

.brand-subtitle {
  font-size: 0.72rem;
  color: var(--primary-light);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.nav-link:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.06);
}

.nav-link-active {
  color: var(--primary-light);
  background: rgba(59, 130, 246, 0.12);
  font-weight: 600;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.35rem 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-subtle);
  border-radius: 9999px;
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
}

.avatar-admin {
  background: linear-gradient(135deg, #ec4899, #8b5cf6) !important;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-main);
  line-height: 1.1;
}

.user-meta {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.page-body {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
}

.app-footer {
  border-top: 1px solid var(--border-color);
  background: rgba(9, 13, 22, 0.6);
  padding: 1.25rem 1.5rem;
  font-size: 0.82rem;
  color: var(--text-dim);
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.system-status {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--text-muted);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 8px var(--success);
}

/* Button & UI Utilities */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  font-family: inherit;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid transparent;
}

.btn-primary {
  background: var(--primary);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.08);
  color: var(--text-main);
  border-color: var(--border-color);
}

.btn-secondary:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.12);
}

.btn-outline-danger {
  background: transparent;
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.3);
  padding: 0.4rem 0.8rem;
  font-size: 0.82rem;
}

.btn-outline-danger:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.5);
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

@media (max-width: 768px) {
  .nav-container {
    flex-wrap: wrap;
    gap: 1rem;
  }
  .nav-links {
    order: 3;
    width: 100%;
    justify-content: center;
    overflow-x: auto;
  }
  .user-info {
    display: none;
  }
}
</style>
