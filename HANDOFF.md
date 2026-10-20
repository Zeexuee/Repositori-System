# Handoff — Perbaikan RBAC (Poin 1)

**Tanggal:** 21 Oktober 2026
**Branch:** `open-beta-oct`
**Status:** Poin 1 selesai, perubahan **belum di-commit**
**Test:** `php artisan test` → **46 passed, 199 assertions**

---

## 1. Ringkasan Proyek (konteks)

**Corporate Secretariat Repository System** — aplikasi repositori surat & arsip sekretariat korporat.

- Backend: Laravel 13 / PHP 8.3 (`declare(strict_types=1)`)
- Frontend: Blade + Alpine.js + Tailwind 4 (Vite), tema monokrom "Liquid Glass"
- DB: MySQL `repositori_rektorat` (test pakai SQLite in-memory)
- Auth: `AuthController` + quick-login demo; RBAC: `spatie/laravel-permission`
- Dokumen disimpan di disk `local` privat, diunduh via `DocumentDownloadController`
- Aturan proyek: `agent.md` & `workflow.md` (bukan `README.md` yang masih bawaan Laravel)

Entitas utama:
- `IncomingMail` (surat masuk, batch, disposisi, return, revisi, attachments)
- `OutgoingMail` (surat keluar, tanda tangan, return, revisi, attachments)
- `MailDisposition`, `AuditLog`, `BroadcastEmail`, `User`

State machine:
- `app/Support/IncomingMailStatus.php` — `DRAFT → RECEIVE/REVISI/DISPOSISI`, `RECEIVE → REVISI/RETURN/DISPOSISI`, `REVISI → RECEIVE/DISPOSISI`, `WAITING_RETURN → RETURN`, `DISPOSISI` final.
- `app/Support/OutgoingMailStatus.php` — `WAITING_RECEIVE → RECEIVE/REVISI`, `REVISI → WAITING_RECEIVE`, `RECEIVE` final.
- Transisi lewat `IncomingMailStatusTransitionService` / `OutgoingMailStatusTransitionService` (transaction + lockForUpdate).

---

## 2. Keputusan yang Disepakati

1. **Role kanonik hanya 2:** `staff` dan `master`.
   - `master` = Direksi / wewenang penuh
   - `staff` = staf sekretariat
   - Role lama `Staf` / `Direksi` dinormalisasi lewat migrasi.
2. **Matriks izin:**
   - staff: view + create + update dokumen non-final, termasuk alur SOP disposisi/pengembalian.
   - master: semua, plus delete, finalisasi manual `DISPOSISI`, tanda tangan digital, Audit/User/Broadcast.
3. **Alur SOP tetap boleh staff.** Yang master-only hanya *shortcut manual* finalisasi (`bulk update`, create/edit langsung ke `DISPOSISI`).
4. **Dokumen final dikunci total** (tidak bisa edit/ubah lampiran) — termasuk untuk master.

---

## 3. File yang Dibuat

| File | Isi |
|---|---|
| `app/Support/Roles.php` | Konstanta `STAFF`/`MASTER`, `all()`, `labels()`, `label()` |
| `app/Policies/UserPolicy.php` | Master-only; delete tidak boleh akun sendiri |
| `app/Policies/AuditLogPolicy.php` | Master-only (`viewAny`, `view`, `export`) |
| `app/Policies/BroadcastEmailPolicy.php` | Master-only (`viewAny`, `view`, `create`) |
| `database/migrations/2026_10_20_000001_normalize_legacy_role_names.php` | Rename role `Staf → staff`, `Direksi → master`, pindahkan assignment, clear cache Spatie |
| `tests/Feature/RoleAccessControlTest.php` | 13 test RBAC baru |

## 4. File yang Diubah

**Policy**
- `app/Policies/IncomingMailPolicy.php` — role check, final lock, ability `finalize` & `finalizeAny`, delete master-only.
- `app/Policies/OutgoingMailPolicy.php` — role check, final lock, delete & sign master-only.
- `app/Policies/MailDispositionPolicy.php` — create/view staff+master, delete master.

**Controller**
- `app/Http/Controllers/UserController.php` — `Gate::authorize`, validasi role `Rule::in(Roles::all())`, label role.
- `app/Http/Controllers/AuditLogController.php` — inline `hasRole` → `Gate::authorize`.
- `app/Http/Controllers/BroadcastEmailController.php` — inline `hasRole` → `Gate::authorize`; hitungan role pakai konstanta.
- `app/Http/Controllers/IncomingMailController.php` — guard finalisasi di `bulkUpdateStatus`, `store`, `update`; helper `guardManualFinalization()`.
- `app/Http/Controllers/IncomingMailDispositionController.php` — `show()` pakai `view` (bukan `update`).
- `app/Http/Controllers/IncomingMailReturnController.php` — `show()` pakai `view`.
- `app/Http/Controllers/OutgoingMailReturnController.php` — `show()` pakai `view`.
- `app/Http/Controllers/DocumentDownloadController.php` — `hasAnyRole(Roles::all())`.

