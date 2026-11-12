# Deployment FORISTA (Docker + Coolify)

Aplikasi berjalan sebagai tiga container PHP yang berbagi satu image:

| Service     | Peran                                                         |
| ----------- | ------------------------------------------------------------- |
| `web`       | nginx + php-fpm, menjalankan migrasi saat start, health `/up`  |
| `worker`    | `queue:work` untuk notifikasi pengingat (ShouldQueue)          |
| `scheduler` | `schedule:work` untuk `forista:dispatch-reminders` tiap menit  |
| `db`        | PostgreSQL 17 (produksi; SQLite hanya untuk lokal)             |

## Wajib diisi sebelum deploy

Coolify → *Environment Variables*:

```
APP_KEY=base64:...            # php artisan key:generate --show
APP_URL=https://absensi.domain.id
APP_ENV=production
DB_PASSWORD=<kata sandi kuat>
VAPID_SUBJECT=mailto:admin@domain.id
VAPID_PUBLIC_KEY=...          # generate ulang untuk produksi
VAPID_PRIVATE_KEY=...
MAIL_MAILER=smtp              # opsional, hanya untuk reset manual
```

`SESSION_SECURE_COOKIE=true` sudah di-set di compose; butuh HTTPS (disediakan Coolify/Traefik).

## Langkah deploy Coolify

1. Buat resource **Docker Compose** dari repo ini, branch `main`.
2. Set `docker-compose.yml` sebagai compose path; isi environment di atas.
3. Domain → container `web`, port `8080`, aktifkan HTTPS.
4. Deploy. Migrasi berjalan otomatis di container `web` (idempoten).
5. Seed awal sekali: `docker compose exec web php artisan db:seed --force`.
6. Cek `https://domain/up` → 200.

## Catatan operasional

- Volume `pgdata` wajib persisten; backup harian dengan `pg_dump`.
- Jangan scale `web` > 1 selama migrasi masih di entrypoint (bisa balapan). Untuk multi-replika, pindahkan migrasi ke release job.
- `opcache.validate_timestamps=0`; setiap deploy membangun image baru, jadi aman.
- `storage:link` dijalankan sekali per start container dan idempoten.
- VAPID key produksi berbeda dari `.env` lokal; subscription lama harus dibuat ulang.
- Cache `config`/`route`/`view` dibangun saat runtime karena env platform baru tersedia di titik itu.
