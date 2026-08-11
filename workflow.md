# Technical Development Workflow
**System:** Corporate Secretariat Repository System
**Stack:** Laravel, Vite, MySQL, Redis, Amazon S3, VPS (Linux)

---

## Phase 1: Persiapan Infrastruktur & Lingkungan Dasar
*Fase ini memastikan pondasi keamanan dan penyimpanan beroperasi sebelum kode aplikasi ditulis.*

1. **Konfigurasi Amazon S3:**
   - Buat S3 Bucket khusus.
   - Konfigurasi IAM User. **Wajib:** Hanya berikan akses `s3:PutObject`, `s3:GetObject`, dan `s3:DeleteObject`. Dilarang menggunakan kredensial root.
2. **Inisialisasi Proyek Lokal:**
   - Instalasi Laravel.
   - Konfigurasi `.env` untuk MySQL, Redis, dan S3.
3. **Autentikasi & RBAC (Role-Based Access Control):**
   - Instalasi *package* RBAC (misal: Spatie Permission).
   - Buat *Seeder* hierarki pengguna: Super Admin, Direksi, Kepala Divisi, Staf Sekretariat.

## Phase 2: Perancangan Skema Database
*Jangan membangun UI sebelum skema data divalidasi.*

1. **Tabel Utama (Surat Masuk & Keluar):**
   - Buat migrasi tabel `incoming_mails` dan `outgoing_mails`.
   - **Kritis:** Terapkan kolom `status` dengan tipe `ENUM` untuk mengunci *state machine* (contoh: `DRAFT`, `IN_REVIEW`, `APPROVED`, dll).
2. **Tabel Relasional:**
   - Buat migrasi tabel `mail_dispositions` untuk melacak rute disposisi dan tenggat waktu.
3. **Tabel Jejak Audit (*Audit Trail*):**
   - Buat migrasi tabel `audit_logs` (`user_id`, `action`, `model_type`, `model_id`, `ip_address`, `created_at`). 
   - Tabel ini bersifat *append-only* (tidak ada fungsi hapus/ubah).

## Phase 3: Pengembangan Logika Bisnis (Backend)
*Logika inti harus diisolasi dari Controller.*

1. **Validasi Request:**
   - Buat `FormRequest` untuk semua *endpoint* input. Validasi ketat ekstensi (hanya `.pdf`), ukuran file, dan parameter wajib.
2. **Service Classes:**
   - Implementasi `DocumentSignatureService` untuk integrasi API PSrE.
   - Implementasi `OcrProcessingService` untuk ekstraksi teks.
3. **Asynchronous Jobs (Redis/Queue):**
   - Buat *Jobs* untuk proses yang memakan waktu > 3 detik (OCR, pengiriman notifikasi, permintaan PSrE).
4. **Observer untuk Audit:**
   - Terapkan *Model Observer* pada entitas dokumen untuk merekam otomatis setiap operasi CRUD ke dalam tabel `audit_logs`.

## Phase 4: Pengembangan Antarmuka (Frontend)
*Gunakan pendekatan Flat Corporate UI. Dilarang menggunakan efek visual yang merusak keterbacaan (seperti Glassmorphism).*

1. **Layouting Dasar:**
   - Bangun struktur navigasi dan *layout* kartu (Card) dengan kontras tinggi sesuai standar WCAG 2.1.
2. **Integrasi Data (API/Web Routes):**
   - Terapkan *Server-side Pagination* pada tabel untuk mencegah *browser freezing* saat memuat data dalam jumlah besar.
3. **UX Feedback:**
   - Terapkan *loading state* (spinner/toast) untuk aksi yang memicu proses *Queue* di latar belakang.

## Phase 5: Deployment & Konfigurasi Server (VPS)
*Penerapan aplikasi ke lingkungan produksi.*

1. **Provisioning VPS:**
   - Instalasi & konfigurasi OS Linux, Nginx, PHP-FPM, MySQL, dan Redis.
2. **Konfigurasi Supervisor:**
   - Buat skrip *Supervisor* untuk `php artisan queue:work` agar proses latar belakang (OCR/Notifikasi) berjalan 24/7 dan *auto-restart* saat gagal.
3. **Kompilasi & Migrasi:**
   - Eksekusi kompilasi *asset* via Vite (`npm run build`).
   - Eksekusi *database migration*.
4. **Hardening Keamanan Server:**
   - Pastikan *web root* Nginx menunjuk ke folder `/public` Laravel.
   - Blokir akses publik ke berkas `.env` dan direktori internal lainnya.