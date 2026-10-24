# Журнал решений

## GUEST-D7 — Batas teknis sementara untuk profil tamu (bukan persetujuan hukum)

- Status: keputusan teknis sementara di bawah mandat build otonom; D-007 tetap terbuka dan GUEST-001 implementasi PII tetap diblokir sampai owner/privacy counsel menentukan dasar pemrosesan, tujuan, notice/consent bila berlaku, retensi per kategori, hak akses/permintaan, pengungkapan, dan penghapusan.
- Sumber primer: UU RI No. 27 Tahun 2022 tentang Pelindungan Data Pribadi, JDIH BPK (diakses 2026-10-23), khususnya Pasal 4 (kategori data), Pasal 5–13 (hak subjek), Pasal 16(2)(a–h) (pengumpulan terbatas/spesifik, tujuan, hak, keamanan, transparansi, retensi), Pasal 20 (dasar pemrosesan), Pasal 43–44 (penghapusan/pemusnahan). Teks resmi: https://peraturan.bpk.go.id/Download/224884/UU%20Nomor%2027%20Tahun%202022.pdf ; status metadata BPK: https://peraturan.bpk.go.id/Details/229798/uuno-27-tahun-2022 . Ini ringkasan sumber, bukan nasihat hukum atau penetapan kewajiban spesifik hotel.
- Rekomendasi teknis sementara: jangan implementasikan guest document upload, salinan identitas, nomor identitas, DOB, data kesehatan/keuangan, consent capture/legal-basis selector, export PII, atau sync PII. Tidak ada guest PII saat ini. Guest profile slice belum READY sampai field/tujuan disepakati.
- Bila nanti field minimum disetujui: setiap field harus punya tujuan dan dasar pemrosesan tercatat; nama tampilan saja tidak cukup untuk mengklaim anonymous. Gunakan property-scoped CRUD, deny-by-default, audit akses/perubahan, redaksi log, tidak mengirim data keluar instalasi, dan reversible merge review. Hard delete/pemusnahan hanya melalui prosedur terotorisasi setelah pemeriksaan kewajiban arsip/hukum/riwayat transaksi; jangan membuat retention duration default tanpa keputusan.
- Task teknis yang bisa disiapkan tanpa guest data: inventory processing/data flows dan threat/retention matrix sebagai artefak desain; acceptance: tidak menambah schema/route/PII, setiap proposed field punya purpose/basis/recipient/retention/role/deletion handling, dan semua TBD ditandai BLOCKED_EXTERNAL. Implementasi profile baru setelah review owner/privacy counsel.
- Revisi: pemilik/privacy counsel menetapkan purpose, dasar, field, retensi, hak akses/permintaan, kebutuhan pencatatan tamu dan aturan penghapusan; akuntan/legal menentukan konflik dengan dokumen bisnis/arsip. Rekomendasi ini bukan approval D-007.

## Sumber eksternal GUEST-D7

- Undang-Undang Republik Indonesia Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi: https://peraturan.bpk.go.id/Download/224884/UU%20Nomor%2027%20Tahun%202022.pdf
- Metadata dan status di JDIH BPK: https://peraturan.bpk.go.id/Details/229798/uuno-27-tahun-2022

## PROP-D1 — Identitas dan lifecycle dasar properti

- Status: asumsi teknis sementara untuk CRUD awal; tidak menyelesaikan aturan hotel, keuangan, atau akses staf.
- Sumber: keputusan pemilik: satu instalasi multi-hotel; `specs/004-property-guest-foundation/spec.md` FR-001 dan `docs/product/ROADMAP.md` Phase 2.
- Bentuk data: nama wajib max 160 karakter; kode wajib unik, uppercase, max 20, hanya huruf/angka/hyphen; status `is_active` default true.
- Hak akses: hanya `platform_admin` instalasi dapat membuat/mengubah/nonaktifkan; staff tidak bisa memanipulasi ID via URL. Tidak ada hard delete; nonaktifkan master.
- Batas: belum mencakup timezone, alamat, mata uang, pajak, outlet, membership, authorization per hotel untuk staf, audit event. Tambah sebagai slice terpisah setelah ditemukan kebutuhan teknis/SOP; jangan memakai default ini untuk invoice/operasi.
- Alasan: kode stabil dan unique membantu route/import tanpa menebak aturan finansial; nonaktif lebih aman daripada menghapus properti bersejarah.
- Revisi: field profil tambahan menunggu perbandingan pilot hotel; role/property membership harus menggantikan akses platform-admin global sebelum property CRUD diberikan kepada staff.

