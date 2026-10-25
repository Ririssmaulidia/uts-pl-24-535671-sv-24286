<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../api/http'
import { auth } from '../stores/auth'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await http.post('/login', {
      email: email.value,
      password: password.value,
    })
    // Simpan token; interceptor Axios akan mengirimkannya di setiap permintaan.
    auth.set(data.token, data.user)
    router.push({ name: 'expenses' })
  } catch (e) {
    if (e.response?.status === 401) {
      error.value = e.response.data.message
    } else if (e.response?.status === 422) {
      error.value = 'Email dan password wajib diisi dengan benar.'
    } else {
      error.value = 'Tidak dapat menghubungi server. Pastikan backend berjalan.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="card login">
    <h2>Login</h2>
    <div v-if="error" class="error">{{ error }}</div>

    <form @submit.prevent="submit">
      <label for="email">Email</label>
      <input id="email" v-model="email" type="email" autocomplete="username" required />

      <label for="password">Password</label>
      <input id="password" v-model="password" type="password" autocomplete="current-password" required />

      <button class="btn" type="submit" :disabled="loading">
        {{ loading ? 'Memproses…' : 'Masuk' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.login { max-width: 380px; margin: 40px auto; }
</style>
