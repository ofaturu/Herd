<template>
  <div class="landing-container">
    <div class="hero-card">
      <div class="hero-badge">Portal Kemahasiswaan & Akademik</div>
      <h1 class="hero-title">Layanan Mahasiswa Mandiri</h1>
      <p class="hero-desc">
        Ajukan berbagai keperluan administrasi akademik seperti Surat Keterangan Aktif Kuliah, Cuti Akademik, Legalisir Ijazah, dan Transkrip Nilai secara digital, cepat, dan transparan.
      </p>

      <div class="hero-actions">
        <NuxtLink v-if="user" to="/mahasiswa" class="btn btn-primary">
          Masuk ke Dashboard
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </NuxtLink>
        <NuxtLink v-else to="/login" class="btn btn-primary">
          Login Portal Mahasiswa
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </NuxtLink>

        <NuxtLink to="/layanan" class="btn btn-secondary">
          Lihat Katalog Layanan
        </NuxtLink>
      </div>

      <div class="features-grid">
        <div class="feature-item">
          <div class="feature-icon">⚡</div>
          <h3>Proses Cepat</h3>
          <p>Pengajuan langsung diproses bagian akademik tanpa antre manual.</p>
        </div>
        <div class="feature-item">
          <div class="feature-icon">🔍</div>
          <h3>Tracking Real-time</h3>
          <p>Pantau status verifikasi dan catatan perbaikan kapan saja.</p>
        </div>
        <div class="feature-item">
          <div class="feature-icon">🔒</div>
          <h3>Aman & Resmi</h3>
          <p>Tervalidasi oleh sistem autentikasi dan database resmi universitas.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'

const { user, fetchUser } = useAuth()

onMounted(async () => {
  await fetchUser()
})
</script>

<style scoped>
.landing-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2.5rem 1rem;
}

.hero-card {
  max-width: 860px;
  width: 100%;
  text-align: center;
  background: var(--surface-card);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 3.5rem 2.5rem;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(12px);
}

.hero-badge {
  display: inline-block;
  padding: 0.35rem 1rem;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--primary-light);
  background: rgba(56, 189, 248, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.25);
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: 2.75rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 1.25rem;
  background: linear-gradient(135deg, #f8fafc 30%, #94a3b8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-desc {
  font-size: 1.125rem;
  color: var(--text-muted);
  line-height: 1.7;
  max-width: 680px;
  margin: 0 auto 2.5rem;
}

.hero-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 3.5rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  border-top: 1px solid var(--border-color);
  padding-top: 2.5rem;
  text-align: left;
}

.feature-item {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 1.25rem;
}

.feature-icon {
  font-size: 1.75rem;
  margin-bottom: 0.75rem;
}

.feature-item h3 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 0.35rem;
}

.feature-item p {
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.5;
}

@media (max-width: 640px) {
  .hero-card {
    padding: 2rem 1.25rem;
  }
  .hero-title {
    font-size: 2rem;
  }
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