## PROP-D2 — Identitas inventory lokal (irisan awal)

- Status: default struktur sementara, hanya untuk membuat daftar master dan tidak menghitung ketersediaan/biaya.
- Sumber: `specs/004-property-guest-foundation/spec.md` FR-001/FR-002 meminta property boundary, outlet, tipe/kamar dan riwayat; owner menetapkan instalasi multi-hotel dan LAN. Belum ada SOP yang menentukan floorplan, occupancy, outlet usage, atau room state semantics.
- Bentuk: outlet, room type dan room memiliki `property_id`, kode unik per property dan `is_active`; nama room/outlet/type wajib; room code + type wajib. Tipe kamar dan kamar memakai composite FK property agar referensi lintas hotel mustahil. Nonaktifkan master, tidak ada hard delete.
- Hak akses: sementara hanya platform-admin yang dapat melihat/mengubah inventory karena IAM-001 property membership/policy belum diimplementasikan. Semua daftar, create dan deactivate tetap dibatasi `property_id` route; staff ditolak.
- Lifecycle sementara: room type tidak dapat dinonaktifkan jika masih memiliki room aktif; room harus dinonaktifkan lebih dulu. Room baru hanya dapat memakai tipe aktif dari property yang sama. Outlet tidak terhubung ke harga/tender.
- Batas: belum ada status room occupancy/cleaning, room moves, block dates, status history, availability, audit event atau izin hotel-admin/front-desk. Data ini tidak boleh dipakai untuk alokasi booking atau billing.
- Opsi revisi: tambah status history/block sebagai tabel event terpisah setelah availability/SOP disepakati; tambah outlet scope features jika hubungan outlet->POS/payment ditentukan.
- Pengujian awal SQLite membuktikan unique-per-property, composite property/type relation, request validation, disable retention, IDOR route scope dan staff deny. MySQL/locking race perlu uji DB target sebelum operasi produksi.

---

## PROP-D3 — Room housekeeping status dan block dates interim

