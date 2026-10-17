# Keputusan Terbuka

## Delegasi riset dan keputusan sementara 2026-10-18

Pemilik mengizinkan Hermes melanjutkan pembangunan tanpa pertanyaan rutin: gunakan sumber primer dan kasus serupa, catat rekomendasi/default configurable dalam `docs/product/DECISION_LOG.md` beserta provenance. D-004..D-010 tetap belum menjadi fakta legal/SOP/vendor yang dikonfirmasi; sementara itu boleh membangun perilaku konservatif yang bisa dikonfigurasi, adapter disabled dan contract tests. Jangan memalsukan hak lisensi, compliance, credentials, perangkat atau hasil Windows. Lihat mandat lengkap `docs/HERMES_AUTONOMOUS_BUILD.md`.

Catatan ini memisahkan fakta, asumsi sementara, dan keputusan pemilik. Keputusan baru hanya dianggap sah setelah jawaban pemilik atau persetujuan pihak berwenang tercatat di sini beserta tanggal dan dampaknya.

## Konfirmasi pemilik yang memblokir rancangan dasar

| ID | Pertanyaan | Dampak | Status |
|---|---|---|---|
| D-001 | Satu atau beberapa hotel per instalasi? | Model property, membership, role, unique key dan tenant isolation | **Diputuskan pemilik 2026-10-17:** beberapa hotel per instalasi |
| D-002 | Server saja atau beberapa workstation melalui LAN? | Bind/firewall, session/device model, Windows pilot topology | **Diputuskan pemilik 2026-10-17:** server + beberapa PC staf melalui LAN; subnet/firewall tetap harus dikonfigurasi per lokasi |
| D-003 | Bagaimana registrasi dan admin awal bekerja? | Exposure akun dan bootstrap/install acceptance | **Diputuskan pemilik 2026-10-17:** self-registration tetap tersedia; akun baru pending sampai admin approve. Admin pertama dibuat melalui wizard lokal sekali saat instalasi, lalu wizard terkunci |
| D-004 | Negara/pajak/mata uang, invoice format/numbering dan tanggal efektif? | Rate snapshot, invoice, reports, finance tests | Terbuka; perlu owner/accountant |
| D-005 | Aturan cancellation/no-show, waitlist/overbooking, deposit, room-share/move, split folio dan approvals? | Reservation/front desk/cashier state transitions | Terbuka; perlu SOP hotel |
| D-006 | Model lock, card family, reader, SDK and redistribution rights; issue credential or read UID only? | Hardware protocol, threat model, licensing, device tests | Terbuka; butuh vendor/device pilot |
| D-007 | Data tamu apa boleh keluar hotel, sync one-way atau central edits, consent/retention? | Sync payload, privacy, conflict handling | Terbuka; blokir sinkronisasi PII |
| D-008 | License offline grace, renewal, support/update ownership; Flux Pro entitlement for multi-customer distribution? | Customer install and commercial package terms | Terbuka; perlu pemilik/vendor terms |
| D-009 | Which of OTA, gateway/QRIS, POS, QR ordering, guest portal and accounting is release-critical? Which providers? | Vendor contracts and phase scope | Terbuka; perlu owner/vendor/accountant |
| D-010 | Approval limits, dual control, tax invoice corrections, business date reopening, reports, payment tenders/cash close SOP? | Cashier/night audit/reports/accounting | Terbuka; perlu hotel/accountant |

## Asumsi sementara yang aman

- Semua transaksi inti terjadi di server lokal dan tidak membutuhkan internet.
- Data sumber dari legacy adalah discovery evidence, bukan requirement final.
- Tidak mengirim PII atau memproses pembayaran eksternal sebelum kebijakan dan provider disahkan.
- Posted finance dan audit bersifat immutable; koreksi melalui reversal/adjustment.
- FrankenPHP/WinSW dan MySQL Windows masih kandidat sampai instalasi nyata lulus di versi Windows sasaran.
- Role awal mengikuti least privilege; nama dan limit kewenangan final menunggu SOP.
- Flux Pro 2.20.1 tetap versi pilihan pemilik; arsip proprietary tetap lokal dan dikeluarkan dari commit/push.

## Update log

- 2026-10-17: D-001..D-010 dikompilasi dari `docs/AI_PROJECT_CONTEXT.md` dan pertanyaan baru untuk bootstrap. Jawaban pemilik belum tercatat.
- 2026-10-17: Pemilik menetapkan D-001 multi-hotel, D-002 server + beberapa workstation LAN, dan D-003 self-registration pending approval; admin pertama dibuat lewat wizard lokal sekali pakai. Behavior starter kit perlu mengikuti keputusan tersebut.
