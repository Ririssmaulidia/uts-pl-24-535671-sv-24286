<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http from '../api/http'

const CATEGORIES = ['Makan', 'Transport', 'Kuliah', 'Hiburan', 'Lainnya']

const expenses = ref([])
const loading = ref(true)
const loadError = ref('')
const message = ref('')
const formErrors = ref([])
const saving = ref(false)
const editingId = ref(null)

const today = () => new Date().toISOString().slice(0, 10)
const emptyForm = () => ({ title: '', amount: '', category: 'Makan', spent_on: today(), note: '' })
const form = reactive(emptyForm())

const total = computed(() => expenses.value.reduce((sum, e) => sum + e.amount, 0))
const rupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await http.get('/expenses')
    expenses.value = data.data
  } catch {
    loadError.value = 'Gagal memuat data dari server.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(form, emptyForm())
  editingId.value = null
  formErrors.value = []
}

function startEdit(expense) {
  editingId.value = expense.id
  Object.assign(form, {
    title: expense.title,
    amount: expense.amount,
    category: expense.category,
    spent_on: expense.spent_on,
    note: expense.note ?? '',
  })
  formErrors.value = []
  message.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function save() {
  saving.value = true
  formErrors.value = []
  message.value = ''
  const payload = { ...form, amount: Number(form.amount), note: form.note || null }
  try {
    if (editingId.value) {
      await http.put(`/expenses/${editingId.value}`, payload)
      message.value = 'Pengeluaran berhasil diubah.'
    } else {
      await http.post('/expenses', payload)
      message.value = 'Pengeluaran berhasil ditambahkan.'
    }
    resetForm()
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      formErrors.value = Object.values(e.response.data.errors).flat()
    } else {
      formErrors.value = ['Terjadi kesalahan saat menyimpan data.']
    }
  } finally {
    saving.value = false
  }
}

async function remove(expense) {
  if (!confirm(`Hapus "${expense.title}"?`)) return
  try {
    await http.delete(`/expenses/${expense.id}`)
    message.value = 'Pengeluaran berhasil dihapus.'
    if (editingId.value === expense.id) resetForm()
    await load()
  } catch {
    loadError.value = 'Gagal menghapus data.'
  }
}

onMounted(load)
</script>

<template>
  <section>
    <div class="card">
      <h2>{{ editingId ? 'Ubah Pengeluaran' : 'Tambah Pengeluaran' }}</h2>

      <div v-if="formErrors.length" class="error">
        Data belum valid:
        <ul><li v-for="(err, i) in formErrors" :key="i">{{ err }}</li></ul>
      </div>

      <form @submit.prevent="save">
        <label for="title">Judul</label>
        <input id="title" v-model="form.title" type="text" maxlength="100" />

        <label for="amount">Jumlah (Rp)</label>
        <input id="amount" v-model="form.amount" type="number" min="1" />

        <label for="category">Kategori</label>
        <select id="category" v-model="form.category">
          <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
        </select>

        <label for="spent_on">Tanggal</label>
        <input id="spent_on" v-model="form.spent_on" type="date" />

        <label for="note">Catatan (opsional)</label>
        <input id="note" v-model="form.note" type="text" maxlength="255" />

        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Menyimpan…' : editingId ? 'Simpan Perubahan' : 'Tambah' }}
        </button>
        <button v-if="editingId" class="btn btn-light" type="button" @click="resetForm">Batal</button>
      </form>
    </div>

    <div v-if="message" class="ok">{{ message }}</div>

    <div class="card">
      <h2>Daftar Pengeluaran</h2>
      <p v-if="loading" class="muted">Memuat data dari server…</p>
      <p v-else-if="loadError" class="error">{{ loadError }}</p>
      <template v-else>
        <p class="muted">Total: <strong>{{ rupiah(total) }}</strong> ({{ expenses.length }} catatan)</p>
        <p v-if="!expenses.length" class="muted">Belum ada pengeluaran.</p>

        <article v-for="e in expenses" :key="e.id" class="item">
          <div>
            <strong>{{ e.title }}</strong> · {{ rupiah(e.amount) }}
            <div class="muted">{{ e.category }} · {{ e.spent_on }}<span v-if="e.note"> · {{ e.note }}</span></div>
          </div>
          <div class="actions">
            <button class="btn btn-light" @click="startEdit(e)">Ubah</button>
            <button class="btn btn-danger" @click="remove(e)">Hapus</button>
          </div>
        </article>
      </template>
    </div>
  </section>
</template>

<style scoped>
.item { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid #e5e7eb; flex-wrap: wrap; }
.actions { display: flex; gap: 6px; }
</style>
