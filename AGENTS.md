# Instruksi kerja project Hotel PMS

## Mandat terbaru 2026-10-25 — satu alur `main`

Pemilik mengarahkan agar `main` menjadi satu-satunya branch kerja aktif: lanjutkan pekerjaan di checkout VPS canonical `/home/saidadam28/workspace/hotel_bome_workflow` pada `main`, jalankan pemeriksaan yang sesuai, lalu commit path yang ditinjau dan push langsung ke `origin/main` setelah lulus. Jangan membuat branch fitur atau worktree baru untuk task rutin dan jangan force-push. Checkout lama yang masih dirty harus dipertahankan sampai isinya dibandingkan dan disatukan dengan bukti; push ke `main` tidak berarti deploy ke server live. Mandat ini menggantikan pembatasan branch lama di bawah.

## Mandat terbaru 2026-10-18

Untuk branch `work/hermes-full-build`, baca `docs/HERMES_AUTONOMOUS_BUILD.md`. Pemilik mendelegasikan perencanaan, riset keputusan sementara, implementasi dan pengujian berkelanjutan kepada Hermes. Mandat tersebut menggantikan batas DISC-001 saja, kewajiban menunggu Codex per task, dan gate keputusan rutin pada dokumen lama. Keputusan eksternal/legal/hardware tetap dilaporkan sesuai bukti; jangan mengarang persetujuan atau hasil. Semua prinsip keamanan/data/lisensi tetap berlaku.

Dokumen ini adalah pintu masuk ringkas untuk Codex dan agent lain. Hermes harus membaca dokumen ini secara eksplisit setiap sesi baru karena AGENTS.md belum tentu dimuat otomatis oleh runtime Hermes.

## Mulai setiap sesi

Dari root repository:

1. Periksa branch, status Git, dan commit terbaru. Jangan menimpa atau memasukkan perubahan yang sudah ada sebelum sesi.
2. Baca docs/AI_WORKFLOW.md dan docs/AI_WORKFLOW_STATE.md.
3. Baca .specify/memory/constitution.md.
4. Untuk fitur aktif saja, baca spec.md, plan.md, tasks.md, checklist terkait, dan file sumber yang dirujuk tugas. Baca docs/AI_PROJECT_CONTEXT.md atau docs/REBUILD_PLAN.md pada bagian relevan; baca keseluruhan dokumen saat membuat spesifikasi atau keputusan arsitektur.
5. Jangan memindai vendor, node_modules, .env, storage, backup, atau paket biner untuk orientasi umum.

Satu status state dan satu direktori fitur aktif adalah sumber kelanjutan. Jangan mengandalkan ingatan percakapan atau membuat ulang pekerjaan yang sudah tercatat.

## Fakta teknis dan arah produk

- Target adalah PMS hotel local-first. Rencana dan batasan produk ada di docs/AI_PROJECT_CONTEXT.md dan docs/REBUILD_PLAN.md.
- Implementasi Laravel baru berada di repository ini; baseline legacy yang dibahas di konteks adalah sumber pemetaan alur, bukan requirement final.
- composer.json dan composer.lock adalah bukti teknis untuk versi dan dependency yang benar-benar dipakai. Jangan menyamakan constraint manifest dengan versi terkunci.
- UI yang dipilih adalah Flux Pro 2.20.1, Livewire 4, dan Tailwind CSS 4. Jangan gunakan Filament pada aplikasi rebuild. Jangan menaikkan versi Flux tanpa persetujuan pemilik.
- Flux Pro berlisensi komersial. Jangan menambah atau mengunggah arsip paket komersial, kredensial Composer, token, .env, database produksi, atau data tamu ke Git.
- Operasi inti harus tetap lokal saat WAN mati. Integrasi online tidak boleh membuat front desk lokal berhenti.

## Pembagian peran dan batas perubahan

- Codex lokal menata requirement, pertanyaan bisnis, constitution, spesifikasi, rencana, tugas, acceptance criteria, serta review hasil Hermes.
- Hermes VPS mengimplementasikan hanya tugas siap yang tercatat pada fitur aktif. Ia boleh melanjutkan tugas-tugas independen yang sudah siap secara berurutan; jangan berhenti hanya karena satu task selesai. Catat status setiap task dan fitur sebelum berpindah sesi.
- Implementasi hanya boleh dimulai setelah fitur memiliki spec, plan, tasks yang dianalisis, dan keputusan pemblokir untuk task tersebut sudah diselesaikan. Jangan mengarang aturan bisnis yang belum disepakati.
- Alur sinkronisasi pemilik: setelah satu slice PMS selesai dan pemeriksaan lokalnya lulus, audit branch, remote, status, serta daftar path; commit hanya file slice yang sudah ditinjau, lalu push ke `main` agar pemilik dapat menarik dan memeriksa UI. Jangan push jika instruksi repo, remote/branch, identitas commit, atau isi commit bertentangan/tidak dapat diverifikasi; laporkan konflik spesifik.
- Jangan commit/push perubahan campuran yang belum diaudit. Dilarang stage massal; stage path eksplisit. Kecualikan secret, kredensial, `.env`, data tamu/produksi, arsip Flux Pro, `docs/product/BASELINE_EVIDENCE.md`, `tmp/`, dan file pengguna yang tidak terkait. Jangan reset, clean, stash, force-push, migrasi destruktif, atau mengubah konfigurasi/server produksi di luar lingkup. Push kode ke `main` tidak mengizinkan deploy atau perubahan live.
- Pertahankan perubahan pengguna yang sudah ada, termasuk perubahan Flux Pro 2.20.1, tmp/, dan file lokal lain yang bukan bagian dari tugas.

## Mutu dan keamanan

- Verifikasi setiap klaim dengan file, output perintah, test, atau observasi langsung. Jangan menyatakan lulus jika check belum dijalankan atau gagal.
- Untuk fitur UI, uji semua jalur yang disepakati: daftar/tabel, filter, input, validasi, tambah, lihat, ubah, dan lifecycle hapus/nonaktif/void yang benar untuk jenis data. Sertakan akses per role.
- Untuk perubahan backend, cakup unit, database/integration, authorization, tenant boundaries, validation, transaction, audit, dan regresi yang relevan.
- Security test harus menggunakan data uji, memeriksa IDOR/cross-hotel access, session/CSRF, XSS, mass assignment, upload/export, rate limits, replay bila ada integrasi perangkat, dan tidak membuka secret.
- Jalankan command dari dokumentasi project dan fitur aktif. Catat command dan hasil aktual. Jangan mengklaim seluruh aplikasi aman hanya dari pemeriksaan sebagian.
- Jika ada konflik antara requirement, kode, lockfile, atau instruksi; laporkan bukti dan dampaknya, lalu hentikan hanya pekerjaan yang terdampak. Lanjutkan tugas independen yang sudah jelas.
