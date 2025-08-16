# wp-ai-auto-summary

Plugin WordPress **AI Auto Summary (On‑Demand)** + backend (**PHP untuk shared hosting**, dan contoh **FastAPI**).  
Repo ini memuat semua kode yang sudah diuji di situs wordpress.

## Isi repo
- `plugin/ai-auto-summary-ondemand-patched/` — Plugin WordPress (on‑demand, versi patched).
- `backend-php/ai-summarize/` — Backend PHP berbasis Gemini untuk shared hosting (public_html).
- `backend-fastapi/` — Contoh backend FastAPI (opsional).

## Quickstart: push ke GitHub
```bash
unzip wp-ai-auto-summary.zip -d wp-ai-auto-summary
cd wp-ai-auto-summary
git init
git add .
git commit -m "init: wp-ai-auto-summary"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/wp-ai-auto-summary.git
git push -u origin main
```

## Konfigurasi ringkas
- **Endpoint plugin**: `https://DOMAIN/ai-summarize/index.php`
- **Backend API Key** (opsional): isi sama di plugin & `config.php` → `OPTIONAL_BACKEND_API_KEY`
- **Ambang paragraf**: 15 (bisa diubah)
- **Panjang ringkasan**: 100 kata (bisa diubah)

## Build ZIP otomatis (opsional, via GitHub Actions)
- Workflow ada di `.github/workflows/build-zips.yml`.
- Buat tag:
```bash
git tag v1.0.0
git push origin v1.0.0
```
- Release akan memuat:
  - `ai-auto-summary-ondemand-patched.zip`
  - `ai-summarize-php.zip`

## Lisensi
- Plugin WordPress: GPL-2.0-or-later
- Backend PHP & FastAPI: MIT