- Status: default teknis sementara yang dapat direvisi; hanya mendefinisikan state operasional, bukan SOP housekeeping atau kontrak reservation.
- Sumber: `specs/004-property-guest-foundation/spec.md` FR-002 meminta room status, maintenance block, unavailable dates dan histori. Oracle OPERA membedakan housekeeping status dari Out of Order/Out of Service; status housekeeping meliputi clean/dirty dan inspected opsional [1], sedangkan room block memiliki rentang tanggal dan memengaruhi penugasan/availability [2]. Ini referensi pola produk, bukan persetujuan SOP Hotel Bome.
- Pemisahan state: housekeeping adalah kondisi kebersihan; occupancy tetap milik stay/reservation; `is_active` tetap lifecycle master; room block adalah periode unavailable. Jangan memakai satu kolom status untuk mewakili empat konsep itu.
- State interim: `dirty`, `clean`, `inspected`. Room baru/backfill mulai `dirty` agar tidak dianggap siap tanpa tindakan; `clean` berarti housekeeping mencatat layanan selesai; `inspected` berarti supervisor memeriksa setelah bersih. `inspected` tidak diwajibkan untuk penggunaan, dan tidak satu pun state ini menghitung ketersediaan atau mengizinkan check-in dengan sendirinya.
- Transisi sah: initial → `dirty`; `dirty` → `clean`; `clean` → `inspected` atau `dirty`; `inspected` → `dirty`. Transisi `dirty` → `inspected` ditolak; cleaning ulang setelah dirt ditemukan dicatat sebagai `dirty` lalu `clean`. Tiap perubahan adalah event append-only dengan state sebelum/sesudah, actor, waktu, dan alasan opsional; koreksi menambah event, bukan menulis ulang histori.
- Block interim: satu row `room_blocks` memiliki tanggal mulai/akhir bertipe DATE dalam kalender properti, inklusif, reason wajib, actor, dan waktu pencatatan. `start_date <= end_date`; validasi dilakukan di service/request. Row block tidak memiliki mutable status; pembatalan ditulis sekali ke `room_block_cancellations` (actor, waktu, reason) dengan FK unik ke block. Tanggal terblokir bila interval mencakup tanggal dan belum ada event pembatalan. Overlap antarblock diizinkan; tanggal kembali terbuka setelah semua block yang mencakupnya dibatalkan atau rentangnya berlalu.
- Konsekuensi terbatas: helper `hasActiveBlockOnDate` hanya menjawab apakah ada block aktif yang mencakup tanggal; ia bukan kalkulasi booking availability. Ia tidak menghapus kamar dari master dan tidak mengubah occupancy, revenue, room count atau laporan. Tidak mengadopsi perbedaan Out of Service vs Out of Order atau aturan jual/kalkulasi statistik vendor sebelum SOP owner/accountant ditentukan. Konflik block dengan stay aktif tetap harus ditetapkan di D-005/reservation spec.
- Test matrix wajib untuk T02b2/T04b2: transisi sah satu per satu; `dirty` → `inspected` ditolak tanpa event; dua perubahan bersamaan tidak kehilangan event; rentang satu hari/inclusive boundaries; tanggal invalid ditolak; overlap dan pembatalan satu block masih menutup saat block lain aktif; semua block dibatalkan membuka kembali tanggal; event/block tidak dapat dihapus atau diedit in-place; semua query tetap scope property. Uji concurrency perlu dijalankan pada DB target, bukan disimpulkan dari SQLite.
- Revisi: pemilik/property SOP dapat mengganti vocabulary, status awal, syarat inspeksi, reason taxonomy, tanggal/hotel timezone, block-vs-reservation conflict dan efek laporan sebelum UI availability digunakan. Default `dirty` adalah deny-safe, tetapi biaya operasionalnya adalah kamar baru tidak dianggap bersih tanpa action.

### Sumber eksternal PROP-D3

- [1] Oracle OPERA Cloud, *Using the Housekeeping Board*: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.2/ocsuh/t_housekeeping_using_the_housekeeping_board.htm
- [2] Oracle OPERA 5, *Out of Order/Service*: https://docs.oracle.com/cd/E53547_01/opera_5_04_03_core_help/out_of_order_service_hsk_ooo.htm

Dokumentasi vendor dipakai sebagai pola pembanding, bukan persetujuan SOP Hotel Bome.

## IAM-D1 — Локальные роли и approval аккаунтов

- Статус: временный консервативный default по делегированию владельца; не заменяет подтверждение гостиничных SOP.
- Источники: решение владельца от 2026-10-17, зафиксированное в `docs/AI_WORKFLOW_STATE.md` (несколько отелей; self-registration только pending; однократное локальное создание первого admin); требования IAM-001 в `specs/003-identity-property-access/spec.md`.
- Роли: `platform_admin` управляет установкой и назначением отелей; `hotel_admin` управляет доступом и настройками назначенных отелей; `front_desk`, `housekeeping`, `finance`, `operations` — именованные минимальные профили для последующего permission mapping. Неиспользуемые модули недоступны; deny-by-default; только platform_admin может назначать hotel_admin/platform_admin. Пользователь без активного membership не получает доступ к отелю.
- Переходы: регистрация -> pending; admin approve -> active только вместе с назначением минимум одного отеля и роли; reject -> rejected без входа; active -> suspended/revoked немедленно запрещает новые запросы и завершает/инвалидирует сессии; повторная отправка регистрации не меняет статус; повторное решение требует явного admin action и аудита.
- Одобрение: только текущий platform_admin либо hotel_admin, имеющий полномочие управления пользователями; hotel_admin может назначить пользователя только в собственные отели и не может повысить до platform_admin/hotel_admin. Самоодобрение и self-escalation запрещены. Все изменения записываются в audit trail с actor, target, scope, временем и причиной, без секретов.
- Риск/default: конкретные права профилей и право межотельного администрирования требуют гостиничного SOP. До отдельного утверждения ограничивать права, не делать role permissions предположением для уже не реализованных модулей. Данное решение разрешает только спецификацию IAM transition/schema slice.
- Проверка: новые permission mapping требуют тестов каждого разрешённого и запрещённого действия, изоляции отеля и отзыва сессии. Ручной ввод матрицы остается конфигурируемым будущим шагом; production rollout не утвержден.

