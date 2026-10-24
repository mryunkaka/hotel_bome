# Rencana bangun ulang sistem hotel

**Rancangan discovery, 17 Oktober 2026.** Fondasi Laravel 13 + Livewire 4 sudah dibuat; modul PMS dan alur operasional belum dibangun.

## Tujuan

Sistem hotel berjalan lokal saat internet mati. Server hotel menyimpan transaksi; staf mengaksesnya melalui browser di komputer server atau jaringan lokal. Saat internet tersedia, sistem dapat mengirim data yang diizinkan dan menawarkan paket pembaruan.

## Fondasi yang disarankan

| Bagian | Pilihan |
|---|---|
| Framework | Laravel 13.x, PHP 8.5.x, versi patch dikunci saat build |
| UI | **Flux Pro 2.20.1 + Livewire 4 + Tailwind CSS 4.2+**; tanpa Filament |
| Database | MySQL 9.7 LTS lokal |
| Web/PHP Windows | FrankenPHP sebagai kandidat, service dikelola WinSW setelah diuji pada Windows target |
| Hardware pintu | Agent lokal terpisah hanya jika SDK/perangkat mengharuskannya |
| Update | Paket rilis bertanda tangan, backup, migrasi aman; pelanggan tidak menjalankan Git |

Flux adalah pustaka komponen UI, bukan pembuat seluruh panel hotel. Halaman, alur kerja, tabel, hak akses, dan validasi tetap dibuat khusus dengan Blade/Livewire. Ini memberi kendali tampilan jangka panjang, dengan biaya awal lebih besar daripada panel CRUD siap pakai. Flux Pro berlisensi komersial; lisensi dan hak distribusi untuk produk yang dijual perlu dipastikan pada akun pembelian.

## Kinerja dan penggunaan offline

- Aplikasi inti, CSS/JavaScript, font, ikon, database, dan layanan berjalan lokal; jangan gunakan CDN atau font eksternal.
- Jika semua jaringan terputus, komputer server tetap dapat dibuka melalui `localhost`; komputer staf lain memerlukan LAN hotel. MySQL hanya menerima koneksi lokal dari aplikasi.
- Flux/Livewire tidak perlu memuat ulang seluruh halaman untuk tiap aksi, tetapi aksi yang mengubah data tetap melakukan request lokal ke server. Hindari request pada setiap ketikan, pecah komponen besar, gunakan pagination, dan jangan aktifkan polling realtime tanpa kebutuhan.
- Tanpa Redis, Reverb, atau layanan tambahan pada tahap awal. Gunakan database lokal untuk session, antrean ringan, dan outbox; tambah layanan hanya bila pengukuran membuktikan perlu.
- Internet dibutuhkan untuk OTA, pembayaran online, booking publik, sinkronisasi pusat, dan pemeriksaan update. PMS lokal tetap bekerja saat semua layanan itu tidak tersedia; transaksi eksternal masuk antrean dan direkonsiliasi kemudian.

## Fitur sasaran

**Operasional inti:** hotel/kamar, tarif dan pajak, profil tamu, reservasi dan grup, walk-in, ketersediaan, check-in/out, deposit, beberapa folio/tagihan, pembayaran, invoice, cashier shift, night audit/closing, laporan, audit trail, backup/restore.

**Operasional lengkap:** housekeeping dan penugasan kamar, maintenance/engineering dan work order, lost & found, minibar dan persediaan, fasilitas/meeting/banquet/BEO, company/travel agent, loyalty, POS/room service, accounting (AR/AP, kas/bank, jurnal, GL, aset, anggaran), serta laporan operasional dan keuangan.

**Integrasi pilihan:** booking engine dan website, OTA/channel manager, payment gateway/QRIS, pengalaman mobile tamu, self check-in, digital key/RFID. Komponen tersebut bergantung pada vendor atau internet sehingga berada di atas PMS lokal, bukan syarat agar front desk dapat beroperasi.

Daftar lengkap alur, entitas data, risiko, serta peta fitur dari proposal ada di [AI_PROJECT_CONTEXT.md](AI_PROJECT_CONTEXT.md). Proposal sumber: :codex-file-citation{path="D:\\Project\\hotel_new_backup_20261017\\docs\\251112_Proposal Sistem Hotel DIP & Lavender Hotel (1).pdf" purpose="source"}

## Data, migrasi, dan pembaruan aman

