# Stayora — PHP Hotel Reservation

Struktur:
- `index.php` — halaman utama dan pencarian hotel
- `booking.php` — halaman reservasi
- `style.css` — seluruh styling website

## Menjalankan

Jika menggunakan XAMPP:
1. Copy folder `hotel_reservation_php` ke `htdocs`.
2. jalankan `composer require vlucas/phpdotenv` pada `terminal` 
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