## IAM-D2 — Platform-admin approval foundation

- Status: temporary build decision under the autonomous branch mandate; not final hotel SOP, role permission approval, or production rollout approval.
- Scope: a platform admin may approve a pending account only with one active property, one named profile from IAM-D1, and a reason; rejection requires a reason. Approval/rejection status, property/profile (when applicable), actor, target, timestamp, and reason are committed atomically to immutable `user_access_events`. Fortify's replacement registration POST is capped at five requests per minute per IP; account creation explicitly persists `pending` status.
- Authorization: only current active platform admins can open/manage the approval queue. Self-approval and non-pending transitions are denied. Existing property/inventory routes still require `platform-admin`; having a `property_user` row does not grant property access or permissions to `hotel_admin`/staff profiles.
- Hotel-admin approval delegation and cross-property data access remain denied until exact action/property matrix is implemented and tested. Membership editing and account suspend/reactivation have separate platform-admin-only temporary decisions in IAM-D4/IAM-D5.
- Rationale: registration already leaves users pending and unauthenticated; approval without a property/profile is unsafe. Requiring a reason and immutable audit gives traceability. Keeping the existing global gate avoids accidental privilege expansion while membership policy remains unfinished.
- Validation: SQLite feature tests cover admin authorization, approval/rejection, property/profile/reason validation, self-escalation denial, immutable audit and no implicit property access. MySQL transactions/trigger and concurrency are unverified; production use is blocked until target-DB testing and IAM acceptance complete.

## IAM-D3 — Housekeeping-only assigned property board

- Status: temporary least-privilege implementation default under the autonomous build mandate; not hotel SOP approval.
- Exact matrix for existing module: `platform_admin` retains current property/inventory access. `housekeeping` membership may GET a read-only room/status board and PATCH housekeeping status only for its assigned active property. Deny blocks/history, room/outlet/type CRUD, property administration, account management and every other role/module action unless explicitly granted later. `hotel_admin`, `front_desk`, `finance`, and `operations` memberships grant no routes yet.
- Data scope: board query is constrained by the route property and membership on every request; no active membership or inactive property returns 404. Status writes retain existing transition validation and append-only event actor attribution. UI visibility is supplemental; middleware is authoritative.
- Navigation: non-platform-admin workspace list shows only active properties with an explicit `housekeeping` membership; other profiles see no property entries.
- Rationale: housekeeping needs room/status work but not master-data, room-block, or account administration. Reusing the full admin board would leak functions beyond policy, so the staff board is separate and minimal.
- Verification: SQLite tests cover assigned/other-property direct URL, allowed status change, denied block/master paths, and scoped navigation. MySQL route/locking behavior and real hotel SOP remain unverified; do not call this production-ready.

## IAM-D4 — Platform-admin membership lifecycle (temporary technical default)

- Status: temporary least-privilege policy under the autonomous build mandate; not hotel SOP approval.
- Only `platform_admin` may add, change, or revoke property memberships. Targets for add/change must be active non-platform-admin users and target properties must be active. Revoke remains available for inactive properties, non-active users, and legacy memberships on other platform-admin users to clean up access; it never removes global platform-admin status. Self-target membership changes and delegation to other profiles are denied.
- Every actual add/change/revoke requires a nonblank reason (max 500 chars) and writes an append-only audit event with actor, user, property, previous role, new role, event type, and time in the same transaction as membership mutation. Same-role re-submit is idempotent and creates no audit event.
- Property authorization is checked against current membership on each protected request. Add/change/revoke also increments the target account's `auth_version`, so previous web sessions fail on their next protected request across supported Laravel session drivers; physical session records/cookies are not deleted. Same-role resubmission does not increment it.
- Other role profiles receive no new capabilities. Membership assignment does not grant anything beyond the existing exact `property-role` route map. SQLite tests are local evidence only; MySQL concurrency/trigger verification remains open.

