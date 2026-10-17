# Workflow AI: Codex lokal ke Hermes VPS lalu kembali ke Codex

## Tujuan

Workflow ini membawa pekerjaan melalui requirement yang dapat ditinjau, implementasi terbatas, pengujian nyata, dan review. File ini bersama docs/AI_WORKFLOW_STATE.md menjadi catatan lintas sesi. Setiap sesi baru wajib membaca keduanya; jangan bergantung pada chat history atau ingatan model.

## Pintu masuk sesi baru

Jalankan dari root repository. Codex membaca AGENTS.md, dokumen workflow dan state, lalu hanya artefak fitur aktif. Hermes harus menerima instruksi eksplisit untuk membaca file yang sama karena integrasi Hermes dapat menggunakan skill global dan tidak otomatis memuat instruksi Codex.

Perintah awal Hermes yang dapat ditempel pada sesi baru:

> Baca AGENTS.md, docs/AI_WORKFLOW.md, docs/AI_WORKFLOW_STATE.md, dan .specify/memory/constitution.md dari root repo. Pastikan branch dan Git status sebelum perubahan. Baca hanya spec/plan/tasks untuk fitur yang ditandai aktif di state. Lanjutkan tugas ready pada branch fitur tersebut, jangan mengulang task yang sudah done, jangan menyentuh main, rahasia, data produksi, atau perubahan pengguna. Jalankan verifikasi yang diminta, catat bukti aktual dan perbarui state serta handoff. Jika ada blocker bisnis, akses, keamanan, atau data, catat blocker; lanjutkan task independen yang tidak terpengaruh.

Jika branch atau commit lokal berbeda dari remote yang disebut dalam state, berhenti sebelum edit dan laporkan hash serta status. Jangan menebak branch.

## Sumber kebenaran

1. Requirement bisnis terbaru yang secara eksplisit disetujui pemilik.
2. Spec Kit constitution untuk prinsip yang berlaku lintas fitur.
3. docs/AI_PROJECT_CONTEXT.md dan docs/REBUILD_PLAN.md untuk temuan legacy, target produk, risiko, urutan rilis, dan pertanyaan discovery.
4. spec.md fitur untuk scope/acceptance fitur; plan.md untuk pendekatan teknis; tasks.md untuk unit implementasi; checklist bila tersedia.
5. Kode, composer.json, composer.lock, migration, dan test untuk perilaku serta versi teknis yang benar-benar ada.
6. docs/AI_WORKFLOW_STATE.md untuk status dan handoff terkini.

Dokumen discovery memuat rekomendasi dan sejumlah keputusan yang belum dikonfirmasi. Rekomendasi bukan izin untuk menganggap pertanyaan bisnis sudah terjawab. Jika sumber bertentangan, catat konflik dan jangan diam-diam mengubah scope.

## Peran

### Codex lokal

- Memimpin discovery dan memecah target PMS menjadi slice yang dapat ditinjau.
- Membuat atau memperbarui constitution, spec, plan, tasks, checklist, serta keputusan bisnis.
- Menjalankan analisis konsistensi sebelum implementasi.
- Menyiapkan branch dan handoff yang menyebut branch, commit dasar, fitur, file artefak, task siap, task tertahan, serta command verifikasi.
- Setelah Hermes selesai, memeriksa diff, test evidence, cakupan acceptance, keamanan, dan status remote. Jangan menerima klaim tanpa bukti.

### Hermes VPS

- Mengambil branch dan commit sesuai handoff; tidak bekerja dari folder atau branch lain.
- Menjalankan tugas implementasi berurutan pada satu fitur aktif; teruskan ke task berikutnya yang statusnya ready sampai semua task fitur selesai atau tersisa blocker.
- Memperbarui tasks.md hanya sesuai hasil yang diverifikasi, menambahkan temuan dan keputusan ke artefak yang tepat, dan menjaga state/handoff tetap akurat.
- Menjalankan serta melaporkan verifikasi. Commit dan push hanya branch fitur, tanpa force-push dan tanpa mengubah main.
- Tidak memperluas fitur sendiri, memasang dependency/tool/plugin dari luar tanpa alasan yang dicatat dan tinjauan lisensi/keamanan, atau mengubah requirement demi mengatasi kegagalan test.

### Siklus Codex → Hermes → Codex

