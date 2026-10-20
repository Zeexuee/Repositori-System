# Handoff — Perbaikan Audit (Poin 1–4)

**Tanggal:** 21 Oktober 2026
**Branch:** `open-beta-oct`
**Status:** Poin 1–5 di-commit; poin 6 selesai, **belum di-commit**
**Test:** `php artisan test` → **65 passed, 247 assertions**

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
- `app/Support/IncomingMailStatus.php` — `DRAFT → RECEIVE/REVISI/DISPOSISI`, `RECEIVE → REVISI/RETURN/DISPOSISI`, `REVISI → RECEIVE`, `WAITING_RETURN → RETURN`, `DISPOSISI` final.
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

**Penanganan data lama (selesai):** 2 record ghost hasil sync lama sudah di-hard-delete:
- `SK-20261021-0005` — `[REVISI] Makan siang bersama Keren`
- `SK-20261021-0006` — `[REVISI] Undangan makan Malam`

Backup JSON (lokal, tidak di-commit karena `storage/app/private` di-gitignore):
`storage/app/private/backups/ghost-outgoing-mails-2026-10-21.json`

**Generator nomor surat (selesai):** `app/Support/OutgoingMailNumber.php`
- Counter disimpan di tabel `outgoing_mail_sequences` (`sequence_date` PK, `last_number`), migrasi `2026_10_21_000001_create_outgoing_mail_sequences_table.php`.
- `next()` berjalan dalam transaksi: `insertOrIgnore` lalu `lockForUpdate`, sehingga dua request bersamaan tidak mendapat nomor sama.
- Nomor tetap mempertimbangkan nomor manual dan soft-deleted: `max(counter, nomor tertinggi yang ada) + 1`.
- Gap nomor bisa terjadi bila pemanggilan gagal setelah counter naik; ini normal untuk sequence dan tidak berbahaya.
- Dipakai di `OutgoingMailController::store()` dan `update()`.

**Tooling review ghost record (selesai):** `php artisan outgoing-mails:detect-sync-artifacts`
- Default **read-only**: hanya menampilkan kandidat, tidak menghapus.
- Kandidat = surat keluar berprefix `[REVISI]`/`[RETURN]` yang subject bersihnya cocok dengan surat masuk berstatus `REVISI`/`RETURN` **dan** `sender` surat masuk = `recipient` surat keluar.
- `--delete` meminta konfirmasi; `--force` melewati konfirmasi; backup JSON selalu dibuat ke `storage/app/private/backups/` (bisa diatur dengan `--backup=`).
- Test: `tests/Feature/DetectOutgoingMailSyncArtifactsTest.php` (3 test).

---

## 8. Poin 5 — Quick-login Tanpa Password (Selesai)

**Keputusan:** dihapus total.

Alasan:
- `POST /quick-login/{email}` bisa login sebagai pengguna mana pun hanya dengan mengetahui email, tanpa password.
- Tidak ada UI/tombol yang memanggil route ini (`auth/login.blade.php` hanya berisi form email + password); route hanya tersisa dari demo lama.
- Tidak ada test yang bergantung padanya.

Perubahan:
- Hapus route `quick-login` dari `routes/web.php`.
- Hapus `AuthController::quickLogin()` dan import `User` yang tidak lagi terpakai.
- Tambah `tests/Feature/AuthenticationTest.php` (3 test): endpoint quick-login mengembalikan 404 dan tetap guest, password salah ditolak, login dengan kredensial valid berhasil.

---

## 9. Poin 6 — Unduhan Dokumen Berbasis Kepemilikan (Selesai)

**Keputusan:** fallback berbasis role dihapus; path hanya dilayani jika ter-resolve ke dokumen yang boleh dilihat.

Temuan:
- `download()` menerima `path` mentah dari query string.
- Bila path tidak ditemukan di tabel mana pun, kode hanya memeriksa role, sehingga pengguna ber-role bisa mengunduh file apa pun yang ada di disk `local`.

