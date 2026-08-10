# Sistem Informasi Monitoring Tahfidz

Aplikasi PHP native untuk Rumah Tahfidz As-Sakinah, dibangun dari mockup yang tersedia. Aplikasi otomatis memakai SQLite jika tersedia, atau MySQL bawaan Laragon sebagai fallback.

## Menjalankan aplikasi

1. Nyalakan Apache dan MySQL di Laragon. Jika `pdo_sqlite` aktif, MySQL tidak wajib.
2. Jalankan `php -S localhost:8000` dari direktori proyek, atau buka melalui Laragon.
3. Akses `http://localhost:8000` atau `http://localhost/rumahtahfidz`.

Database dan data demo dibuat otomatis pada kunjungan pertama. Pengaturan utamanya berada di `config/database.php`.

```php
'driver' => 'mysql',
'mysql' => array(
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'rumahtahfidz',
    'username' => 'root',
    'password' => '',
),
```

Gunakan driver `auto` agar aplikasi memilih SQLite jika tersedia dan MySQL sebagai fallback. Gunakan `mysql` untuk memaksa MySQL atau `sqlite` untuk memaksa SQLite. Environment variable `TAHFIDZ_DB_DRIVER`, `TAHFIDZ_DB_HOST`, `TAHFIDZ_DB_PORT`, `TAHFIDZ_DB_NAME`, `TAHFIDZ_DB_USER`, `TAHFIDZ_DB_PASS`, dan `TAHFIDZ_SQLITE_PATH` tetap dapat digunakan untuk menimpa konfigurasi file.

## Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Administrator | admin@tahfidz.test | password |
| Ustadzah | ustadzah@tahfidz.test | Tahfidz123! |
| Wali Santri | wali@tahfidz.test | Tahfidz123! |

## Fitur

- Login dan pembatasan menu berbasis peran.
- Pembuatan akun login Ustadzah dan Wali langsung dari formulir master data.
- Aktivasi dan nonaktivasi akun Ustadzah/Wali oleh Admin tanpa menghapus profil.
- Halaman profil untuk memperbarui informasi pribadi dan kata sandi.
- Dashboard khusus admin, ustadzah, dan wali santri.
- CRUD santri, ustadzah, halaqoh, kategori, indikator, surah, dan penilaian.
- Pencarian, detail data, laporan, dan tampilan cetak.
- Pengiriman laporan per santri melalui WhatsApp dan email wali.
- Cetak laporan per santri atau seluruh laporan dalam format A4 siap PDF.
- Antarmuka responsif untuk desktop dan perangkat seluler.
- Landing page publik dengan informasi lembaga, program, kegiatan, kontak, dan akses login.
- Form santri dua tahap untuk memisahkan identitas santri dan akun wali.
- Form penilaian rinci untuk hafalan, murojaah, karakter, dan catatan Ustadzah.
- Kategori dan indikator tambahan otomatis membentuk bagian baru pada form penilaian.
- Sinkronisasi 114 surat dari EQuran.id v2 dan Juz awal dari Al Quran Cloud.
- Pengurutan Data Surah berdasarkan Juz awal dan nomor resmi surat.
- Relasi Halaqoh–Surat; cakupan Juz dan jumlah surat dihitung otomatis.

## Struktur kode

```text
app/
  bootstrap.php     koneksi database, session, dan fungsi dasar
  pages.php         metadata, akses, dan konfigurasi halaman data
assets/
  app.css           fondasi visual dan layout
  components.css    komponen halaman data dan navigasi
  app.js            interaksi modal dan formulir
views/
  landing.php        halaman beranda publik
  pages/            isi dashboard dan halaman data
  partials/         sidebar, topbar, dan modal detail
  layout.php        kerangka utama aplikasi
index.php           routing dan controller permintaan
```

Untuk menambahkan master data baru, daftarkan metadata dan konfigurasi tabelnya di `app/pages.php`, lalu tambahkan konfigurasi form di `views/modal_form.php`.

### Menambah akun

- Akun Ustadzah dibuat melalui **Data Ustadzah → Tambah Data**.
- Akun Wali dibuat bersamaan melalui **Data Santri → Tambah Data**.
- Kata sandi awal akun Ustadzah dan Wali adalah `Tahfidz123!`. Kolom kata sandi dapat diisi jika Admin ingin menggunakan kata sandi lain.
- Pada kolom **Status Akun**, tombol `＋` membuat sekaligus mengaktifkan akun, `✓` mengaktifkan akun nonaktif, dan `⊘` menonaktifkan akun.

## Pengiriman laporan

- WhatsApp menggunakan tautan resmi `wa.me` dengan nomor dan isi laporan yang telah disiapkan. Pengguna tetap menekan tombol kirim di WhatsApp.
- Nama dan alamat email pengirim diatur melalui `config/mail.php` pada nilai `from_name` dan `from_email`.
- Email menggunakan fungsi `mail()` PHP. Pada server produksi, layanan email/PHP mail pada hosting harus aktif agar email benar-benar diterima.
- Notifikasi sukses berarti email telah diterima oleh server email untuk diproses, bukan jaminan sudah masuk ke kotak masuk. Status pengiriman akhir diperiksa melalui log email hosting; SMTP/API diperlukan jika ingin pelacakan terkirim, tertolak, atau terpental secara otomatis.
- Logika dan format pesan berada di `app/reports.php`, sehingga mudah diganti ke WhatsApp Business API atau layanan SMTP.
