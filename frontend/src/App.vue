<script setup>
import { useRouter } from 'vue-router'
import http from './api/http'
import { auth } from './stores/auth'

const router = useRouter()

async function logout() {
  try {
    await http.post('/logout')
  } finally {
    // Token dibuang di sisi browser apa pun hasilnya.
    auth.clear()
    router.push({ name: 'login' })
  }
}
</script>

<template>
  <header class="topbar">
    <h1>💸 Catatan Pengeluaran Mahasiswa</h1>
    <div v-if="auth.isLoggedIn" class="user">
      <span>{{ auth.user?.name }}</span>
      <button class="btn btn-light" @click="logout">Logout</button>
    </div>
  </header>

  <main class="container">
    <RouterView />
  </main>
</template>

<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: -apple-system, 'Segoe UI', Arial, sans-serif; background: #f3f4f8; color: #1f2937; }
.topbar { background: #1e3a5f; color: #fff; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
.topbar h1 { margin: 0; font-size: 1.15rem; }
.user { display: flex; align-items: center; gap: 12px; }
.container { max-width: 860px; margin: 24px auto; padding: 0 16px; }
.card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); margin-bottom: 14px; }
label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 4px; }
input, select, textarea { width: 100%; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; margin-bottom: 12px; }
.btn { border: 0; border-radius: 6px; padding: 9px 14px; font: inherit; cursor: pointer; background: #4f46e5; color: #fff; }
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn-light { background: #e5e7eb; color: #111827; }
.btn-danger { background: #dc2626; }
.error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; font-size: .9rem; }
.error ul { margin: 6px 0 0; padding-left: 18px; }
.ok { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; font-size: .9rem; }
.muted { color: #6b7280; font-size: .9rem; }
</style>
