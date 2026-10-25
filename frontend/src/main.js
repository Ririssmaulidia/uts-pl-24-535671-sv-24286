import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { setUnauthorizedHandler } from './api/http'
import { auth } from './stores/auth'

// Bila server menjawab 401 (token tidak valid/kedaluwarsa), buang token
// lalu arahkan pengguna kembali ke halaman login.
setUnauthorizedHandler(() => {
  auth.clear()
  router.push({ name: 'login' })
})

createApp(App).use(router).mount('#app')
