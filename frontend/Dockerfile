# syntax=docker/dockerfile:1

# ── Tahap 1: build (Node.js) ────────────────────────────────
FROM node:24-alpine AS build

WORKDIR /app

# Daftar dependensi dulu supaya cache "npm ci" awet.
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Alamat API untuk hasil build. "/api" = path relatif; Nginx di tahap 2
# meneruskannya ke service "backend". Bukan rahasia (ikut tertanam di JS).
ARG VITE_API_URL=/api
ENV VITE_API_URL=$VITE_API_URL

RUN npm run build

# ── Tahap 2: menyajikan hasil build (Nginx) ─────────────────
# Image akhir hanya berisi Nginx + folder dist. TIDAK ada node_modules.
FROM nginx:1.28-alpine AS runtime

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
