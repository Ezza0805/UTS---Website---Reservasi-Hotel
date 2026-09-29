# Stayora — PHP Hotel Reservation

# Stayora — PHP Hotel Reservation

Stayora adalah aplikasi reservasi hotel berbasis **PHP Native** dan **MySQL**.

Aplikasi ini memungkinkan pengguna untuk melihat hotel, memilih tipe kamar, menentukan tanggal menginap, jumlah tamu, memilih metode pembayaran, dan melakukan reservasi.

Project ini dibuat sebagai project pembelajaran/tugas menggunakan PHP Native tanpa framework.

---

## Fitur

### User

- Registrasi akun
- Login dan logout
- Melihat daftar hotel
- Mencari hotel
- Melihat informasi hotel
- Memilih tipe kamar
- Melihat kapasitas kamar
- Memilih jumlah tamu sesuai kapasitas kamar
- Menentukan tanggal check-in dan check-out
- Melihat jumlah malam
- Melihat total harga reservasi
- Memilih metode pembayaran
- Melakukan reservasi
- Melihat informasi reservasi

### Admin

- Login sebagai admin
- Mengelola data hotel
- Mengelola tipe kamar
- Mengelola kamar
- Mengelola reservasi
- Mengelola pembayaran

---

## Teknologi

Project ini menggunakan:

- PHP Native
- MySQL
- PDO
- HTML5
- CSS3
- JavaScript
- Composer
- PHP dotenv (`vlucas/phpdotenv`)

---

## Struktur Project

```text
hotel_reservation_php/
│
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── config/
│   └── database.php
│
├── uploads/
│   ├── .htaccess
│   ├── hotels/
│   └── room_types/
│
├── public/
│   └── css/
│       └── style.css
│
├── index.php
├── booking.php
├── payment.php
│
├── composer.json
├── composer.lock
├── .env
├── .gitignore
└── README.md

## Menjalankan

Jika menggunakan XAMPP:
1. Copy folder `hotel_reservation_php` ke `htdocs`.
2. jalankan 
```bash
composer require vlucas/phpdotenv
``` 
pada `terminal` 
2. Jalankan Apache dari XAMPP.
3. Buka:
   `http://localhost/hotel_reservation_php/`

Jika menggunakan PHP built-in server:
```bash
php -S localhost:8000
```

Lalu buka:
`http://localhost:8000/`

Saat ini data hotel masih berupa array PHP sehingga cocok sebagai dasar untuk tahap berikutnya. Database MySQL dapat ditambahkan setelah struktur halaman selesai.
