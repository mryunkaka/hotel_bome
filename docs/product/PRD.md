# Product Requirements Document — Hotel Offline PMS

**Status:** Draft untuk discovery pemilik dan hotel pilot
**Tanggal baseline:** 2026-10-17
**Sumber:** `docs/AI_PROJECT_CONTEXT.md`, `docs/REBUILD_PLAN.md`, `AGENTS.md`, dan inspeksi repository Laravel saat ini.

## Ringkasan

Bangun PMS untuk hotel kecil dan menengah yang menyimpan operasi harian di server hotel dan tetap bisa dipakai saat WAN mati. Staf memakai browser lokal; integrasi cloud, OTA, payment provider, dan update menjadi konektor tambahan yang tidak menghentikan front desk bila gagal.

## Pengguna dan kebutuhan

| Peran | Kebutuhan utama | Batas akses |
|---|---|---|
| Pemilik/administrator hotel | Menyiapkan hotel, pengguna, konfigurasi, laporan dan pemulihan | Hak admin dibatasi ke instalasi/properti yang ditugaskan |
| Resepsionis | Reservasi, kedatangan, kamar, folio, pembayaran dan keberangkatan | Tidak boleh membuka data/aksi properti lain atau melewati approval |
| Kasir/night auditor | Rekonsiliasi shift dan tanggal bisnis, laporan penutupan | Transaksi posted tidak dapat dihapus atau diubah diam-diam |
| Housekeeping/engineering | Status kamar, tugas, temuan dan work order | Hanya data operasional yang diperlukan |
| Akuntan/manajer | Ekspor dan laporan sesuai kewenangan | Aturan pajak/akuntansi ditetapkan bersama hotel |
| Staf pusat/support (opsional) | Lisensi, health, dukungan dan sinkronisasi yang diizinkan | Tidak otomatis memperoleh PII atau hak edit transaksi hotel |

## Tujuan produk

1. Selesaikan alur reservasi hingga checkout dan closing lokal tanpa koneksi WAN.
2. Jaga saldo, snapshot harga/pajak, jejak audit, dan riwayat transaksi.
3. Batasi akses menurut pengguna, role, dan properti pada semua jalur termasuk URL, export, job, dan API.
4. Instalasi, restart, backup, restore, dan update dapat dilakukan staf hotel tanpa tool developer.
5. Tambahkan modul operasi dan konektor secara bertahap tanpa menjadikan layanan cloud sebagai syarat operasi inti.

## Ruang lingkup

### Rilis operasional inti

- Instalasi lokal Windows dan first-run setup (pilihan runtime masih perlu divalidasi pada perangkat sasaran).
- Identitas lokal, role, approval, hotel/properti, outlet dan kamar.
- Guest profile, tarif/pajak yang disahkan, availability, reservasi individu/grup, walk-in.
- Check-in/out, folio, deposit, pembayaran/refund, invoice/receipt, cashier shift, business date/night audit.
- Laporan inti, audit trail, backup/restore dan update yang dapat dipulihkan.

### Rilis operasi lengkap

Housekeeping, engineering, inventory/procurement/minibar, fasilitas/event/catering, POS/room service, sales/company, loyalty, accounting lanjutan dan laporan terkait. Semua memiliki prioritas rilis sendiri dan bergantung pada discovery pemilik.

### Konektor opsional

Doorlock/RFID lokal, booking engine/OTA, payment gateway/QRIS, guest portal/self check-in, sinkronisasi pusat, lisensi dan signed updater. Modul ini dikerjakan setelah kontrak vendor, kebijakan data, akses perangkat, dan kebutuhan rilis dikonfirmasi.

## Di luar scope awal

- Menganggap instalasi multi-hotel, mode LAN, metode pajak, format invoice, chart of accounts, perangkat lock, atau sync PII sudah diputuskan.
- Mengklaim kompatibilitas/keamanan perangkat keras tanpa pengujian model nyata.
- Menyembunyikan source PHP dari administrator mesin hanya dengan membundel EXE.
- Menjalankan database pelanggan melalui Git, migration destructive, atau akses internet sebagai syarat operasi harian.

## Prinsip requirement

- Operasi inti lokal; konektor online gagal tertutup dengan status antre/gagal/rekonsiliasi.
- Data posted bersifat immutable; koreksi memakai reversal/void/adjustment dengan alasan, aktor, dan waktu.
- Master bersejarah dinonaktifkan, bukan dihapus.
- Uang memakai presisi desimal dan mata uang eksplisit; dokumen diterbitkan menyimpan snapshot nilai yang disahkan.
- Role, tenant/properti, dan izin diperiksa server-side di setiap endpoint/aksi.
- Tidak ada secret, data tamu, atau arsip Flux Pro pada Git.

## Ukuran keberhasilan dan gate rilis

- Seluruh alur reservation → check-in → charge/payment → invoice/folio → checkout → close lulus pada mesin uji dan tetap lulus tanpa WAN.
- Restore backup terenkripsi berhasil pada mesin kedua; versi dan invariant data terverifikasi.
- Uji akses lintas properti gagal aman pada halaman, action, selector, export, job, dan API yang tersedia.
- Instalasi bersih dan restart service berjalan tanpa Node/npm/Composer/Git pada mesin hotel.
- Setiap acceptance wajib memiliki bukti test dan hasil; hardware/deployment yang belum diuji berstatus BLOCKED, bukan PASS.

## Keputusan terbuka

Pemilik telah menetapkan satu instalasi mendukung beberapa hotel, akses melalui server dan beberapa workstation LAN, serta registrasi mandiri dengan akun pending approval oleh admin sebelum login. Keputusan pajak, laporan, cashier/approval, perangkat, PII sync, lisensi dan prioritas integrasi masih terbuka; lihat `docs/product/OPEN_DECISIONS.md`.

## Kondisi aplikasi saat ini

Repository adalah Laravel 13 + Livewire 4 starter dengan login, registrasi, profil, keamanan, dashboard placeholder, dan tes starter. Belum ada model atau workflow PMS. Manifest root adalah bukti constraint; lockfile lokal milik pengguna menunjukkan Flux Pro 2.20.1 tetapi arsip/package changes tersebut belum di-commit dan tidak boleh ikut handoff Git. Inspeksi ini bukan audit lengkap seluruh kode.