1. Codex membaca status, kode terkait, dan dokumen produk; menyelesaikan discovery yang diperlukan.
2. Codex menggunakan $speckit-specify untuk satu slice yang bounded. Fitur tidak berarti seluruh PMS sekaligus.
3. Codex menjalankan $speckit-clarify bila ada ambiguitas penting, lalu $speckit-plan dan $speckit-tasks. Gunakan $speckit-checklist untuk acceptance quality dan $speckit-analyze sebelum handoff.
4. Codex menulis handoff pada docs/AI_WORKFLOW_STATE.md: scope, feature directory, branch/base hash, tasks ready/blocked, command test, dan larangan spesifik.
5. Hermes menjalankan $speckit-implement pada feature aktif atau command yang ditampilkan adapter Hermes. Skill Codex menggunakan nama $speckit-*; jangan menganggap command terminal dan skill chat sebagai hal yang sama.
6. Hermes menjalankan test dan pemeriksaan keamanan sesuai scope. Ia memperbarui task menjadi done hanya dengan evidence yang lolos, menyimpan hasil di file fitur/QA yang relevan, lalu memperbarui state dan push branch.
7. Codex fetch/review branch, memeriksa perubahan serta bukti, menjalankan $speckit-converge dan $speckit-analyze bila perlu. Perbaikan atau task sisa kembali melalui siklus yang sama.
8. Ulangi untuk slice berikutnya. Workflow berakhir setelah scope produk yang disepakati lengkap, acceptance dan keamanan terverifikasi, serta tak ada task ready atau blocker yang belum dicatat.

## Urutan produk dan pemecahan kerja

Gunakan fase pada docs/REBUILD_PLAN.md sebagai roadmap, bukan satu mega-feature:

1. Discovery aturan bisnis dan keputusan operasional yang masih terbuka.
2. Installer/fondasi aplikasi, identitas, property, kamar, guest, rates, ketersediaan, reservasi, front desk, folio, pembayaran, cashier, night audit, laporan dasar, audit, backup/restore.
3. Housekeeping, maintenance, inventory/procurement, minibar, fasilitas/event/catering, POS/room service, sales, laporan lanjutan.
4. Akuntansi lanjutan dan RFID/doorlock setelah keputusan akuntan, protokol, hardware dan lisensi vendor disahkan.
5. Integrasi online, sinkronisasi, booking/OTA, payment gateway, guest portal, signed update dan updater setelah perilaku offline inti stabil.

Setiap modul atau alur dipisah lagi menjadi slice yang dapat diuji ujung ke ujung. Jangan membuat satu spec yang hanya menyalin seluruh AI_PROJECT_CONTEXT.

## Kriteria implementasi dan pengujian

- Semua field input dalam scope punya sumber, tipe, validasi server-side, batas panjang/range, pesan error yang dapat dipahami, serta otorisasi.
- Semua halaman dan tabel punya empty/loading/error state, pagination/filter yang disepakati, dan pemeriksaan akses; jangan menganggap rendering sebagai bukti CRUD.
- Setiap data master menguji list/create/view/update/disable/restore atau delete sesuai aturan retensi. Data transaksi yang telah diposting diuji void/reversal, bukan hapus.
- Uji alur integrasi dari UI hingga database dan output yang relevan; verifikasi perubahan status, audit, total uang, pajak/rate snapshot, serta konsistensi antar entitas.
- Regression test setelah tiap slice dan acceptance end-to-end pada alur resepsionis. Uji restart/offline/LAN, backup-restore, installer dan upgrade saat scope rilis memerlukannya.
- Security checks menyertakan role/access matrix, IDOR/tenant isolation, session/CSRF, XSS, validation/mass assignment, upload/export, secrets/logging, replay/idempotency untuk device API, dan batas jaringan.
- Gunakan database dan identitas palsu khusus test. Jangan jalankan destructive migration atau test terhadap database hotel.
- Laporan test mencantumkan nama/command, exit status, ringkasan hasil, skenario yang belum tercakup dan alasan. Screenshot boleh mendukung hasil UI, tetapi tidak menggantikan assertion dan bukti server.

## Aturan perubahan dan sinkronisasi

- Jangan stage seluruh repository. Stage path yang dimiliki task saja dan periksa git diff --cached sebelum commit.
- Jangan menambahkan .env, credential, auth.json, dump database, file upload pelanggan, atau arsip Flux Pro.
- Commit harus menjelaskan fitur dan menyertakan hanya scope yang diverifikasi. Push hanya branch fitur yang diperintahkan oleh handoff; pastikan remote URL/branch benar.
- Setiap perubahan state, decisions, acceptance, tests, blockers, atau hash branch segera dicatat di docs/AI_WORKFLOW_STATE.md sebelum sesi berakhir.
- Jika Hermes berjalan non-stop, ia boleh meneruskan hanya task ready yang sudah tercantum pada task list. Ia harus berhenti pada keputusan bisnis, izin, risiko keamanan/destruktif, perubahan eksternal berbiaya, kegagalan test yang belum dipahami, atau hilangnya sinkronisasi Git.
