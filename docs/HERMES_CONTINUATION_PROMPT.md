# Hermes continuation prompt

Copy this prompt into Hermes when resuming the Hotel Bome build. It supplements `docs/HERMES_AUTONOMOUS_BUILD.md`; it does not replace the product requirements or acceptance criteria.

```text
Anda adalah Hermes yang melanjutkan build Hotel Bome. Pemilik telah mendelegasikan implementasi dan pengujian berkelanjutan melalui `docs/HERMES_AUTONOMOUS_BUILD.md`; lanjutkan mandat itu tanpa meminta persetujuan rutin dan jangan berhenti setelah satu task selesai.

CHECKPOINT YANG DIKETAHUI (verifikasi ulang sebelum bertindak)
- Branch kerja tunggal: `main`; commit handoff: `7dfbdf0`. Bila `origin/main` sudah lebih maju, lanjutkan dari commit terbaru yang tersinkron. Jangan force-push atau membuat branch/worktree baru.
- Checkout kanonis: container `/workspace/hotel_bome_workflow` (atau path kanonis eksplisit dari operator). Jalankan `cd` eksplisit dan pastikan `pwd` sebelum perintah Git.
- Fase produk terakhir yang tercatat: Phase 5 — Foundation acceptance, PARTIAL. Phase 0–3 planning/reconcile selesai; Phase 4 IAM audit lokal selesai dengan gate eksternal; Phase 6 core PMS belum mulai.
- UI-001: T001–T005 selesai; T006 masih menunggu visual browser review. OPS-001 T03a–T03c selesai; Windows/runtime/signing/pilot/network acceptance masih BLOCKED_EXTERNAL.
- VPS Linux pada checkpoint ini memakai `/home/saidadam28/workspace/hotel_bome_workflow`, dan Nginx melayani checkout itu di `127.0.0.1:8083`. Folder lama `hotel_bome` sudah dihapus oleh pemilik. Jangan buat ulang checkout tersebut.
- Pertahankan file pengguna `docs/product/BASELINE_EVIDENCE.md`, `packages/flux-pro-2.20.1.zip`, serta `tmp/` bila ada. Jangan stage arsip Flux, `.env`, build output, database, atau file lokal yang tidak terkait task.

MULAI SESI
1. Baca `AGENTS.md`, `docs/HERMES_AUTONOMOUS_BUILD.md`, `docs/AI_WORKFLOW.md`, `docs/AI_WORKFLOW_STATE.md`, `.specify/memory/constitution.md`, `docs/product/MASTER_BUILD_PLAN.md`, dan `docs/product/ROADMAP.md`. Baca spec/plan/tasks/acceptance hanya untuk feature yang akan dikerjakan.
2. Periksa `git status --short --branch`, `git rev-parse HEAD`, dan sinkronisasi dengan `origin/main`. Jika ada perubahan tracked atau commit yang tidak dikenal, jangan reset/clean/stash/overwrite; identifikasi dan pertahankan sebelum lanjut. Pull hanya fast-forward setelah checkout aman.
3. Tentukan dan laporkan dua hal: (a) fase proyek dari roadmap/master plan, dan (b) fase/task feature aktif dari plan/tasks. Jangan menyimpulkan fase hanya dari nomor spec atau persentase.
4. Baca snapshot paling atas `docs/AI_WORKFLOW_STATE.md`, lalu cocokkan dengan queue tunggal di `docs/product/MASTER_BUILD_PLAN.md`, register, dan task feature. Snapshot lebih baru daripada catatan historis. Bila status/urutan bertentangan, audit bukti, perbaiki dokumen yang relevan, lalu tulis snapshot konsisten sebelum perubahan kode.

LANJUTKAN PEKERJAAN
- Pilih task READY paling awal yang dapat dikerjakan secara independen. Mandat otonom mengizinkan Hermes menganalisis dan menyiapkan spec/plan/tasks/acceptance yang hilang, menjalankan analisis konsistensi, dan melanjutkan task siap. Jangan mengimplementasikan perilaku bisnis yang masih membutuhkan persetujuan owner, accountant, legal, vendor, signer, atau pilot; catat keputusan sementara beserta sumber dan lanjutkan task independen.
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
Tutup respons dengan ringkasan singkat dalam bahasa Indonesia:
`Fase proyek: ... | Feature/task: ... | Selesai: ... | TODO berikutnya: ... | Blocker: ... | Verifikasi/commit: ...`
Jika semua pekerjaan lokal selesai dan hanya acceptance eksternal tersisa, gunakan `READY_FOR_EXTERNAL_ACCEPTANCE` dan berikan langkah konkret yang masih diperlukan. Jangan menyebut seluruh PMS selesai hanya karena build atau sebagian test lulus.
```
