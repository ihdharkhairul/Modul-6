#  GaiaCity — Sistem Pelaporan Lingkungan

> Aplikasi web berbasis **PHP + MySQL** untuk pelaporan masalah lingkungan kota.  
> Menerapkan **OOP (Object-Oriented Programming)**, CRUD, upload file, dan autentikasi berbasis peran.

---

##  Fitur Utama

| Fitur | Keterangan |
|---|---|
|  Login / Register | Autentikasi aman dengan `password_hash` & `password_verify` |
|  Dashboard | Hanya bisa diakses setelah login (redirect otomatis) |
|  CRUD Laporan | Create, Read, Update, Delete laporan lingkungan |
|  Upload Foto | Satu tombol **Simpan** = CRUD + upload file sekaligus |
|  Role-Based Access | Citizen hanya lihat milik sendiri; Officer & Admin lihat semua |
|  Filter & Cari | Filter kategori + pencarian judul/lokasi |
|  Statistik | Ringkasan Total, Pending, Diproses, Selesai |

---

##  Arsitektur OOP

```
classes/
├── Database.php          → Singleton Pattern        : satu instance koneksi MySQL
├── User.php              → Encapsulation            : login, register, logout, sesi
├── LaporanRepository.php → Repository Pattern       : seluruh operasi CRUD laporan
└── FileUploader.php      → Single Responsibility   : upload & validasi file foto
```

| Class | Pola / Prinsip | Tanggung Jawab |
|---|---|---|
| `Database` | Singleton Pattern | Satu instance koneksi MySQL per request |
| `User` | Encapsulation | Login, registrasi, logout, manajemen sesi |
| `LaporanRepository` | Repository Pattern | Seluruh operasi CRUD tabel `laporan_lingkungan` |
| `FileUploader` | Single Responsibility | Upload, validasi, dan hapus file foto |

---

##  Struktur Proyek

```
GaiaCity/
├── classes/
│   ├── Database.php
│   ├── User.php
│   ├── LaporanRepository.php
│   └── FileUploader.php
├── config/
│   ├── database.php          ← konstanta DB + autoload semua class
│   └── auth.php              ← wrapper fungsi autentikasi
├── pages/
│   └── dashboard.php         ← halaman utama (protected)
├── uploads/                  ← folder penyimpanan foto laporan
├── login.php
├── register.php
├── logout.php
├── database.sql
└── README.md
```

---

##  Instalasi

### Prasyarat
- XAMPP / Laragon (PHP >= 8.0, MySQL)
- Browser modern

### Langkah

**1. Clone repository**
```bash
git clone https://github.com/username/gaiacity.git
```
> Atau ekstrak ZIP ke folder `htdocs/` (XAMPP) / `www/` (Laragon)

**2. Import database**
```bash
mysql -u root -p < database.sql
```
> Atau buka **phpMyAdmin** → Import → pilih `database.sql`

**3. Sesuaikan konfigurasi**

Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // sesuaikan password MySQL
define('DB_NAME', 'gaiacity_db');
```

**4. Jalankan aplikasi**

Buka browser:
```
http://localhost/GaiaCity/login.php
```

---

## 👤 Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin | admin@gaiacity.id | `Password123` |
| Officer | officer@gaiacity.id | `Password123` |
| Citizen | citizen@gaiacity.id | `Password123` |

---

## 🛠️ Teknologi

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat&logo=tailwind-css&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-D22128?style=flat&logo=apache&logoColor=white)

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan **Tugas Besar** mata kuliah Pemrograman Web.  
© 2024 GaiaCity Team