## IAM-D5 — Account suspension, reactivation, and session invalidation (temporary technical default)

- Status: bounded implementation default under the autonomous build mandate; not hotel HR/SOP approval.
- Only active `platform_admin` may suspend or reactivate another non-platform-admin account. Self-management, platform-admin targets, and other account states are denied. Suspension sets account status to `inactive`; existing property memberships remain for audit/possible return. Reactivation changes status to `active` but does not restore existing sessions. A platform admin separately reviews/removes property memberships when appropriate.
- Every state transition requires a nonblank reason (max 500 chars) and writes immutable `user_account_events` in the same transaction. Suspension increments account `auth_version` and rotates `remember_token`; property membership add/change/revoke also increments `auth_version` and rotates `remember_token`. Rotation revokes outstanding Laravel remember-me cookies atomically with the change. Login stores current version in session; active middleware rejects authenticated sessions missing/stale snapshot on the next protected request. Server-side session records are not physically deleted; issued remember cookies are invalidated by token rotation. Users must authenticate again after reactivation.
- Authorization, status, version and audit are locked/updated atomically where supported by DB transactions. Tests currently use SQLite only; MySQL row-lock, trigger and Windows runtime acceptance remain unverified. A successful re-login after reactivation restores access only if memberships remain valid.

## OPS-D4 — Windows-only operating-system family

- Status: owner-confirmed OS family selection; exact support baseline and deployment approval remain open.
- Decision: build and validate only for Windows targets. This does not choose Windows release/edition, architecture, PHP version, database engine, service wrapper, signer custody or pilot device.
- Remaining work: choose supported Windows versions/editions and runtime/service wrapper; inventory PHP extensions; test web routing, DB, assets/fonts, filesystem permissions, service start/restart, offline installation, and firewall on a disposable pilot. Signer and pilot machine remain external dependencies.
- Current Linux checks are source/test evidence only and are not Windows compatibility evidence.

## OPS-D1 — Безопасный локальный bootstrap первого администратора

- Статус: временный технический default для реализации и тестирования на локальном окружении; не подтверждает Windows deployment.
- Источники: решение владельца о локальном одноразовом wizard и ожидания OPS-001 FR-003/SC-002 в `specs/002-offline-runtime-bootstrap/spec.md`; Laravel 13 transaction/pessimistic-locking API для сериализации bootstrap.
- Правило: wizard GET/POST допускаются только при TCP peer loopback (`127.0.0.1`/`::1`), не используют forwarded headers. Endpoint отвечает 404 при удаленном peer или после завершения.
- Aturan: row singleton bootstrap menserialisasi POST dalam transaksi; hanya input valid membuat user berstatus active dan `is_platform_admin=true`, lalu status selesai dicatat bersama user. Kegagalan transaksi tidak menghabiskan wizard. Wizard tidak membuat sesi login; admin harus login normal.
- Batasan/revisi: `is_platform_admin` adalah kolom transisi, bukan permission engine. Ganti dengan role/membership ter-normalisasi dalam IAM-001 sebelum persetujuan hotel/properti. Peer loopback bergantung proxy tidak memalsukan `REMOTE_ADDR`; jangan percaya `X-Forwarded-For`. Konfigurasi installer dan uji Windows/LAN tetap harus dilakukan pada pilot.
- Alasan: akses first-admin melalui LAN membuka jalan eskalasi sebelum admin ada; transaksi dan peer socket address menjaga satu kali dan mengabaikan header klien.
- Opsi revisi: bootstrap code lokal berjangka/PIN fisik atau provisioning installer terverifikasi dapat mengganti loopback-only setelah desain ancaman, operator workflow, dan pilot disetujui.

## OPS-D2 — Health status minimum tanpa rahasia

