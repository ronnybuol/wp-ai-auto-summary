# AI Summarize Backend (PHP)

**Tujuan:** menyediakan endpoint `POST /summarize` untuk dipanggil plugin WordPress.

- Letakkan folder ini di `public_html/ai-summarize/`
- Ubah `config.php.sample` menjadi `config.php` dan isi `GEMINI_API_KEY`
- Opsional: set `OPTIONAL_BACKEND_API_KEY` untuk shared secret
- Endpoint stabil: `https://DOMAIN/ai-summarize/index.php`

Jika server mendukung `.htaccess`, akses juga bisa via `https://DOMAIN/ai-summarize/summarize`.
