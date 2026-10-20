# Handoff — Perbaikan Audit (Poin 1–4)

**Tanggal:** 21 Oktober 2026
**Branch:** `open-beta-oct`
**Status:** Poin 1–3 di-commit; poin 4 selesai, perubahan **belum di-commit**
**Test:** `php artisan test` → **48 passed, 208 assertions**

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

## 6. Poin 2 & 3 — Fitur PSrE Orphan (Selesai)

**Keputusan:** fitur PSrE dihapus, bukan diperbaiki.

Alasan:
- `OutgoingMailPolicy::sign()` mensyaratkan `status === 'APPROVED'`, sedangkan `APPROVED` adalah alias `RECEIVE` (status final terkunci). Endpoint tidak pernah bisa dipakai.
- Tidak ada tombol/UI yang memanggil route `outgoing-mails.sign`.
- Alur tanda tangan yang benar-benar dipakai adalah penerimaan per penerima (`OutgoingMailReturnController`) yang sudah memakai state machine dan transisi ke `RECEIVE`.

Perubahan:
- Hapus `POST /outgoing-mails/{id}/sign`, `OutgoingMailController::sign()`, `OutgoingMailPolicy::sign()`.
- Hapus `app/Jobs/ProcessDigitalSignatureJob.php` dan `app/Services/DocumentSignatureService.php` (tidak ada pemakai lain).
- Hapus 2 test sign di `RoleAccessControlTest`.
- Tambah `tests/Feature/OutgoingMailReceiveWorkflowTest.php` (4 test): finalisasi `complete` → `RECEIVE`, `skip_remaining` → `RECEIVE`, validasi tanda tangan wajib, dan dokumen final menolak alur ulang (403).

Konsekuensi:
- Tidak ada lagi penulisan status `SIGNED` / `SIGN_FAILED` di luar state machine (poin 3 tertutup).
- Integrasi PSrE belum ada sama sekali. Bila nanti dibutuhkan, harus dibuat status/alur eksplisit di state machine, bukan menumpang status penerimaan.

---

## 7. Poin 4 — Sinkronisasi Surat Masuk → Surat Keluar (Selesai)

**Keputusan:** sinkronisasi otomatis dihapus total.

Temuan yang mendasari:
- `IncomingMailStatusSyncService` mencari surat keluar dengan `subject LIKE '%<subject>'` (wildcard tidak di-escape), lalu menimpa `status`/`subject` atau membuat surat keluar baru.
- Tidak ada aturan bisnisnya di `agent.md`, `workflow.md`, maupun UI.
- Status `REVISI`/`RETURN` adalah konsep SOP surat **masuk**; menuliskannya ke surat keluar mencampur dua register.
- Subject sama dalam satu batch → saling menimpa. Nomor surat `count()+1` rawan bentrok dengan kolom `mail_number` yang unique.
- Efek samping berada di dalam transaksi finalisasi disposisi/pengembalian; kegagalan sync bisa membatalkan tanda tangan.

Perubahan:
- Hapus `app/Services/IncomingMailStatusSyncService.php`.
- Hapus dependency + pemanggilan di `IncomingMailController`, `IncomingMailDispositionController`, `IncomingMailReturnController`, dan `IncomingMailStatusTransitionService`.
- Test regresi: transisi `REVISI` dan finalisasi `RETURN` tidak membuat/mengubah surat keluar (`OutgoingMail::count() === 0`).

**Belum ditangani (menunggu keputusan):** 2 record ghost di DB lokal hasil sync lama:
- `SK-20261021-0005` — `[REVISI] Makan siang bersama Keren`
- `SK-20261021-0006` — `[REVISI] Undangan makan Malam`

Catatan teknis: `OutgoingMailController::store()`/`update()` memakai `OutgoingMail::whereNotNull('mail_number')->count() + 1` untuk nomor otomatis. Query default mengecualikan soft-deleted, sehingga soft delete ghost **tidak** mengosongkan nomor dan berpotensi memicu unique violation saat nomor yang sama dibuat lagi.

---

## 8. Verifikasi

```bash
php artisan test --compact
# 48 passed, 208 assertions
```

Catatan environment: **MySQL lokal sedang mati** saat sesi ini (`127.0.0.1:3306` refused), jadi migrasi belum dijalankan ke DB lokal. Jalankan saat DB aktif:

```bash
php artisan migrate
```

Pint hanya dijalankan pada file baru (project punya banyak pelanggaran style lama yang sengaja tidak disentuh agar diff tidak melebar).

---

## 9. Sisa Poin Audit (Belum Dikerjakan)

1. ~~Policy hampir kosong / RBAC tidak ditegakkan~~ ✅ **SELESAI**
2. ~~Alur tanda tangan surat keluar praktis tidak tercapai~~ ✅ **SELESAI** — fitur PSrE orphan dihapus; alur resmi = penerimaan per penerima.
3. ~~`ProcessDigitalSignatureJob` menulis status di luar state machine~~ ✅ **SELESAI** — job dihapus.
4. ~~Sinkronisasi surat keluar berbasis `LIKE` subject~~ ✅ **SELESAI** — sinkronisasi otomatis dihapus.
5. **Quick-login tanpa password** (`POST /quick-login/{email}`) — matikan untuk produksi.
6. **`DocumentDownloadController`** validasi berbasis string path, bukan kepemilikan; fallback mengizinkan siapa pun ber-role mengunduh path yang ada.
7. **"Thin controller" belum tercapai** — `IncomingMailController` ~700 baris, validasi inline di beberapa method.
8. **Redis belum dipakai** (queue/cache/session = `database`); `OcrProcessingService` masih placeholder (`return ''`). PSrE belum diimplementasikan.
9. **Artefak debug**: `test_repo_ajax.php`, `app/Console/Commands/TestRevisi.php`, `welcome.blade.php` (2030 baris, tidak dipakai).
10. **Dokumen vs implementasi**: `agent.md` menyebut 4 role hierarki + S3, implementasi 2 role + local disk.

---

## 10. Cara Melanjutkan

1. Buka sesi baru di direktori `D:\laragon\www\Repository`.
2. Baca file ini + `git status` + `git diff`.
3. Nyalakan MySQL (Laragon), lalu `php artisan migrate`.
4. Jalankan `php artisan test --compact` untuk baseline.
5. Lanjut ke poin 5 (quick-login tanpa password) — perlu keputusan: hapus total atau batasi ke environment `local`/`demo`.

---

## 11. Perintah Berguna

```bash
php artisan test --compact
php artisan migrate
php artisan migrate:status
php artisan route:list --except-vendor
vendor/bin/pint --test
git status --short
git diff --stat
```