- Transaksi yang sudah diposting tidak dihapus; koreksi memakai void/reversal. Data master dinonaktifkan. Relasi memakai `RESTRICT`/`SET NULL`, bukan penghapusan berantai untuk riwayat.
- Migrasi hanya dibuat saat skema berubah, satu kali per versi. Jangan gunakan `migrate:fresh` pada data pelanggan. Tambah kolom, pindahkan data, verifikasi, lalu hentikan kolom lama di rilis berikutnya.
- Sebelum update: cek ruang disk dan versi, backup database/file, terapkan migrasi kompatibel, cek kesehatan aplikasi, lalu aktifkan rilis. Simpan log dengan masa retensi dan bersihkan ekspor sementara secara terjadwal.
- Push Git hanya ke pipeline rilis privat. Installer pelanggan memverifikasi tanda tangan paket; tidak membawa token Git. EXE tidak dapat menjamin source PHP tersembunyi dari administrator komputer.

## Temuan penting dari aplikasi yang ada

Aplikasi legacy yang ditinjau sebelum fondasi rebuild memakai Laravel 12 dan Filament 4; catatan tersebut hanya peta alur dan risiko, bukan stack kode saat ini. Audit awal juga menemukan endpoint RFID belum memvalidasi token bridge dan belum mencatat scan dengan benar, terdapat relasi cascade yang berisiko menghapus riwayat, serta konsep `bookings` dan `reservations` yang perlu disatukan/ditetapkan. Integrasi pintu belum boleh dianggap siap sebelum diuji dengan perangkat sebenarnya.

## Tahapan pembangunan

1. Tetapkan aturan bisnis, peran, pajak, format laporan, perangkat lock, dan model satu/lebih hotel.
2. Sahkan ERD dan kebijakan retensi; siapkan installer offline, login, kamar/tamu, reservasi, front desk, folio, pembayaran, closing, laporan dan backup.
3. Tambahkan housekeeping, engineering, inventory, fasilitas, POS/room service, accounting dan modul lengkap lainnya sesuai prioritas operasional.
4. Integrasikan RFID/lock pada perangkat uji; kemudian sinkronisasi pusat dan updater bertanda tangan.
5. Uji instalasi bersih, restart, alur hotel penuh, pemulihan backup, update/migrasi, dan operasi tanpa internet.

## Riset teknologi (sumber primer dan komunitas)

- [Laravel release/support policy](https://laravel.com/framework/docs/releases), [deployment](https://laravel.com/framework/docs/deployment), [PHP supported versions](https://www.php.net/supported-versions.php)
- [MySQL 9.7 LTS policy](https://dev.mysql.com/doc/refman/9.7/en/mysql-releases.html)
- [Flux installation](https://fluxui.dev/docs/installation), [Flux license terms](https://fluxui.dev/terms), [Flux releases](https://github.com/livewire/flux/releases)
- [Livewire 4 hydration](https://livewire.laravel.com/docs/4.x/hydration), [Livewire installation](https://livewire.laravel.com/docs/4.x/installation)
- Komunitas: [pengalaman Livewire 4 + Flux](https://www.reddit.com/r/laravel/comments/1qk51s8/livewire_4_and_flux_the_best_way_to_build_web/) dan [diskusi kinerja Flux lama](https://www.reddit.com/r/laravel/comments/1lg6ljv/is_flux_too_slow_or_am_i_missing_something/). Ini anekdot, bukan benchmark. Diskusi lama tentang Flux v1/v2 awal bukan bukti kondisi v2.20.1; risiko yang masih relevan ialah ketergantungan komersial dan kebutuhan membangun alur CRUD sendiri.
- PMS pembanding: [Oracle PMS](https://www.oracle.com/hospitality/what-is-hotel-pms/), [OPERA tour](https://www.oracle.com/europe/hospitality/hotel-property-management/hotel-pms-software/tour/), [Mews Operations](https://help.mews.com/s/article/Mews-Operations), [Cloudbeds platform](https://www.cloudbeds.com/hospitality-platform/)
- [FrankenPHP Windows services](https://frankenphp.dev/docs/production/)

Pemilik memperbarui pilihan Flux Pro ke 2.20.1 dan lockfile pada working tree lokal menguncinya. Periksa lockfile serta ketersediaan artifact pada branch/clone yang digunakan sebelum implementasi. Verifikasi lisensi/kompatibilitas saat build dan jangan menaikkan versi otomatis.