- Status: default sementara, hanya untuk kesiapan runtime lokal.
- Sumber: OPS-001 FR-004 dan AC03 dalam `specs/002-offline-runtime-bootstrap/spec.md`; Laravel route/middleware auth; tes fake DB lokal.
- Keputusan: tampilkan hanya application, database, storage, dan schema sebagai `ok`/`unavailable`; return 503 bila dependency wajib gagal. Lindungi endpoint memakai login, status aktif, dan platform-admin. Jangan sertakan exception/config/credential.
- Alasan: operator perlu tahu service apa gagal tanpa mengekspos konfigurasi pada LAN atau log HTTP.
- Batas: restart, service manager, storage yang benar-benar read-only, dan lintas OS belum diuji; memerlukan pilot Windows. Tidak menggantikan health probe internal service manager.
- Revisi bila: kebutuhan monitoring service manager terbukti; definisikan endpoint internal terpisah yang hanya bind loopback, tetap tanpa secret.

## OPS-D4 — Baseline deployment Windows untuk pilot (sementara)

- Status: keputusan teknis sementara untuk mengarahkan prototype; bukan bukti kompatibilitas, persetujuan rilis, atau persetujuan security owner.
- Sumber: riset Microsoft lifecycle/release health diakses 2026-10-23: https://learn.microsoft.com/en-us/windows/release-health/release-information ; https://learn.microsoft.com/en-us/windows/release-health/windows11-release-information ; https://learn.microsoft.com/en-us/windows/whats-new/windows-11-prepare ; dokumentasi IIS PHP FastCGI: https://learn.microsoft.com/en-us/iis/application-frameworks/install-and-configure-php-on-iis/how-to-install-the-microsoft-web-platform-for-php .
- Keputusan sementara: target pilot baru memakai Windows 11 Pro pada General Availability Channel (GAC), release supported/current yang tersedia di image pilot; gunakan Enterprise bila organisasi perlu pengelolaan terpusat atau entitlement mengharuskan. Jangan jadikan Windows 10 baseline deployment baru; Windows 10 22H2 berakhir dukungan reguler 2025-10-14. ESU hanya jalur transisi untuk PC yang terdaftar, bukan default.
- Update: annual feature release + monthly cumulative updates; gunakan staged rings dan safeguard holds. Set target product/version secara eksplisit dan uji terlebih dulu pada pilot; jangan gunakan Insider prerelease. Nilai jadwal/deferral ditentukan setelah observasi jam operasional pilot.
- PHP di IIS/FastCGI merupakan opsi teknis, bukan keputusan server/service wrapper. Runtime Laravel, PHP, database, service manager, backup/restore, peripheral, offline behavior dan rollback tetap belum diterima sampai diuji di mesin pilot.
- Pilot wajib: hardware eligibility/TPM/Secure Boot dan lisensi; install persis stack; start/restart/reboot; update ring dan rollback; LAN dan WAN mati; database backup/restore; printer/scanner/peripheral terpilih; login/RBAC; service health/log tanpa secret.
- Signing custody: jangan menamai custodian tanpa penunjukan organisasi. Default kontrol yang direkomendasikan untuk dibahas: private key di HSM/key vault atau protected signing service (bukan repo/PFX di workstation); least-privilege access, approval dua pihak untuk production release, audit penggunaan, recovery/rotation/revocation plan. Rujukan Microsoft: https://learn.microsoft.com/en-us/windows/apps/dev-tools/winapp-cli/security dan https://learn.microsoft.com/en-us/security/zero-trust/sfi/secure-future-initiative-adoption . Pemilik harus menunjuk custodian dan approver sebelum release signing.
- Runtime decision: IIS/FastCGI hanyalah opsi yang terdokumentasi untuk PHP di IIS, belum membuktikan kompatibilitas/runtime service Laravel 13. Seleksi runtime/service wrapper tetap menunggu prototype dengan stack terkunci.
- Sumber lifecycle diperiksa 2026-10-23 menunjukkan Windows 11 26H2 GAC tersedia; tabel lifecycle memuat dukungan Pro sampai 2028-10-10 dan Enterprise sampai 2029-10-09. Usulan deployment baru “Windows 11 Pro GAC current supported release” berarti target release mengikuti GAC/pilot update policy, bukan mengunci 26H2 secara permanen.
- Perbaikan presisi: Windows 10 Home/Pro dan Enterprise/Education 22H2 mendapat final regular update 2025-10; jalur ESU dapat memberi update hanya pada PC enrolled. Jangan sebut seluruh edisi/servicing mode Windows 10 mati tanpa mengecualikan LTSC lifecycle berbeda.
- Update source pages (akses 2026-10-23) are versioned status snapshots; ulangi lifecycle check sebelum image/package release.
- Alasan: 10/11 pada pesan pemilik ditafsirkan sebagai cakupan OS desktop keluarga Windows, tetapi dukungan siklus hidup membedakan versi. Windows 11 GAC memberi baseline yang masih didukung; Windows 10 tidak dipakai untuk instalasi baru karena lifecycle reguler telah berakhir.
- Batas/revisi: dapat direvisi setelah owner menyediakan device pilot, edition entitlement, runtime/service pilihan, dan hasil acceptance. Tidak menyimpulkan bahwa Windows 11 26H2 sudah kompatibel dengan aplikasi ini hanya dari lifecycle Microsoft.