**View / Seeder**
- `resources/views/layouts/app.blade.php` — `hasRole(Roles::MASTER)`.
- `resources/views/users/create.blade.php`, `edit.blade.php`, `index.blade.php` — value `staff`/`master`, label `Staf`/`Direksi`.
- `database/seeders/RoleSeeder.php` — pakai `Roles::all()`.

**Test**
- `tests/Feature/IncomingMailTest.php`
- `tests/Feature/IncomingMailStatusSopTest.php` (tambah helper `master()`; test finalisasi DISPOSISI pakai master)
- `tests/Feature/IncomingMailDispositionWorkflowTest.php`
- `tests/Feature/OutgoingMailTest.php`
- Semua role `'Staf'` diganti `Roles::STAFF`.

---

## 5. Perubahan Perilaku Penting

1. Dokumen `DISPOSISI` (masuk) dan `RECEIVE` (keluar) terkunci: `update` = false → edit, tambah/hapus lampiran, ubah status ditolak.
2. Staff tidak bisa set `DISPOSISI` manual via bulk/create/edit; harus lewat alur disposisi (tanda tangan) atau master.
3. Staff tidak melihat tombol Hapus (view memakai `@can('delete')`).
4. Modul Audit/User/Broadcast mengembalikan **403** untuk staff.
5. User tanpa role `staff`/`master` ditolak di halaman dokumen.
6. Endpoint baca penerima disposisi/pengembalian tetap bisa diakses staff (pakai `view`), termasuk dokumen final.

---

## 6. Verifikasi

```bash
php artisan test --compact
# 46 passed, 199 assertions
```

Catatan environment: **MySQL lokal sedang mati** saat sesi ini (`127.0.0.1:3306` refused), jadi migrasi belum dijalankan ke DB lokal. Jalankan saat DB aktif:

```bash
php artisan migrate
```

Pint hanya dijalankan pada file baru (project punya banyak pelanggaran style lama yang sengaja tidak disentuh agar diff tidak melebar).

---

## 7. Sisa Poin Audit (Belum Dikerjakan)

1. ~~Policy hampir kosong / RBAC tidak ditegakkan~~ ✅ **SELESAI**
2. **Alur tanda tangan surat keluar praktis tidak tercapai**
   - `OutgoingMailPolicy::sign()` butuh `status === 'APPROVED'`, padahal status nyata `WAITING_RECEIVE/REVISI/RECEIVE` (`APPROVED` hanya alias `RECEIVE`).
   - Perlu diputuskan: status apa yang valid untuk sign, dan siapa yang mengubah ke status itu.
3. **`ProcessDigitalSignatureJob` menulis status di luar state machine** (`SIGNED`, `SIGN_FAILED`).
4. **Sinkronisasi surat keluar berbasis `LIKE` subject** di `IncomingMailStatusSyncService` — rapuh, bisa cocok ke surat salah.
5. **Quick-login tanpa password** (`POST /quick-login/{email}`) — matikan untuk produksi.
6. **`DocumentDownloadController`** validasi berbasis string path, bukan kepemilikan; fallback mengizinkan siapa pun ber-role mengunduh path yang ada.
7. **"Thin controller" belum tercapai** — `IncomingMailController` ~700 baris, validasi inline di beberapa method.
8. **Redis belum dipakai** (queue/cache/session = `database`); `OcrProcessingService` & `DocumentSignatureService` masih placeholder (`return ''` / `return true`).
9. **Artefak debug**: `test_repo_ajax.php`, `app/Console/Commands/TestRevisi.php`, `welcome.blade.php` (2030 baris, tidak dipakai).
10. **Dokumen vs implementasi**: `agent.md` menyebut 4 role hierarki + S3, implementasi 2 role + local disk.

---

## 8. Cara Melanjutkan

1. Buka sesi baru di direktori `D:\laragon\www\Repository`.
2. Baca file ini + `git status` + `git diff`.
3. Nyalakan MySQL (Laragon), lalu `php artisan migrate`.
4. Jalankan `php artisan test --compact` untuk baseline.
5. Lanjut ke poin 2 (alur sign) — perlu keputusan status valid & aktor yang boleh mengubahnya.

---

## 9. Perintah Berguna

```bash
php artisan test --compact
php artisan migrate
php artisan migrate:status
php artisan route:list --except-vendor
vendor/bin/pint --test
git status --short
git diff --stat
```
