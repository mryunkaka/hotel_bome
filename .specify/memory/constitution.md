# Konstitusi Project Hotel PMS

## Amendemen workflow 2026-10-25

Pemilik menetapkan `main` sebagai satu-satunya branch kerja aktif: lakukan task pada checkout VPS canonical, jalankan test/check, lalu commit path yang ditinjau dan push langsung ke `origin/main` setelah lulus. Jangan membuat branch/worktree rutin atau force-push. Preserve dan bandingkan checkout lama yang dirty sebelum menyatukannya. Push kode tidak sama dengan deployment live. Amendemen ini menggantikan arahan branch-only `work/hermes-full-build` dan larangan push main di amendemen lama.

## Amendemen eksekusi 2026-10-18

Mandat eksplisit pemilik dalam `docs/HERMES_AUTONOMOUS_BUILD.md` mengizinkan Hermes mengambil peran perencanaan, riset keputusan sementara, implementasi dan pengujian berkelanjutan. Requirement yang belum dikonfirmasi tetap diberi provenance sebagai keputusan sementara; approval legal/accountant/vendor dan hasil physical acceptance tidak boleh dikarang. Semua prinsip inti di bawah tetap berlaku.

## Prinsip inti

### I. Operasional local-first
Server hotel adalah sumber kebenaran transaksi harian. Login lokal, reservasi, check-in/out, folio, pembayaran, laporan, dan akses pintu tidak boleh bergantung pada ketersediaan WAN. Sinkronisasi dan provider online boleh tidak tersedia tanpa menghentikan operasi inti lokal.

### II. Integritas keuangan dan riwayat
Riwayat operasional dan transaksi yang sudah diposting tidak boleh hilang karena cascade delete atau tertimpa diam-diam. Koreksi memakai void, reversal, atau adjustment eksplisit beserta alasan. Data master yang dirujuk riwayat dinonaktifkan, bukan dihapus. Dokumen yang diterbitkan mempertahankan nilai dan dasar pajak/tarif saat penerbitan.

### III. Hak minimum dan isolasi properti
Setiap baca/tulis harus diotorisasi terhadap pengguna, role, dan properti yang aktif. Jangan mempercayai property ID atau identitas perangkat hanya karena dikirim oleh klien. Uji akses melalui URL langsung, relation selector, export, job, dan API untuk memastikan data properti lain tidak terbuka. Database dan diagnostic service tidak boleh terbuka ke jaringan yang tidak diperlukan.

### IV. Perlindungan data orang, credential, dan perangkat
Gunakan data palsu dalam test. Lindungi identitas/kontak tamu, password, token, nomor kartu, backup, dan rahasia perangkat. Jangan masukkan credential atau data pelanggan ke source, log, fixture, laporan, atau Git. Endpoint hardware harus autentik, anti-replay, idempotent, dan dapat diaudit sebelum dinyatakan aman.

### V. Rilis kecil yang dapat dipulihkan
Migration harus additive, sadar restart, dan mendefinisikan versi skema awal yang didukung. Jangan jalankan migrate:fresh terhadap data pelanggan. Sebelum upgrade, buat backup dan uji restore; cek ruang disk, versi, service health, dan jalur pemulihan. Penghapusan skema lama ditunda ke rilis berikutnya.

### VI. Pengiriman berbasis bukti
Requirement hanya selesai jika acceptance criteria lulus dan evidence dicatat. Pemeriksaan source saja tidak membuktikan alur aplikasi berfungsi. Laporkan command dan hasil aktual, cakupan yang belum diuji, serta blocker. Test yang tidak jalan atau gagal tidak boleh disebut lulus.

### VII. Deployment offline sederhana
Runtime, asset, font, script, dan dependency harus tersedia lokal. Tambahkan Redis, broker, search engine, Reverb, Docker, CDN, atau service lain hanya jika fitur yang disetujui dan pengukuran membuktikan kebutuhan.

## Batas teknologi dan lisensi

- Target teknologi pilihan pemilik adalah Laravel 13, Livewire 4, Tailwind CSS 4, dan Flux Pro 2.20.1. Gunakan composer.json dan composer.lock pada branch/clone yang sedang dikerjakan sebagai bukti dependency dan versi aktual. Jangan gunakan Filament pada rebuild.
- Jangan mengubah versi Flux Pro terpilih tanpa persetujuan pemilik. Verifikasi kompatibilitas package/runtime saat build.
- Flux Pro adalah software komersial. Patuhi entitlement dan syarat redistribusi. Jangan commit arsip Flux, credential Composer privat, salinan customer, atau secret lisensi.
- Pilihan PHP/MySQL/service Windows pada dokumen rencana adalah rekomendasi sampai diuji pada lingkungan target yang didukung.

## Workflow dan tata kelola

- docs/AI_PROJECT_CONTEXT.md dan docs/REBUILD_PLAN.md adalah sumber discovery produk. Pertanyaan yang belum terjawab tetap terbuka sampai pemilik atau keputusan terdokumentasi menyelesaikannya.
- Setiap fitur memiliki spec terbatas, technical plan, task yang dapat dikerjakan, dan acceptance criteria yang dapat diverifikasi sebelum implementasi.
- Codex/Hermes bekerja pada `main` sesuai mandat pemilik 2026-10-25; lakukan review dan pemeriksaan sebelum push langsung ke main. Jaga perubahan lain, jangan force-push, dan jangan menyamakan push dengan deployment.
- Uji UI, persistence, authorization, security, dan recovery sesuai scope acceptance. Tandai test selesai hanya setelah lulus aktual.
- Ubah prinsip ini hanya dengan alasan terdokumentasi dan maksud pemilik. Jangan mengubah rekomendasi sementara menjadi aturan permanen tanpa bukti.

## Governance

Konstitusi ini memandu Spec Kit dalam specification, planning, implementation, review, dan convergence. Jika bertentangan dengan aturan keselamatan yang lebih tinggi atau keputusan eksplisit pemilik, jelaskan konfliknya dan ubah konstitusi hanya saat kebijakan hasilnya jelas. Jika perilaku produk ambigu, jangan mengarang aturan: catat pertanyaan yang tepat, blokir pekerjaan terdampak, dan lanjutkan task independen yang siap.

Versi: 1.0.0 | Disahkan: 2026-10-17 | Terakhir diubah: 2026-10-17
