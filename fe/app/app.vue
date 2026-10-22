<script setup lang="ts">
import { ref } from 'vue'

const config = useRuntimeConfig()
const apiKey = ref(config.public.apiKey || 'SECRET_KEY_123456')
const baseUrl = ref(config.public.apiBaseUrl || 'http://localhost:8000')

const loading = ref(false)
const responseData = ref<any>(null)
const errorData = ref<any>(null)
const lastStatusCode = ref<number | null>(null)

const fetchData = async (overrideKey?: string) => {
  loading.value = true
  responseData.value = null
  errorData.value = null
  lastStatusCode.value = null

  const keyToUse = overrideKey !== undefined ? overrideKey : apiKey.value

  try {
    const res = await fetch(`${baseUrl.value}/api/data`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        ...(keyToUse ? { 'X-API-KEY': keyToUse } : {})
      }
    })

    lastStatusCode.value = res.status
    const json = await res.json()

    if (!res.ok) {
      errorData.value = json
    } else {
      responseData.value = json
    }
  } catch (err: any) {
    lastStatusCode.value = 0
    errorData.value = {
      success: false,
      message: err.message || 'Gagal menghubungi Backend. Pastikan backend Laravel sedang berjalan (misal: php artisan serve).'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="app-container">
    <div class="card">
      <header class="header">
        <div class="badge">Nuxt 4 + Laravel 12</div>
        <h1>Backend & Frontend API Key Test</h1>
        <p class="subtitle">Uji komunikasi antara Frontend dan Backend dengan header <code>X-API-KEY</code></p>
      </header>

      <section class="form-section">
        <div class="form-group">
          <label for="backend-url">Backend Base URL</label>
          <input id="backend-url" v-model="baseUrl" type="text" placeholder="http://localhost:8000" />
        </div>

        <div class="form-group">
          <label for="api-key-input">X-API-KEY Header Value</label>
          <div class="input-wrapper">
            <input id="api-key-input" v-model="apiKey" type="text" placeholder="Masukkan API Key..." />
            <button class="btn-ghost" @click="apiKey = 'SECRET_KEY_123456'">Reset Default</button>
          </div>
        </div>

        <div class="actions">
          <button class="btn btn-primary" :disabled="loading" @click="fetchData()">
            <span v-if="loading">Mengirim Permintaan...</span>
            <span v-else>Kirim Request (Dengan API Key)</span>
          </button>
          <button class="btn btn-danger" :disabled="loading" @click="fetchData('')">
            Uji Tanpa Key (Harus 401)
          </button>
        </div>
      </section>

      <section v-if="lastStatusCode !== null" class="result-section">
        <div class="result-header">
          <span class="status-indicator" :class="lastStatusCode === 200 ? 'status-ok' : 'status-err'">
            Status: {{ lastStatusCode === 0 ? 'Connection Error' : lastStatusCode }}
          </span>
          <span class="endpoint-badge">GET /api/data</span>
        </div>

        <div v-if="responseData" class="response-box response-success">
          <h3>Response Berhasil:</h3>
          <pre>{{ JSON.stringify(responseData, null, 2) }}</pre>
        </div>

        <div v-if="errorData" class="response-box response-error">
          <h3>Response Error / Ditolak:</h3>
          <pre>{{ JSON.stringify(errorData, null, 2) }}</pre>
        </div>
      </section>

      <section class="info-guide">
        <h3>Petunjuk Testing Postman</h3>
        <ol>
          <li>Method: <code>GET</code></li>
          <li>URL: <code>{{ baseUrl }}/api/data</code></li>
          <li>Headers: Tambahkan key <code>X-API-KEY</code> dengan value <code>SECRET_KEY_123456</code></li>
          <li>Tekan <strong>Send</strong> &rarr; Backend akan mengembalikan status <code>200 OK</code>.</li>
        </ol>
      </section>
    </div>
  </main>
</template>

<style scoped>
:global(body) {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  background-color: #0d1117;
  color: #e6edf3;
}

.app-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  box-sizing: border-box;
}

.card {
  width: 100%;
  max-width: 680px;
  background: #161b22;
  border: 1px solid #30363d;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}

.header {
  margin-bottom: 1.5rem;
  text-align: center;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #58a6ff;
  background: rgba(56, 139, 253, 0.15);
  border: 1px solid rgba(56, 139, 253, 0.4);
  border-radius: 999px;
  margin-bottom: 0.75rem;
}

h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.6rem;
  color: #f0f6fc;
}

.subtitle {
  margin: 0;
  color: #8b949e;
  font-size: 0.95rem;
}

.subtitle code {
  color: #f0883e;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #21262d;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #8b949e;
}

input {
  background: #0d1117;
  border: 1px solid #30363d;
  border-radius: 6px;
  padding: 0.65rem 0.85rem;
  color: #c9d1d9;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s;
  width: 100%;
  box-sizing: border-box;
}

input:focus {
  border-color: #58a6ff;
}

.input-wrapper {
  display: flex;
  gap: 0.5rem;
}

.actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.65rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: background-color 0.2s, opacity 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #238636;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2ea043;
}

.btn-danger {
  background-color: #da3633;
  color: #ffffff;
}

.btn-danger:hover:not(:disabled) {
  background-color: #f85149;
}

.btn-ghost {
  background: transparent;
  border: 1px solid #30363d;
  color: #8b949e;
  border-radius: 6px;
  padding: 0 0.85rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-ghost:hover {
  background: #21262d;
  color: #c9d1d9;
}

.result-section {
  background: #0d1117;
  border: 1px solid #30363d;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.status-indicator {
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 700;
}

.status-ok {
  background: rgba(46, 160, 67, 0.2);
  color: #3fb950;
  border: 1px solid rgba(46, 160, 67, 0.4);
}

.status-err {
  background: rgba(248, 81, 73, 0.2);
  color: #f85149;
  border: 1px solid rgba(248, 81, 73, 0.4);
}

.endpoint-badge {
  font-family: monospace;
  font-size: 0.8rem;
  color: #8b949e;
}

.response-box pre {
  margin: 0;
  padding: 0.75rem;
  background: #161b22;
  border-radius: 6px;
  overflow-x: auto;
  font-family: monospace;
  font-size: 0.85rem;
  color: #e6edf3;
}

.response-box h3 {
  margin: 0 0 0.5rem 0;
  font-size: 0.85rem;
  color: #8b949e;
}

.info-guide {
  background: rgba(56, 139, 253, 0.05);
  border: 1px solid rgba(56, 139, 253, 0.2);
  border-radius: 8px;
  padding: 1rem;
}

.info-guide h3 {
  margin: 0 0 0.5rem 0;
  font-size: 0.95rem;
  color: #58a6ff;
}

.info-guide ol {
  margin: 0;
  padding-left: 1.25rem;
  color: #8b949e;
  font-size: 0.85rem;
  line-height: 1.6;
}

.info-guide code {
  color: #f0883e;
  background: rgba(110, 118, 129, 0.1);
  padding: 0.1rem 0.3rem;
  border-radius: 3px;
}
</style>
