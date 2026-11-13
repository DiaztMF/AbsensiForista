# Deployment FORISTA (Docker + Coolify)

Domain produksi: **https://forista.klikhadir.my.id** (subdomain; `klikhadir.my.id` dipakai aplikasi lain).

Aplikasi berjalan sebagai tiga container PHP yang berbagi satu image:

| Service     | Peran                                                         |
| ----------- | ------------------------------------------------------------- |
| `web`       | nginx + php-fpm, migrasi saat start, health check `/up`        |
| `worker`    | `queue:work` untuk notifikasi pengingat (ShouldQueue)          |
| `scheduler` | `schedule:work` untuk `forista:dispatch-reminders` tiap menit  |
| `db`        | PostgreSQL 17 (produksi; SQLite hanya untuk lokal)             |

## Isolasi dari domain utama

Cookie sesi dipin ke host subdomain lewat `SESSION_DOMAIN=forista.klikhadir.my.id`, sehingga tidak bertabrakan dengan aplikasi di `klikhadir.my.id`. Jangan ganti ke `.klikhadir.my.id` kecuali kedua aplikasi memang ingin berbagi sesi.

## Wajib diisi sebelum deploy

Coolify → *Environment Variables*:

```
APP_KEY=base64:...            # php artisan key:generate --show
APP_URL=https://forista.klikhadir.my.id
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=<kata sandi kuat>
SEEDER_ADMIN_PASSWORD=<kata sandi admin awal>
VAPID_SUBJECT=mailto:admin@klikhadir.my.id
VAPID_PUBLIC_KEY=...          # pasangan baru, bukan kunci dev
VAPID_PRIVATE_KEY=...
```

Email tidak dipakai: pendaftaran dan reset mandiri dinonaktifkan. Reset sandi dilakukan Admin di `/admin/members`.

`SESSION_SECURE_COOKIE=true` dan `SESSION_DOMAIN` sudah di-set di compose; HTTPS disediakan Coolify/Traefik.

## Langkah deploy Coolify

1. Buat resource **Docker Compose** dari repo ini, branch `main`.
2. Set `docker-compose.yml` sebagai compose path; isi environment di atas.
3. Domain → container `web`, port `8080`, aktifkan HTTPS/Let's Encrypt.
4. Batasi akses langsung: hanya proxy Coolify yang boleh menjangkau `web`.
5. Deploy. Migrasi berjalan otomatis di `web` (idempoten).
6. Seed sekali: `docker compose exec web php artisan db:seed --force`. Kata sandi admin dicetak di log; `db:seed` ulang **tidak** mereset kata sandi akun yang sudah ada.
7. Cek `https://forista.klikhadir.my.id/up` → 200.

## Resource

Compose tidak memasang limit; set di panel Coolify:

| Service     | RAM | CPU   |
| ----------- | --- | ----- |
| `web`       | 512M | 0.5  |
| `worker`    | 256M | 0.25 |
| `scheduler` | 128M | 0.1  |
| `db`        | 512M | 0.5  |

`php-fpm` memakai `pm=dynamic`, `max_children=8`. Naikkan hanya bila RAM host memang cukup.

## Backup

`pg_dump` harian ke penyimpanan off-site (S3/Backblaze), retensi 14 hari, lalu uji restore sekali sebelum rilis dianggap selesai. Snapshot volume saja tidak cukup karena tidak portabel dan bisa tidak konsisten saat DB hidup.

## Jam server

Aktifkan `chrony` atau `systemd-timesyncd` di VPS. Window presensi hanya 15 menit; drift kecil sudah menggeser pembukaan, penutupan, dan pengingat.

## Monitoring

Pantau `/up` dari Uptime Kuma eksternal dan kirim alert ke Telegram/WA. Health check Docker hanya menandai container, tidak memberi tahu siapa pun.

## Catatan operasional

- Volume `pgdata` wajib persisten.
- Jangan scale `web` > 1 selama migrasi masih di entrypoint. Untuk multi-replika, pindahkan migrasi ke release job.
- `opcache.validate_timestamps=0`; setiap deploy membangun image baru, jadi aman.
- `storage:link` dijalankan sekali per start container dan idempoten. Ekspor XLSX/PDF di-stream, tidak menumpuk.
- VAPID produksi berbeda dari key dev; pengguna harus menekan "Aktifkan" sekali lagi.
- Cache `config`/`route`/`view` dibangun saat runtime karena env platform baru tersedia di titik itu.