## OPS-D5 — Default SOP operasional hotel untuk spesifikasi awal (sementara)

- Status: baseline desain konservatif hasil riset produk hotel, bukan SOP universal, keputusan Hotel Bome, nasihat hukum/pajak, atau persetujuan accountant.
- Sumber: Oracle OPERA Cloud task catalogs dan workflows (retrieved 2026-10-23): https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/r_opera_tasks_bookings.htm ; https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/r_opera_tasks_inventory.htm ; https://docs.oracle.com/en/industries/hospitality/opera-cloud/24.3/ocsuh/t_housekeeping_using_the_housekeeping_board.htm ; https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.2/ocsuh/t_checking_in_reservations.htm ; https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/t_logs_viewing_changes_log_details.htm ; https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.2/ocsuh/c_postings_adjustments_postings_adjustments_and_payments_ch.htm . HTNG/AHLA voice framework notes roles/access levels and is explicitly non-prescriptive: https://www.ahla.com/sites/default/files/frameworks_and_guidelines-voice-based.pdf . Vendor capability is evidence of a common pattern, not proof of universal hotel SOP.
- Role baseline: named individual accounts, default deny, explicit property scope, least privilege. Front desk gets normal reservation/room assignment/check-in/out and minimum guest information; sensitive override, post-check-in cancellation, move across properties, refund/adjustment require separate authorization and reason. Housekeeping sees assigned room/task and limited status fields, not folio/payment or full guest profile. Finance accesses permitted folio/settlement/reconciliation but cannot alter room state or staff roles. Property manager reviews operational exceptions and limited overrides for own property. Cross-property access remains denied unless separately granted; global platform admin is installation support, not routine hotel operator.
- Reservation/room baseline: distinguish reservation lifecycle, occupancy, housekeeping cleanliness and out-of-order/service availability. Check-in requires eligible reservation and assigned room considered ready (Clean; Inspected if that hotel enables inspection). If no ready room, queue/exception; no implicit bypass. Checkout follows the hotel's settlement workflow; do not define payment/tax posting rules here. Cancellation/no-show/void/reopen are explicit audited transitions, not silent edits.
- Housekeeping baseline: checkout/dirty room requires service; transition Dirty → Clean; Inspected is optional supervisor step only if pilot SOP enables it. Pickup/partial service, cleanliness vocabulary, OOO/OOS reasons and FO-HK reconciliation require local workflow confirmation before schema/UI behavior is treated final.
- Audit baseline: record actor, timestamp, property, entity, action, relevant before/after values and reason for access/role changes, room/reservation transitions, overrides, cancellations and financial adjustments. Correct posted/important history through explicit void/reversal/adjustment, not hard delete. Retention duration, guest-data fields, consent, refund thresholds, cash variances and tax/invoice rules are explicitly not decided by this entry.
- Revisi: convert only bounded, testable defaults into feature-specific specs/tasks; require owner/property manager review before release and accountant/legal review for finance, tax, privacy and retention. This allows non-financial local product slices to proceed while disallowing permanent policy claims.

## OPS-D3 — Password lokal dan email verification opsional