Perubahan:
- `DocumentDownloadController` dipecah menjadi `findIncomingMail()` dan `findOutgoingMail()`.
- Path harus cocok dengan salah satu pemilik: file/foto/tanda terima surat masuk, attachment, tanda tangan return/disposisi, atau sisi surat keluar (file, attachment, tanda tangan return, file history).
- Tanpa pemilik → **404**. Pemilik ditemukan tetapi policy menolak → **403**. Tidak ada lagi fallback role.
- Signature route tidak berubah, sehingga seluruh view/controller pemanggil tetap kompatibel.
- Test baru `tests/Feature/DocumentDownloadTest.php` (6 test): unduh & inline, path tanpa pemilik 404, signature lewat return recipient, user tanpa role 403, guest redirect.

Catatan: bila storage pindah ke S3, ganti mekanisme ini dengan temporary signed URL sesuai `agent.md`.

---

## 10. Verifikasi

```bash
php artisan test --compact
# 65 passed, 247 assertions
```

Catatan environment: MySQL Laragon aktif di sesi ini. Migrasi berikut sudah dijalankan ke DB lokal:

```bash
php artisan migrate
```

Termasuk `2026_10_20_000001_normalize_legacy_role_names` dan `2026_10_21_000001_create_outgoing_mail_sequences_table`.

Pint hanya dijalankan pada file baru (project punya banyak pelanggaran style lama yang sengaja tidak disentuh agar diff tidak melebar).

---

## 11. Sisa Poin Audit (Belum Dikerjakan)

1. ~~Policy hampir kosong / RBAC tidak ditegakkan~~ ✅ **SELESAI**
2. ~~Alur tanda tangan surat keluar praktis tidak tercapai~~ ✅ **SELESAI** — fitur PSrE orphan dihapus; alur resmi = penerimaan per penerima.
3. ~~`ProcessDigitalSignatureJob` menulis status di luar state machine~~ ✅ **SELESAI** — job dihapus.
4. ~~Sinkronisasi surat keluar berbasis `LIKE` subject~~ ✅ **SELESAI** — sinkronisasi otomatis dihapus.
5. ~~Quick-login tanpa password~~ ✅ **SELESAI** — route dan method dihapus.
6. ~~`DocumentDownloadController` validasi berbasis string path~~ ✅ **SELESAI** — path wajib ter-resolve ke dokumen yang diotorisasi; fallback role dihapus.
7. **"Thin controller" belum tercapai** — `IncomingMailController` ~700 baris, validasi inline di beberapa method.
8. **Redis belum dipakai** (queue/cache/session = `database`); `OcrProcessingService` masih placeholder (`return ''`). PSrE belum diimplementasikan.
9. **Artefak debug**: `test_repo_ajax.php`, `app/Console/Commands/TestRevisi.php`, `welcome.blade.php` (2030 baris, tidak dipakai).
10. **Dokumen vs implementasi**: `agent.md` menyebut 4 role hierarki + S3, implementasi 2 role + local disk.
11. ~~Generator nomor surat belum concurrency-safe~~ ✅ **SELESAI** — tabel `outgoing_mail_sequences` + `lockForUpdate`.
12. ~~Ghost record di lingkungan lain belum ada alat review~~ ✅ **SELESAI** — `php artisan outgoing-mails:detect-sync-artifacts` (read-only secara default).

---

## 12. Cara Melanjutkan

1. Buka sesi baru di direktori `D:\laragon\www\Repository`.
2. Baca file ini + `git status` + `git diff`.
3. Nyalakan MySQL (Laragon), lalu `php artisan migrate`.
4. Jalankan `php artisan test --compact` untuk baseline.
5. Lanjut ke poin 7 ("thin controller") — `IncomingMailController` ~700 baris. Pecah bertahap per alur (store batch, update, attachments) ke Form Request/Service tanpa mengubah perilaku.

---

## 13. Perintah Berguna

```bash
php artisan test --compact
php artisan migrate
php artisan migrate:status
php artisan route:list --except-vendor
php artisan outgoing-mails:detect-sync-artifacts
vendor/bin/pint --test
git status --short
git diff --stat
```
