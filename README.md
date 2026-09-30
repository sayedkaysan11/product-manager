# Product Manager

Tugas akhir mini project Pemrograman Web pertemuan 3. Aplikasi CRUD produk pakai PHP + MySQL, koneksinya lewat PDO biar aman dari SQL injection.

## Fitur

- **Create** - tambah produk baru (nama, kategori, harga, stok)
- **Read** - daftar produk ditampilkan dalam bentuk card, responsif
- **Update** - edit produk berdasarkan ID
- **Delete** - hapus produk, cuma bisa lewat POST + token CSRF (biar gak kehapus gara-gara link doang)

Validasi yang dipasang:
- nama minimal 3 karakter dan harus unik
- harga harus lebih dari 0
- stok gak boleh minus

Semua query pakai prepared statement (`$pdo->prepare()`), dan semua output di-escape pakai `htmlspecialchars()` biar aman dari XSS.

## Struktur file

```
product-manager/
├── schema.sql            -> struktur tabel database
├── index.php              -> halaman utama (form tambah + daftar produk)
├── edit.php                -> form edit produk
├── delete.php              -> proses hapus produk
└── includes/
    ├── config.php          -> koneksi PDO ke MySQL
    ├── functions.php       -> validasi, CSRF, format rupiah
    └── bootstrap.php       -> session + require file lain
```

## Cara jalanin (pakai XAMPP)

1. Nyalain Apache sama MySQL dari XAMPP Control Panel.
2. Buka `http://localhost/phpmyadmin`, klik tab SQL, copy-paste isi `schema.sql`, terus Go. Ini bakal bikin database `product_manager` beserta tabelnya.
3. Copy folder `product-manager` ke `htdocs`.
4. Buka `http://localhost/product-manager` di browser.

Kalau MySQL di laptop kamu pakai password, buka `includes/config.php`, ganti bagian `DB_PASS`.

## Catatan soal refresh

Setiap kali form disubmit (tambah/edit/hapus), aplikasi selalu redirect ke halaman lain setelah selesai proses (pola Post-Redirect-Get). Jadi kalau halaman di-refresh setelah submit, gak bakal ngirim data yang sama dua kali.