- Status: sementara, dapat dibalik; mengikuti prinsip PMS local-first dan keputusan self-registration wajib menunggu persetujuan admin.
- Sumber: `docs/REBUILD_PLAN.md` akses harian tetap berjalan tanpa WAN; IAM-001 FR-001 status pending memblokir login; `app/Providers/AppServiceProvider.php` menetapkan syarat minimum password produksi; temuan review OPS-001 bahwa `Password::uncompromised()` memanggil verifier remote.
- Keputusan: enforce password minimal 12 karakter, mixed-case, huruf, angka, simbol secara lokal. Jangan panggil pemeriksaan kebocoran password online sinkron. Email verification bukan syarat dashboard/settings; akun aktif hanya setelah approval internal. Jangan kirim verifikasi email sinkron saat pendaftaran pending; verifikasi dapat dipicu terpisah setelah approval atau sebagai tindakan opsional.
- Test regresi: `RegistrationTest::test_pending_registration_does_not_send_email_synchronously` memakai Notification fake; request pendaftaran lokal sukses tanpa notifikasi.
- Alasan: registration/bootstrap tidak boleh tergantung WAN atau tertahan timeout provider; kekuatan password lokal + approval admin adalah kontrol wajib yang bisa diuji offline.
- Dampak/batas: password yang bocor di layanan lain tidak diperiksa otomatis. Dapat ditambah pemeriksaan lokal terunduh/optional async setelah desain privasi, lisensi, freshness, dan kegagalan offline dibuktikan.
- Test: `RegistrationTest::test_registration_password_policy_does_not_call_external_compromise_verifier`, `DashboardTest::test_active_unverified_users_can_use_the_dashboard_without_email_service`, serta full auth regression.

## HK-D1 — Antrean tugas housekeeping minimum (sementara)

- Status: default implementasi sementara untuk slice HK-001; bukan SOP Hotel Bome atau persetujuan owner/property manager.
- Sumber: mandate build otonom `docs/HERMES_AUTONOMOUS_BUILD.md`; prinsip least privilege/append-only pada constitution; baseline housekeeping OPS-D5 (baris sebelumnya).
- Keputusan: satu tugas mengacu ke satu kamar aktif/properti aktif; assignee opsional dan harus akun aktif dengan membership housekeeping pada properti yang sama. Lifecycle `open → in_progress → completed`; `open`/`in_progress → cancelled` membutuhkan alasan; state terminal tidak dapat diedit/hapus. Assignment/status dicatat sebagai event immutable dengan actor/timestamp. Completed task tidak mengubah housekeeping cleanliness/room availability; perubahan status kamar tetap aksi terpisah.
- Data minimum: room, instruction bebas maks 500 karakter, assignee, actor, status dan event; tidak menyimpan guest/stay/folio/PII.
- Revisi: owner/property manager meninjau lifecycle, assignment scope dan instruction sebelum pilot. MySQL locking, Windows/LAN, browser dan SOP acceptance tetap belum diuji.
- Risiko jika salah: antrean dapat berbeda dari kebiasaan lokal; data tetap dapat dikoreksi melalui task baru, tanpa mengubah riwayat.

## ENG-D1 — Work order maintenance minimum (sementara)

- Status: default implementasi sementara untuk slice ENG-001; bukan SOP Hotel Bome atau persetujuan owner/property manager.
- Keputusan: work order mandiri, room reference opsional; role `operations` pada properti aktif dapat membuat/mengelola, assignee harus akun aktif dengan membership `operations` properti yang sama. Lifecycle `open → in_progress → resolved`; `open`/`in_progress → cancelled` perlu alasan; riwayat immutable dan actor/timestamp tercatat.
- Batas: tidak mengelola asset/costing, preventive schedule, pembelian, financial posting, guest data, atau room availability/status. Resolution tidak otomatis menjadikan kamar tersedia/bersih.
- Revisi: property manager tinjau sebelum pilot. MySQL concurrency dan target deployment belum diverifikasi.
- Risiko jika salah: workflow dapat berbeda dari SOP aktual; koreksi dilakukan sebagai work order baru tanpa menghapus riwayat.
