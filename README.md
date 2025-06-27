# Proyek Website Katalog Film

Sebuah aplikasi web sederhana yang dibuat dengan PHP dan MySQL untuk menampilkan katalog film, mirip dengan platform streaming populer.

## Deskripsi Singkat

Aplikasi ini memungkinkan pengguna untuk mendaftar, login, mencari film, melihat detail film, dan menyimpannya ke dalam daftar favorit atau daftar tontonan. Terdapat juga panel admin untuk mengelola data film, genre, dan pengguna.

## Fitur
- Sistem Login & Registrasi Pengguna
- Tampilan Katalog Film
- Halaman Detail Film Interaktif (Favorit, Status Tontonan)
- Pencarian Lanjutan (berdasarkan judul, genre, tahun)
- Admin Panel:
  - CRUD (Create, Read, Update, Delete) untuk Film
  - CRUD untuk Genre
  - Manajemen Pengguna (melihat, mengedit role, menghapus)
  - Halaman Laporan

## Cara Instalasi

1.  Pastikan Anda memiliki web server lokal seperti XAMPP atau WAMP.
2.  Letakkan seluruh folder proyek ini di dalam direktori `htdocs` (untuk XAMPP) atau `www` (untuk WAMP).
3.  Buat sebuah database baru di phpMyAdmin dengan nama `katalogfilm`.
4.  Impor file `.sql` yang berisi struktur tabel dan data awal ke dalam database `katalogfilm`.
5.  Buka file `connect.php` dan sesuaikan detail koneksi database (nama server, username, password) jika diperlukan.
6.  Buka aplikasi di browser Anda melalui `http://localhost/nama_folder_proyek`.

## Akun Demo (Opsional)

-   **Admin:**
    -   Username: `momo`
    -   Password: `(masukkan password Anda di sini)`
-   **User Biasa:**
    -   Username: `bagus`
    -   Password: `(masukkan password Anda di sini)`
