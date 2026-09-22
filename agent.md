# AI Agent Instructions for Corporate Secretariat Repository System

## 1. Project Context
You are assisting in the development of a Corporate Secretariat Repository System for a large enterprise. This system handles highly sensitive documents, digital signatures (PSrE), and strict organizational workflows.
Security, auditability, and absolute adherence to business logic are the highest priorities.

## 2. Tech Stack & Infrastructure
- **Backend:** Laravel (PHP)
- **Frontend Tooling:** Vite + Tailwind CSS / Standard UI component library
- **Database:** MySQL
- **Background Processing:** Redis + Laravel Queue + Supervisor
- **Storage:** Amazon S3 (Object Storage)
- **Deployment:** VPS (Linux) - Do NOT write configurations for shared hosting environments.

## 3. Architectural Rules (Backend)
- **Thin Controllers:** Controllers must NOT contain business logic or state transition logic. Use Service classes, Action classes, or the State Pattern to handle complex workflows.
- **Strict Validation:** Use Laravel Form Requests for every inbound HTTP request.
- **State Machine Enforcement:** Outgoing and Incoming mails have strict states. Ensure validations reject any unauthorized or out-of-sequence state transitions (e.g., `DRAFT` cannot jump to `SIGNED`).
- **Asynchronous Processing:** Any task taking > 3 seconds MUST be dispatched to a Laravel Queue using Redis. This includes:
  - OCR Processing (e.g., Tesseract).
  - External API calls to Digital Signature Providers (PSrE).
  - Email notifications for dispositions.
- **Immutable Audit Trail:** Every action (Create, Read, Update, Delete/Archive, Download, Sign) must be logged with User ID, IP Address, Action Type, and Timestamp. Audit logs must never be mutable.
- **Storage Isolation:** Never store physical documents on local disk (`storage/app/public`). All document uploads must go directly to Amazon S3. Database should only store the S3 path/URL.

## 4. STANDAR DESAIN UI/UX & DROPDOWN COMBOBOX (MANDATORY)
Semua form input pemilihan di seluruh aplikasi WAJIB menggunakan standar berikut:
1. **Search-as-you-type Liquid Glass Combobox:**
   - Semua input dropdown relasi/data (misal: Pelanggan, Produk, Branch) WAJIB menggunakan pola *Searchable Combobox* interaktif dengan estetika **Liquid Glass Solid** (`apple-glass-panel bg-white/95 backdrop-blur-3xl border border-white/80 shadow-2xl rounded-2xl`).
   - **Tingkat Transparansi Rendah (Solid & Jelas Terbaca):** Box dropdown wajib memiliki opasitas tinggi (minimal `bg-white/95` atau `bg-white/[0.98]`) agar teks atau elemen tabel di baliknya tidak tembus pandang dan tidak mengganggu keterbacaan.
   - **Efek Buram Komponen yang Tertimpa (*Backdrop Blur*):** Area, tabel, atau baris kontainer di bawah menu dropdown yang tertimpa wajib diburamkan secara mendalam (`backdrop-blur-2xl` atau `backdrop-blur-3xl`).
   - **Opsi Registrasi On-the-Fly di Urutan Paling Atas:** Opsi otomatis **`+ Tambah Baru`** WAJIB selalu diposisikan di **URUTAN PERTAMA / PALING ATAS** dari menu dropdown sejak awal (baik saat kolom input masih kosong maupun saat pengguna sedang mengetik), sehingga admin dapat membuat entri baru *on-the-fly* secara instan tanpa meninggalkan halaman (detail lengkap dapat diselesaikan di lain waktu).
2. **Manajemen Stacking Context & Overflow:**
   - Kontainer tabel dan kartu form WAJIB menggunakan `overflow-visible` dan *z-index* bertingkat (`z-50`) agar menu dropdown mengapung (*floating*) di atas baris dan kontainer bawah tanpa terpotong (*no clipping*).
3. **Kepatuhan Tema Colorless & Icon-less:**
   - Seluruh elemen tetap mematuhi tema monokrom korporat (`slate-900`, `white`, `badge-dark`, subtle borders) dan bebas dari ikon dekoratif pada teks konten.

## 5. Security & RBAC
- Implement strict Role-Based Access Control (RBAC). 
- Verify user authorization not just at the UI level, but on every single API endpoint/controller method using Laravel Gates or Policies.
- Ensure document retrieval from S3 generates temporary, signed URLs rather than permanent public links.

## 6. Coding Standards
- Write strict, strongly typed PHP code (declare strict types).
- Return standard HTTP status codes (e.g., 403 for unauthorized state changes, 422 for validation errors, 200/201 for success).
- Maintain an objective, critical, and direct tone in code comments. Avoid unnecessary filler words.