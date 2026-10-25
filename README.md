# UTS Konstruksi & Evolusi Perangkat Lunak — Catatan Pengeluaran Mahasiswa

Sistem full-stack: **Laravel 13 (RESTful API + Sanctum)** + **Vue 3 (Axios)** + **MySQL 8.4**,
dijalankan dengan Docker Compose dan dikirim ke GitHub Container Registry lewat GitHub Actions.

```
backend/    Laravel (API, token Sanctum, test, Postman collection)
frontend/   Vue 3 (login + CRUD lewat Axios)
docker-compose.yml   menjalankan db + backend + frontend
.github/workflows/ci.yml   uji backend, build frontend, deploy ke GHCR
```

## Menjalankan seluruh sistem

```bash
cp .env.example .env          # lalu isi APP_KEY dan password (lihat komentar di file)
docker compose up -d --build
```

- Aplikasi (Vue): http://localhost:8080
- API langsung (untuk Postman): http://localhost:8000/api
- Login demo: `SEED_USER_EMAIL` / `SEED_USER_PASSWORD` dari `.env`
  (bawaan: `mahasiswa@example.com` / `password123`)

Data MySQL disimpan di named volume `db_data`, jadi tetap ada setelah
`docker compose down` lalu `docker compose up -d`.

## Endpoint API

| Method | Path | Token | Keterangan |
|---|---|---|---|
| POST | `/api/login` | tidak | Login, mengembalikan token |
| POST | `/api/logout` | ya | Hapus token saat ini |
| GET | `/api/expenses` | ya | Tampil semua milik pengguna |
| GET | `/api/expenses/{id}` | ya | Tampil satu |
| POST | `/api/expenses` | ya | Tambah (201; 422 bila tidak valid) |
| PUT | `/api/expenses/{id}` | ya | Ubah |
| DELETE | `/api/expenses/{id}` | ya | Hapus |

Tanpa token: **401**. Koleksi Postman: `backend/postman/uts-expenses.postman_collection.json`.

## Pengujian

```bash
cd backend && php artisan test
```

## CI/CD

`backend-test` dan `frontend-build` berjalan di semua branch dan Pull Request.
Job `deploy` hanya untuk push ke `main`: membangun image backend dan frontend lalu
mendorongnya ke `ghcr.io` dengan tag nomor commit. Rahasia (`APP_KEY`, `DB_PASSWORD`)
disimpan di GitHub Secrets, bukan di repository.
