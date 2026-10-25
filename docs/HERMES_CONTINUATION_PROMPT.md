# Hermes continuation prompt

Copy this prompt into Hermes when resuming the Hotel Bome build. It supplements `docs/HERMES_AUTONOMOUS_BUILD.md`; it does not replace the product requirements or acceptance criteria.

```text
Anda adalah Hermes yang melanjutkan build Hotel Bome. Pemilik telah mendelegasikan implementasi dan pengujian berkelanjutan melalui `docs/HERMES_AUTONOMOUS_BUILD.md`; lanjutkan mandat itu tanpa meminta persetujuan rutin dan jangan berhenti setelah satu task selesai.

CHECKPOINT YANG DIKETAHUI (verifikasi ulang sebelum bertindak)
- Branch kerja tunggal: `main`; kode terbaru yang diverifikasi pada sesi sebelumnya ada di `d446c6f`. Bila `origin/main` sudah lebih maju, lanjutkan dari commit terbaru yang tersinkron setelah memeriksa status. Jangan force-push atau membuat branch/worktree baru.
- Checkout kanonis: container `/workspace/hotel_bome_workflow` (atau path kanonis eksplisit dari operator). Jalankan `cd` eksplisit dan pastikan `pwd` sebelum perintah Git.
- Fase produk terakhir yang tercatat: Phase 5 — Foundation acceptance, PARTIAL. Phase 0–3 planning/reconcile selesai; Phase 4 IAM audit lokal selesai dengan gate eksternal; Phase 6 dimulai pada RATE-001 rate-plan master, bukan berarti seluruh core PMS selesai.
- UI-001: T001–T005 selesai; T006 masih menunggu visual browser review. OPS-001 T03a–T03c selesai; Windows/runtime/signing/pilot/network acceptance masih BLOCKED_EXTERNAL. RATE-001-T01 sudah committed; RATE-001-T02 adalah calon task desain berikutnya setelah readiness diperiksa.
- Guest profile T01a saat ini hanya prototype. Jangan menyebut `display_name` atau free-text `notes` sebagai non-PII: nama tampilan dapat mengidentifikasi seseorang, dan nama lengkap termasuk data pribadi umum pada Pasal 4(3) UU 27/2022. D-007 tetap terbuka; gunakan data palsu saja dan jangan memperluas akses/field/export/sync atau menyebut fitur guest siap operasional sebelum gate diselesaikan.
- VPS Linux pada checkpoint ini memakai `/home/saidadam28/workspace/hotel_bome_workflow`, dan Nginx melayani checkout itu di `127.0.0.1:8083`. Folder lama `hotel_bome` sudah dihapus oleh pemilik. Jangan buat ulang checkout tersebut.
- Pertahankan file pengguna `docs/product/BASELINE_EVIDENCE.md`, `packages/flux-pro-2.20.1.zip`, serta `tmp/` bila ada. Jangan stage arsip Flux, `.env`, build output, database, atau file lokal yang tidak terkait task.

MULAI SESI
1. Baca `AGENTS.md`, `docs/HERMES_AUTONOMOUS_BUILD.md`, `docs/AI_WORKFLOW.md`, `docs/AI_WORKFLOW_STATE.md`, `.specify/memory/constitution.md`, `docs/product/MASTER_BUILD_PLAN.md`, dan `docs/product/ROADMAP.md`. Baca spec/plan/tasks/acceptance hanya untuk feature yang akan dikerjakan.
2. Periksa `git status --short --branch`, `git rev-parse HEAD`, dan sinkronisasi dengan `origin/main`. Jika ada perubahan tracked atau commit yang tidak dikenal, jangan reset/clean/stash/overwrite; identifikasi dan pertahankan sebelum lanjut. Pull hanya fast-forward setelah checkout aman.
3. Tentukan dan laporkan dua hal: (a) fase proyek dari roadmap/master plan, dan (b) fase/task feature aktif dari plan/tasks. Jangan menyimpulkan fase hanya dari nomor spec atau persentase.
4. Baca snapshot paling atas `docs/AI_WORKFLOW_STATE.md`, lalu cocokkan dengan queue tunggal di `docs/product/MASTER_BUILD_PLAN.md`, register, dan task feature. Snapshot lebih baru daripada catatan historis. Bila status/urutan bertentangan, audit bukti, perbaiki dokumen yang relevan, lalu tulis snapshot konsisten sebelum perubahan kode.

LANJUTKAN PEKERJAAN
- Pilih task READY paling awal yang dapat dikerjakan secara independen. Mandat otonom mengizinkan Hermes menganalisis dan menyiapkan spec/plan/tasks/acceptance yang hilang, menjalankan analisis konsistensi, dan melanjutkan task siap. Jangan mengubah blocker eksternal menjadi persetujuan lewat keputusan sementara. Catat keputusan teknis sementara beserta sumber, batasnya, dan task independen yang masih bisa dikerjakan.
- Selesaikan task siap secara berurutan sampai tersisa blocker nyata atau batas sesi. Jika task terhalang akses Windows/browser/hardware/akun/persetujuan eksternal, tandai `BLOCKED_EXTERNAL` dengan bukti, pihak yang dibutuhkan, dan tindakan konkret; lanjutkan task independen.
- Sebelum bekerja, tandai task `IN_PROGRESS` pada `tasks.md` dan snapshot. Setelah setiap task, tandai status hanya berdasarkan bukti, jalankan verifikasi yang relevan, dan update acceptance/state. Jangan mengklaim lulus jika command belum dijalankan.
- Ikuti Laravel/Livewire/Tailwind/Flux Pro 2.20.1 yang terkunci. Jangan gunakan Filament atau menaikkan Flux. Jaga keamanan, tenant boundary, audit trail, local-first behavior, lisensi, dan data pengguna.
- Stage path task secara eksplisit. Setelah pemeriksaan lulus, commit dan push slice yang ditinjau ke `origin/main` tanpa force-push sesuai mandat terbaru. Push bukan izin deploy; jangan mengubah layanan/konfigurasi live, database produksi, firewall, atau kredensial kecuali task dan mandat pemilik secara eksplisit mencakupnya.

PELACAKAN AGAR PEMILIK DAPAT MELIHAT PROGRES
Perbarui bagian `Current progress snapshot` paling atas di `docs/AI_WORKFLOW_STATE.md` setelah tiap task dan sebelum berhenti, termasuk bila terjadi error, batas konteks, atau provider gagal. Selalu catat:
- fase proyek dan statusnya (contoh `Phase 5 — Foundation acceptance: PARTIAL`);
- feature/task aktif dan fase internal feature, atau `none` bila tidak ada;
- task yang selesai sesi ini dengan file dan bukti;
- TODO berikutnya dalam urutan, masing-masing `READY`, `IN_PROGRESS`, `BLOCKED:<ID>`, atau `DONE`;
- blocker, pemilik/akses yang dibutuhkan, dan pekerjaan independen yang tetap bisa jalan;
- command verifikasi, exit code, jumlah test/assertion, keterbatasan, branch dan commit SHA;
- sublangkah tepat yang sedang dikerjakan dan satu tindakan konkret untuk resume.

Jangan mengandalkan riwayat percakapan sebagai progress board. `docs/AI_WORKFLOW_STATE.md` adalah snapshot resume yang harus bisa dibaca pemilik tanpa membuka log Hermes. Jangan buat persentase kecuali dihitung dari task eksplisit. Jika sesi berhenti di tengah task, biarkan status `IN_PROGRESS` dan jelaskan kondisi file agar sesi berikutnya meneruskan tepat dari sana.

LAPORAN SETIAP BERHENTI
Jangan mengirim laporan akhir ketika pekerjaan masih berjalan. Jangan menarasikan setiap file/perintah; bila update diperlukan, kirim satu update milestone berlabel `PROGRES SEMENTARA — MASIH BERJALAN` dan sebut task aktif. Hindari kalimat “selesai”, “semua lulus”, atau “laporan sesi” sebelum pemeriksaan akhir. Setelah menyatakan laporan akhir, jangan lanjutkan task lain dalam sesi yang sama.

Sebelum laporan akhir, lakukan satu gerbang penutupan: pastikan tidak ada tool/perintah yang masih berjalan; perbarui status tasks/acceptance/state; jalankan verifikasi setelah perubahan terakhir; tinjau `git diff --check`, diff, dan daftar file; stage path eksplisit; commit/push sesuai mandat; lalu verifikasi `HEAD == origin/main` dan `git status`. Jika ada task belum selesai atau perubahan tracked belum committed/pushed, laporkan sebagai `IN_PROGRESS` dengan file dan langkah resume, bukan sebagai hasil akhir. Jangan menulis status `pre-push` setelah push berhasil. Daftar untracked yang memang dipertahankan secara eksplisit. Bila state file ikut commit, catat di dalamnya commit task/aplikasi yang diverifikasi; cantumkan SHA akhir dari commit laporan itu di respons final agar state tidak mencoba menulis hash-nya sendiri.

Tutup respons dengan ringkasan singkat dalam bahasa Indonesia:
`Fase proyek: ... | Feature/task: ... | Selesai: ... | TODO berikutnya: ... | Blocker: ... | Verifikasi/commit: ...`
Jika semua pekerjaan lokal selesai dan hanya acceptance eksternal tersisa, gunakan `READY_FOR_EXTERNAL_ACCEPTANCE` dan berikan langkah konkret yang masih diperlukan. Jangan menyebut seluruh PMS selesai hanya karena build atau sebagian test lulus.
```
