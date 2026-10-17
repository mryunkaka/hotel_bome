# Status workflow dan handoff

Tanggal pembaruan: 17 Oktober 2026

## Repository

- Path lokal: `D:\Project\hotel_new`
- Remote: `https://github.com/mryunkaka/hotel_bome.git`
- Branch setup: `chore/spec-kit-workflow`, berasal dari `origin/main`; dokumen, skills, dan adapter Bash sudah di-push dan sinkron dengan VPS.
- `main` tidak diubah.
- Spec Kit CLI 1.2.1; integrasi Codex aktif dan berstatus `ok`; 10 skill Codex berada di `.agents/skills/`.
- Artefak bersama di `.specify/`: template, constitution, dan skrip PowerShell serta Bash.
- Baseline aplikasi: Laravel 13 / Livewire 4 starter; belum ada spesifikasi atau implementasi fitur PMS.

## Hermes VPS

- Container Hermes Agent 0.21.6. `uv 0.13.1` dan Spec Kit CLI 1.2.1 terpasang di `/opt/data/.local/bin`, pada mount persisten `/opt/data`.
- Checkout project: host `/home/saidadam28/workspace/hotel_bome_workflow`, di container `/workspace/hotel_bome_workflow`, branch `chore/spec-kit-workflow` mengikuti remote.
- Sepuluh skill Spec Kit aktif di `/opt/data/skills/speckit-*`. Symlink `/opt/data/.hermes/skills` menunjuk ke `/opt/data/skills` agar cocok dengan direktori skill yang dibaca Hermes Docker ini.
- Spec Kit menyatakan integrasi Hermes tidak aman untuk dipasang bersamaan (`multi_install_safe: false`). Metadata project karena itu tetap hanya mengelola integrasi Codex agar `specify integration status --json` berstatus `ok`. Skill global Hermes dan skrip Bash bersama dipasang terpisah. Jangan jalankan `integration install/uninstall/switch hermes` pada checkout Codex utama.
- Uji read-only melalui Hermes dengan model `hermes-worker-safe` berhasil setelah menjalankan `cd /workspace/hotel_bome_workflow` dan CLI absolut `/opt/data/.local/bin/specify`. Status `ok`, default Codex, sepuluh skill terlihat, dan tidak ada file yang berubah. Shell Hermes dapat mulai di `/workspace`; selalu lakukan `cd` eksplisit. CLI `specify` tanpa path mungkin tidak ditemukan; gunakan path absolut.

## Posisi pekerjaan

- Setup Codex, adapter Bash Hermes, serta dokumen handoff sudah di-commit dan di-push bertahap pada branch `chore/spec-kit-workflow`; verifikasi tip branch dengan Git sebelum sesi kerja.
- Belum ada fitur/spec PMS aktif. Hermes tidak boleh mulai mengubah aplikasi sampai Codex menyiapkan satu slice dengan spec, plan, tasks, acceptance, dan handoff.
- Pemeriksaan workflow: `specify check` dan status integrasi lokal Codex lulus; status integrasi VPS lulus; `bash -n .specify/scripts/bash/*.sh` lulus; uji read-only Hermes lulus.
- Test aplikasi tidak dijalankan karena perubahan tugas ini hanya menyiapkan workflow.
- Target Flux Pro dari pemilik: 2.20.1. Perubahan `composer.lock`, `packages/composer.json`, penghapusan arsip 2.20.0 yang terlacak, dan arsip 2.20.1 yang belum terlacak adalah perubahan working tree pengguna di `D:\Project\hotel_new`; tidak ada yang masuk commit workflow. Branch remote hasil `origin/main` mungkin masih memakai keadaan Flux sebelumnya. Sebelum task dependency, sepakati distribusi privat artifact Flux komersial dan verifikasi lockfile pada branch aktif.
- `tmp/pdfs/` berisi file lokal pengguna; jangan stage atau push.

## Protokol sesi

1. Baca `AGENTS.md`, `docs/AI_WORKFLOW.md`, dokumen state ini, constitution, dan hanya artefak fitur aktif.
2. Codex menentukan satu slice yang terbatas; Hermes hanya mengerjakan task siap pada branch fitur yang diserahkan.
3. Catat command, hasil, file yang berubah, hash commit/push, task tersisa, dan blocker. Jangan menerima klaim selesai tanpa review diff dan bukti yang dapat diulang.
4. Lanjutkan task independen yang siap hanya jika tidak ada keputusan bisnis, masalah akses/keamanan, risiko destruktif, kegagalan test yang belum dipahami, atau masalah sinkronisasi Git.
