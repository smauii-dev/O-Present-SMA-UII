# Architecture & Naming Conventions

Proyek O-Present-SMA UII tidak dibangun menggunakan gaya konvensional *Fat Controller* (Model-View-Controller murni) bawaan CodeIgniter. Proyek ini dirombak secara ekstensif menggunakan prinsip-prinsip **Clean Architecture**, **SOLID Principles**, dan *Design Patterns* untuk memastikan sistem *scalable* dan mudah dipelihara.

Dokumen ini adalah *Single Source of Truth* untuk semua *developer* yang akan berkontribusi pada *codebase* ini.

---

## 1. Clean Architecture di CodeIgniter 4

Aliran data dalam aplikasi bersifat vertikal (dari atas ke bawah) secara ketat:
**Controller** → **Service** → **Repository** → **Model**

### A. Controllers (`app/Http/Controllers/`)
- **Tugas**: Menerima request HTTP, memvalidasi input via *Request Class*, memanggil fungsi di *Service*, dan mengembalikan respons (HTML via Twig, atau JSON).
- **Larangan**: Dilarang keras melakukan manipulasi *database* langsung (mengakses `Model` atau `Query Builder`) atau menempatkan logika bisnis (*Business Logic*) di sini.
- **Injeksi Dependency (DI)**: Controller mendefinisikan *Services* di *Constructor* untuk kemudahan *testing*.

### B. Services (`app/Services/` atau sejenisnya)
- **Tugas**: Tempat logika bisnis aplikasi berada (contoh: kalkulasi waktu keterlambatan, manajemen unggahan file S3, algoritma approval cuti).
- **Alur Kerja**: Mengambil data dari *Repository*, memprosesnya, lalu menugaskan *Repository* lain untuk menyimpan/memperbarui *database*. Di sinilah *Database Transaction* diletakkan jika proses melibatkan banyak tabel.

### C. Repositories (`app/Repositories/`)
- **Tugas**: Mengabstraksi lapisan akses data (*Data Access Layer*).
- **Sifat**: *Interface-based*. Controller/Service berinteraksi dengan kontrak *Interface*, bukan implementasi *database*-nya langsung.
- **Penggunaan Model**: Di *layer* inilah `Model` bawaan CI4 digunakan untuk mengakses *database*.

### D. Models (`app/Models/`)
- **Tugas**: Hanya menjadi representasi ORM (ActiveRecord) dari tabel database. Menggunakan `CodeIgniter\Model`.
- **Fokus**: Menangani kolom, tabel, relasi sederhana, tipe data, dan *soft deletes*.

### E. ViewModels (`app/Core/ViewModels/`)
- **Tugas**: Membentuk data mentah dari database/entitas (biasanya berupa *Object*) menjadi bentuk *Array/Object* presentasi (View-ready) yang sangat ramah untuk dikonsumsi oleh **Twig**.
- **Larangan**: Tidak boleh ada operasi *database* di dalam ViewModel.

---

## 2. Naming Conventions (Konvensi Penamaan)

Mengingat proyek ini menggabungkan konteks administrasi lokal (Indonesia) dengan standar koding internasional, kami menerapkan aturan penamaan **"English First in Code, Indonesian in Database"**.

### Aturan Database (MySQL/PostgreSQL)
- **Nama Tabel**: *Snake_case*, berbahasa Indonesia, bentuk tunggal/jamak tidak dibatasi. 
  *(Contoh: `pegawai`, `lokasi_presensi`, `ketidakhadiran`)*
- **Kolom**: *Snake_case*, berbahasa Indonesia.
  *(Contoh: `jam_masuk`, `tanggal_mulai`, `id_pegawai`)*

### Aturan PHP (Backend Code)
- **Nama Class, Namespace, Interface**: *PascalCase*, berbahasa **Inggris**.
  *(Contoh: `EmployeeController`, `AttendanceService`, `PositionRepositoryInterface`)*
- **Nama Variabel & Fungsi (Method)**: *camelCase*, berbahasa **Inggris**.
  *(Contoh: `getEmployeeById()`, `$activeEmployees`, `$isClockedIn`)*
- **Pengecualian Model**: Class `Model` boleh menggunakan nama entitas Indonesia untuk mencerminkan nama tabelnya dengan mudah. *(Contoh: `PegawaiModel`, `PresensiModel`)*

### Aturan Twig & Frontend (Views / HTML)
- **Struktur Folder Views**: Berbahasa Inggris, disesuaikan dengan rute atau fitur.
  *(Contoh: `app/Views/employees/index.twig`, `app/Views/components/modal.twig`)*
- **Variabel di Twig**: Berbahasa Inggris, karena dipasok oleh ViewModel.
  *(Contoh: `{{ employee.fullName }}`, `{{ attendance.status }}`)*

### Aturan File Statis (TS, JS, CSS)
- **Nama File Frontend**: *kebab-case*, berbahasa Inggris.
  *(Contoh: `attendance-form.ts`, `live-search.js`)*

---

## 3. Aturan Manajemen Routing & API

- **Web Routes**: Selalu gunakan `kebab-case` untuk URI. 
  *(Contoh: `/employee-management`, bukan `/employee_management`)*
- **API Routes**: Selalu letakkan di bawah *prefix* `/api`.
  *(Contoh: `/api/attendance/clock-in`)*
- **Filter (Middleware)**: Wajib dipasang di level grup *Route* (misalnya filter `role:admin`), BUKAN manual di dalam Controller.

---

## 4. Filosofi Integrasi Frontend

### HTMX
- Semua pembaruan DOM parsial (seperti pencarian *live*, pergantian halaman *table*) **WAJIB** menggunakan HTMX (`hx-get`, `hx-target`).
- Setiap respon backend yang diakses via HTMX harus merender **fragmen/partial HTML** komponen (contoh: Twig block), **BUKAN** merender ulang keseluruhan halaman berserta layout-nya.

### Alpine.js
- Digunakan murni untuk interaktivitas komponen klien yang tidak membutuhkan komunikasi ke server (Contoh: Buka/tutup modal, dropdown, interaksi akses kamera `navigator.mediaDevices`, validasi Zod interaktif klien).
- Tidak boleh ada jQuery atau Vanilla JS manipulasi DOM secara masif (`document.getElementById()`); biarkan Alpine (`x-data`, `x-bind`) bereaksi terhadap state reaktif.
