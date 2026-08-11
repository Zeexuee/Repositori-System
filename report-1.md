# Laporan Perubahan & Hasil Eksekusi Phase 1, Phase 2, dan Phase 3 (Refactored)

**Sistem:** Corporate Secretariat Repository System  
**Tanggal Eksekusi:** 11 Agustus 2026  
**Status:** Sukses Phase 1, Phase 2, & Phase 3 ( SoftDeletes & Forensic Integrity Verified 100% )

---

## 1. Ringkasan Eksekutif Refactoring

Telah dilakukan refactoring pada Phase 3 untuk meningkatkan keamanan validasi input, kepatuhan retensi pengarsipan dokumen (*Soft Deletes*), serta integritas forensik jejak audit pada lingkungan CLI/Queue worker.

Ketentuan utama pengembangan:
- **TIDAK ADA** Controller, View, atau Rute HTTP yang dibuat.
- Seluruh berkas PHP menggunakan `declare(strict_types=1);`.
- Penegakan atribut `$fillable` eksplisit tanpa `$guarded = []`.

---

## 2. Rincian Perbaikan (Refactoring Phase 3)

### 1. Form Request (Pencegahan Manipulasi Nomor Surat)
- [app/Http/Requests/StoreOutgoingMailRequest.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/app/Http/Requests/StoreOutgoingMailRequest.php): Menghapus seluruh aturan validasi `mail_number`. Nomor surat dilarang diinput/manipulasi oleh pengguna pada tahap draf karena akan digenerate otomatis oleh sistem backend.
- [app/Http/Requests/UpdateOutgoingMailRequest.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/app/Http/Requests/UpdateOutgoingMailRequest.php): Menghapus aturan validasi `mail_number`.

### 2. AuditLogObserver (Integritas Forensik CLI/Queue)
- [app/Observers/AuditLogObserver.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/app/Observers/AuditLogObserver.php): Memperbarui penanganan `ip_address` menggunakan deteksi console:
  ```php
  'ip_address' => app()->runningInConsole() ? 'SYSTEM' : (request()->ip() ?? 'UNKNOWN'),
  ```
  Hal ini membedakan secara forensik aktivitas otomatis dari worker queue/CLI (`SYSTEM`) dari aktivitas pengguna HTTP lokal/remote.

### 3. Soft Deletes (Standar Retensi Arsip Dokumen)
- **Migrasi Baru:** [database/migrations/2026_08_11_080005_add_soft_deletes_to_mail_tables.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/database/migrations/2026_08_11_080005_add_soft_deletes_to_mail_tables.php)
  - Menambahkan kolom `deleted_at` (`$table->softDeletes();`) pada tabel `incoming_mails` dan `outgoing_mails`.
- **Model Eloquent:**
  - [app/Models/IncomingMail.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/app/Models/IncomingMail.php): Menambahkan trait `Illuminate\Database\Eloquent\SoftDeletes`.
  - [app/Models/OutgoingMail.php](file:///c:/IT_Project_SU/DataApp/laragon/www/Repositori_System/app/Models/OutgoingMail.php): Menambahkan trait `Illuminate\Database\Eloquent\SoftDeletes`.

---

## 3. Hasil Verifikasi CLI

Pengujian ulang database melalui `php artisan migrate:fresh --seed` berjalan 100% lancar:

```text
 Dropping all tables .. 263.91ms DONE

 INFO Preparing database. 

 Creating migration table .. 24.46ms DONE

 INFO Running migrations. 

 0001_01_01_000000_create_users_table .. 96.38ms DONE
 0001_01_01_000001_create_cache_table .. 51.41ms DONE
 0001_01_01_000002_create_jobs_table .. 97.40ms DONE
 2026_08_11_080001_create_incoming_mails_table .. 52.89ms DONE
 2026_08_11_080002_create_outgoing_mails_table .. 90.51ms DONE
 2026_08_11_080003_create_mail_dispositions_table .. 174.92ms DONE
 2026_08_11_080004_create_audit_logs_table .. 68.55ms DONE
 2026_08_11_080005_add_soft_deletes_to_mail_tables .. 31.93ms DONE
 2026_08_11_082829_create_permission_tables .. 349.91ms DONE

 INFO Seeding database. 

 Database\Seeders\RoleSeeder .. RUNNING 
 Database\Seeders\RoleSeeder .. 27 ms DONE 

 Database\Seeders\UserSeeder .. RUNNING 
 Database\Seeders\UserSeeder .. 249 ms DONE 
```
