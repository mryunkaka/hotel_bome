# Workflow state dan handoff

Last updated: 2026-10-17

## Repository

- Local path: `D:\Project\hotel_new`
- Remote: `https://github.com/mryunkaka/hotel_bome.git`
- Setup branch: `chore/spec-kit-workflow` (dibuat dari `origin/main`)
- `main`: tidak diubah
- Spec Kit CLI: 1.2.1; integrasi Codex dipasang di repo dan tervalidasi.
- Codex skills: 10 skill `$speckit-*` di `.agents/skills/`.
- Fondasi aplikasi: Laravel 13 / Livewire 4 starter; fitur PMS belum dimulai.

## Posisi pekerjaan

- Panduan persisten tersedia di `AGENTS.md`, `docs/AI_WORKFLOW.md`, dan dokumen konteks/rencana.
- `.specify/memory/constitution.md` berisi prinsip proyek; belum ada spesifikasi fitur aktif.
- Integrasi Hermes di VPS dan sinkronisasi branch setup: belum diverifikasi pada sesi ini.
- Tes aplikasi: tidak dijalankan; tugas ini menyiapkan workflow, bukan fitur aplikasi.
- Flux Pro target pemilik: 2.20.1. Perubahan `composer.lock` dan `packages/composer.json` serta arsip paket lokal adalah perubahan working tree pengguna; arsip komersial tidak ikut commit/push. Branch setup hasil `origin/main` mungkin masih berisi versi Flux sebelumnya. Sebelum Hermes mulai task yang memasang dependency Flux, sinkronkan paket/lockfile secara privat dan verifikasi lisensi serta ketersediaan artifact.
- `tmp/pdfs/` adalah file lokal pengguna dan tidak termasuk workflow commit.

## Protokol sesi Codex/Hermes

1. Baca `AGENTS.md`, lalu `docs/AI_WORKFLOW.md` dan bagian relevan `docs/AI_WORKFLOW_STATE.md`; baca konteks/rencana dan artefak fitur aktif saja.
2. Codex membuat satu slice berisi spec, plan, tasks, kriteria uji dan handoff sebelum Hermes mengubah kode.
3. Hermes hanya mengerjakan task yang siap dan diizinkan dalam artefak; jangan menyimpulkan keputusan bisnis. Catat commit, file, perintah uji dan hasil.
4. Codex memeriksa diff serta bukti hasil, memperbarui state, lalu menyiapkan slice berikutnya.
5. Teruskan task siap berikutnya hanya selama tidak ada blocker/keputusan manusia, gagal validasi, risiko destruktif atau masalah keamanan.

## Pembaruan per sesi

Catat branch dan commit yang diperiksa, slice aktif, status task, perubahan, perintah uji dan hasil, commit/push hash, serta langkah berikutnya. Jangan menyatakan fitur selesai tanpa pemeriksaan diff dan bukti yang dapat diulang.
