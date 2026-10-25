import axios from 'axios'
import { auth } from '../stores/auth'

// Alamat API TIDAK ditulis di dalam komponen: diambil dari variabel
// lingkungan Vite (VITE_API_URL), lihat frontend/.env.example.
const baseURL = import.meta.env.VITE_API_URL

if (!baseURL) {
  console.warn('VITE_API_URL belum diatur. Salin .env.example menjadi .env')
}

const http = axios.create({
  baseURL,
  headers: { Accept: 'application/json' },
})

// Kirim token pada SETIAP permintaan berikutnya.
http.interceptors.request.use((config) => {
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

let onUnauthorized = () => {}

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.endsWith('/login')
    if (error.response?.status === 401 && !isLoginRequest) {
      onUnauthorized()
    }
    return Promise.reject(error)
  },
)

export default http
