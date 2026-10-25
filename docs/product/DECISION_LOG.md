# Журнал решений

## GUEST-D7 — Batas teknis sementara untuk profil tamu (bukan persetujuan hukum)

- Status: keputusan teknis sementara di bawah mandat build otonom; D-007 tetap terbuka dan GUEST-001 implementasi PII tetap diblokir sampai owner/privacy counsel menentukan dasar pemrosesan, tujuan, notice/consent bila berlaku, retensi per kategori, hak akses/permintaan, pengungkapan, dan penghapusan.
- Sumber primer: UU RI No. 27 Tahun 2022 tentang Pelindungan Data Pribadi, JDIH BPK (diakses 2026-10-23), khususnya Pasal 4 (kategori data), Pasal 5–13 (hak subjek), Pasal 16(2)(a–h) (pengumpulan terbatas/spesifik, tujuan, hak, keamanan, transparansi, retensi), Pasal 20 (dasar pemrosesan), Pasal 43–44 (penghapusan/pemusnahan). Teks resmi: https://peraturan.bpk.go.id/Download/224884/UU%20Nomor%2027%20Tahun%202022.pdf ; status metadata BPK: https://peraturan.bpk.go.id/Details/229798/uuno-27-tahun-2022 . Ini ringkasan sumber, bukan nasihat hukum atau penetapan kewajiban spesifik hotel.
- Rekomendasi teknis sementara: jangan implementasikan guest document upload, salinan identitas, nomor identitas, DOB, data kesehatan/keuangan, consent capture/legal-basis selector, export PII, atau sync PII. Pada saat baseline audit belum ada guest processing; prototype profil dibuat kemudian pada 2026-10-25 dan tetap belum lolos acceptance D-007.
- Bila nanti field minimum disetujui: setiap field harus punya tujuan dan dasar pemrosesan tercatat; nama tampilan saja tidak cukup untuk mengklaim anonymous. Gunakan property-scoped CRUD, deny-by-default, audit akses/perubahan, redaksi log, tidak mengirim data keluar instalasi, dan reversible merge review. Hard delete/pemusnahan hanya melalui prosedur terotorisasi setelah pemeriksaan kewajiban arsip/hukum/riwayat transaksi; jangan membuat retention duration default tanpa keputusan.
- Task teknis yang bisa disiapkan tanpa guest data: inventory processing/data flows dan threat/retention matrix sebagai artefak desain; acceptance: tidak menambah schema/route/PII, setiap proposed field punya purpose/basis/recipient/retention/role/deletion handling, dan semua TBD ditandai BLOCKED_EXTERNAL. Implementasi profile baru setelah review owner/privacy counsel.
- Revisi: pemilik/privacy counsel menetapkan purpose, dasar, field, retensi, hak akses/permintaan, kebutuhan pencatatan tamu dan aturan penghapusan; akuntan/legal menentukan konflik dengan dokumen bisnis/arsip. Rekomendasi ini bukan approval D-007.
- Koreksi riset 2026-10-25: pembaruan sebelumnya keliru menyebut `display_name` dan `notes` sebagai non-PII. Pasal 1 UU 27/2022 mendefinisikan Data Pribadi berdasarkan keterkaitan dengan orang yang teridentifikasi/dapat diidentifikasi; Pasal 4 ayat (3) secara eksplisit memasukkan nama lengkap sebagai data pribadi umum. Nama tampilan dapat berisi nama yang mengidentifikasi orang, dan catatan bebas dapat berisi informasi identitas. Karena itu prototype `guests` adalah pemrosesan yang mungkin mencakup Data Pribadi, bukan fitur non-PII yang disetujui.
- Status pasca-koreksi: kode T01a tetap ada sebagai prototype teknis dengan akses platform-admin dan data pengujian palsu saja. D-007 dan acceptance guest tetap BLOCKED_EXTERNAL; jangan masukkan data tamu nyata, jangan perluas field/role/export/sync, dan jangan menyatakan profile siap operasional sebelum owner/privacy counsel menyepakati tujuan, dasar, notice/consent bila berlaku, hak subjek, retensi, akses, dan penghapusan.
- Sumber primer untuk koreksi: UU RI No. 27 Tahun 2022, Pasal 1 dan Pasal 4 ayat (3), JDIH BPK (diakses 2026-10-25): https://peraturan.bpk.go.id/Download/224884/UU%20Nomor%2027%20Tahun%202022.pdf ; metadata/status: https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022 . Ini klasifikasi awal berdasarkan teks resmi, bukan nasihat hukum atau persetujuan privacy counsel.

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

## D-004 — Keputusan sementara mata uang, pajak, dan pembulatan (sementara)

- Status: keputusan teknis sementara untuk membuka implementasi lokal; bukan penetapan pajak final atau persetujuan akuntan.
- Sumber primer: UU No. 1 Tahun 2022 tentang HKPD, khususnya Pasal 4, 50–51, 58, JDIH BPK: https://peraturan.bpk.go.id/Details/195696/uu-no-1-tahun-2022 ; PP No. 35 Tahun 2023 tentang Ketentuan Umum Pajak Daerah dan Retribusi Daerah: https://peraturan.bpk.go.id/Details/252130/pp-no-35-tahun-2023 ; UU No. 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan, sebagaimana diubah UU No. 6 Tahun 2023: https://peraturan.bpk.go.id/Details/185162/uu-no-7-tahun-2021 ; PMK No. 70/PMK.03/2022 tentang jasa yang tidak dikenai PPN: https://jdih.kemenkeu.go.id/dok/pmk-70-pmk-03-2022 ; contoh Perda DKI Jakarta No. 1 Tahun 2024: https://peraturan.bpk.go.id/Details/279709/perda-prov-dki-jakarta-no-1-tahun-2024 . Tarif aktual bergantung Perda/Perkada lokasi hotel; contoh daerah bukan tarif nasional.
- Keputusan sementara:
  - Mata uang operasional: IDR, simpan seluruh nilai uang sebagai bilangan rupiah bulat; tanpa pecahan desimal. Format tampilan dapat memakai pemisah ribuan Indonesia, misalnya `Rp1.250.000`.
  - PBJT: sebut **PBJT atas Jasa Perhotelan** (istilah lama "Pajak Hotel"/"PB1" jangan dipakai sebagai nama pajak baru). UU HKPD menetapkan batas tarif umum PBJT paling tinggi **10%**; tarif yang berlaku ditetapkan Perda. Tarif **10%** umum dijumpai pada Perda yang diperiksa, tetapi bukan angka otomatis bagi semua properti. Untuk perhitungan, simpan tarif efektif per properti dari Perda setempat yang telah diverifikasi; jangan hard-code 10% sebagai tarif nasional.
  - Hotel/restoran: konsumsi makanan/minuman di restoran merupakan objek PBJT tersendiri; batas tarif umum juga 10%, dengan pengecualian/kriteria yang ditentukan UU dan aturan daerah. Tarif dan perlakuan restoran tidak otomatis mengikuti tarif jasa perhotelan. Jangan anggap tarif maksimum sebagai tarif aktual.
  - PPN: jasa perhotelan yang termasuk objek pajak daerah tidak dikenai PPN. Namun transaksi/komponen berbeda—misalnya sewa ruang untuk selain acara/pertemuan atau jasa lain yang disebut dalam aturan—dapat memiliki perlakuan PPN berbeda. Jangan mengenakan PPN pada jasa kamar secara otomatis; tandai komponen lain untuk klasifikasi pajak terpisah. PPh bukan pajak konsumsi yang ditambahkan otomatis ke tagihan tamu; pemotongan/pelaporannya bergantung jenis transaksi dan status pihak.
  - Pembulatan: semua nilai yang diposting ke folio, invoice, dan pembayaran adalah rupiah bulat. Hitung presisi internal tanpa pembulatan antara bila nilai pecahan muncul; bulatkan hanya pada batas posting sesuai kebijakan pembulatan yang disetujui akuntan. Untuk sementara gunakan pembulatan half-up ke rupiah terdekat pada tiap total pajak/baris yang diposting dan catat metode; jangan mengklaim metode ini sebagai ketentuan hukum.
  - Nomor invoice: tidak ditemukan format nasional tunggal yang wajib untuk invoice komersial hotel dalam sumber yang diperiksa. Pakai nomor unik, berurutan, tidak dapat dipakai ulang, berlingkup properti dan tahun/periode; pola sementara `INV-{KODE_PROPERTI}-{YYYY}-{SEKUENS}`. Simpan tanggal, properti, dan nomor sebagai identitas tetap; pembatalan/void dicatat, bukan menghapus atau memakai ulang nomor. Faktur Pajak, bila relevan, harus mengikuti ketentuan DJP terpisah.
  - Zona waktu: konfigurasi per properti dengan IANA timezone, mendukung `Asia/Jakarta` (WIB), `Asia/Makassar` (WITA), dan `Asia/Jayapura` (WIT). Simpan timestamp teknis secara konsisten dalam UTC; tanggal operasional, folio, cut-off, serta nomor periode dihitung menurut zona waktu properti.
- Batas/revisi: sebelum mengaktifkan posting pajak/invoice di properti, pemilik dan akuntan harus mengonfirmasi Perda/Perkada yang berlaku, objek dan pengecualian, tarif setiap jenis layanan, harga termasuk/tidak termasuk pajak, transaksi campuran, perlakuan PPN/PPh, dasar dan titik pembulatan, serta kebutuhan dokumen pajak. Konfirmasi pula aturan nomor invoice dan kebijakan koreksi/void lokal. Validasi timezone properti sebelum operasi; perubahan timezone tidak boleh mengubah timestamp historis. **Jangan gunakan tarif 10% sebagai default produksi tanpa verifikasi daerah.**

### Sumber eksternal D-004

- UU No. 1 Tahun 2022 tentang HKPD: https://peraturan.bpk.go.id/Details/195696/uu-no-1-tahun-2022
- PP No. 35 Tahun 2023: https://peraturan.bpk.go.id/Details/252130/pp-no-35-tahun-2023
- UU No. 7 Tahun 2021 tentang HPP: https://peraturan.bpk.go.id/Details/185162/uu-no-7-tahun-2021
- PMK No. 70/PMK.03/2022: https://jdih.kemenkeu.go.id/dok/pmk-70-pmk-03-2022
- Perda DKI Jakarta No. 1/2024 (contoh daerah): https://peraturan.bpk.go.id/Details/279709/perda-prov-dki-jakarta-no-1-tahun-2024

## D-005 — Keputusan sementara SOP reservasi dan stay (sementara)

- Status: keputusan teknis sementara; bukan SOP Hotel Bome. Default deny-safe sampai pemilik mengonfirmasi kebijakan.
- Sumber: Oracle OPERA Cloud — Reservations: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/ch_reservations.htm ; Managing Reservation Cancellation: https://docs.oracle.com/en/industries/hospitality/opera-cloud/24.1/ocsuh/t_managing_reservation_cancellation.htm ; Checking in Reservations: https://docs.oracle.com/en/industries/hospitality/opera-cloud/22.5/ocsuh/t_checking_in_reservations.htm ; Checking Out Reservations: https://docs.oracle.com/en/industries/hospitality/opera-cloud/25.3/ocsuh/t_checking_out_reservations.htm ; End of Day Reports: https://docs.oracle.com/en/industries/hospitality/opera-cloud/25.4/ocsuh/c_reports_end_of_day.htm ; Using the Housekeeping Board: https://docs.oracle.com/en/industries/hospitality/opera-cloud/23.2/ocsuh/t_housekeeping_using_the_housekeeping_board.htm .
- Keputusan sementara:
  - Status reservasi: gunakan `confirmed`, `checked_in`, `checked_out`, `cancelled`, dan `no_show` sebagai status lifecycle. `waitlisted` hanya antrean non-konfirmasi; tidak menjamin kamar atau mengurangi ketersediaan.
  - Pembatalan: izinkan pembatalan sebelum check-in. Jangan otomatis mengenakan penalti atau mengembalikan deposit; catat pembatalan dan proses refund/retensi terpisah setelah otorisasi sesuai kebijakan yang disetujui.
  - No-show: tandai setelah tanggal kedatangan berakhir dan petugas memverifikasi tamu belum check-in serta reservasi belum dibatalkan. Jangan otomatis menagih atau menahan deposit; eskalasi untuk keputusan manual.
  - Deposit: opsional sementara. Jangan mewajibkan, menetapkan jumlah/jatuh tempo, atau melakukan refund/retensi otomatis. Catat pembayaran, saldo, dan tindakan refund/retensi sebagai transaksi terpisah yang dapat diaudit.
  - Walk-in: boleh dibuat sebagai reservasi langsung, tetapi proses check-in tetap wajib melewati pemeriksaan eligibility, penetapan kamar ready, identitas, dan pencatatan pembayaran.
  - Group booking: gunakan header grup untuk pengelompokan dan reservasi kamar individual untuk setiap kamar/tamu. Jangan anggap header sebagai alokasi kamar atau pembayaran bersama tanpa aturan eksplisit.
  - Room share/move: dukung hanya melalui tautan tamu/reservasi dan penugasan kamar yang eksplisit. Catat pelaku, waktu, kamar asal/tujuan; larang perubahan yang menimbulkan okupansi atau konflik inventori.
  - Overbooking: nonaktif secara default; jangan menerima reservasi melebihi inventori tersedia.
  - Check-in: wajib ada reservasi yang eligible (termasuk walk-in yang baru dibuat), kamar tersedia dan berstatus ready/clean, serta identitas tamu dicatat. Jangan check-in ke kamar dirty, out-of-order, atau belum ditetapkan.
  - Check-out: wajib meninjau dan menyelesaikan saldo sebelum checkout; pengecualian saldo terbuka memerlukan otorisasi eksplisit. Checkout memicu status kamar vacant-dirty atau tugas housekeeping, bukan ready otomatis.
- Batas/revisi: belum ada keputusan Hotel Bome tentang jam batas dan prosedur Night Audit, penalti no-show/pembatalan, deposit dan metode refund, identitas yang diwajibkan, siapa berwenang menyetujui pengecualian, aturan grup/share/move, atau settlement saldo terbuka. Konfirmasi pemilik diperlukan sebelum kebijakan tersebut diaktifkan. Default ini bukan klaim kewajiban hukum lokal.

### Sumber eksternal D-005

- Oracle OPERA Cloud Reservations: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/ch_reservations.htm
- Managing Cancellation: https://docs.oracle.com/en/industries/hospitality/opera-cloud/24.1/ocsuh/t_managing_reservation_cancellation.htm
- Checking In: https://docs.oracle.com/en/industries/hospitality/opera-cloud/22.5/ocsuh/t_checking_in_reservations.htm
- Checking Out: https://docs.oracle.com/en/industries/hospitality/opera-cloud/25.3/ocsuh/t_checking_out_reservations.htm
- End of Day Reports: https://docs.oracle.com/en/industries/hospitality/opera-cloud/25.4/ocsuh/c_reports_end_of_day.htm

## D-010 — Keputusan sementara finance controls, cashier, dan night audit (sementara)

- Status: keputusan teknis sementara; konservatif sampai kebijakan pemilik dikonfirmasi.
- Sumber: Oracle OPERA Cloud — End of Day controls: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.2/ocsuh/c_opera_controls_end_of_day.htm ; Closing Cashiers: https://docs.oracle.com/en/industries/hospitality/opera-cloud/24.4/ocsuh/t_cashiers_closure.htm ; Income Audit: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/c_endofday_income_audit.htm ; Charges Adjustment and Payments: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.4/ocsuh/c_postings_adjustments_postings_adjustments_and_payments_ch.htm ; Oracle OPERA 5 Close Cashier: https://docs.oracle.com/cd/E53547_01/opera_5_04_03_core_help/close_cashier.htm .
- Keputusan sementara:
  - **Business date:** tanggal operasional properti yang dipakai untuk posting dan pelaporan; tidak otomatis disamakan dengan tanggal kalender. Night audit memajukan business date. Zona waktu properti dan waktu mulai audit harus mengikuti konfigurasi properti; nilainya belum ditetapkan.
  - **Cashier shift:** cashier harus dibuka sebelum transaksi kasir. Saat tutup, sistem merangkum transaksi per cashier dan tender, mencatat saldo awal, penerimaan, pengeluaran, jumlah aktual, dan selisih. Tutup memerlukan rekonsiliasi serta alasan selisih jika tidak seimbang. Laporan penutupan disimpan.
  - **Tender:** catat cash, card, transfer, dan deposit sebagai jenis pembayaran terpisah. Deposit bukan pendapatan kamar; tampilkan sebagai transaksi tersendiri. Rekonsiliasi per shift harus memenuhi total tercatat per tender = total aktual/settlement yang dilaporkan; selisih tidak boleh disamarkan atau dihapus.
  - **Night audit:** jalankan sebagai proses end-of-day terkontrol untuk business date aktif. Validasi transaksi dan status operasional dahulu; posting room charge dan pajak sesuai aturan tarif, transaksi, dan konfigurasi pajak yang berlaku. Simpan hasil proses dan laporan. Kegagalan atau ketidaksesuaian menghentikan penutupan untuk ditinjau, bukan dilewati otomatis.
  - **Close/reopen:** penutupan business date mengunci tanggal itu dari perubahan rutin. Reopen hanya oleh supervisor berwenang, dengan alasan dan jejak audit; tidak mengubah atau menghapus riwayat posting. Jika Income Audit kelak dipakai, tanggal yang masih terbuka hanya dapat diselesaikan melalui alur audit dan urutan tanggal yang benar.
  - **Adjustment/void:** jangan menghapus transaksi posted. Koreksi melalui reversal, refund, credit, atau adjustment baru yang merujuk transaksi asal, dengan alasan, pengguna, waktu, dan nilai tercatat. Pembatalan folio mengikuti alur void yang menjaga jejak dokumen.
  - **Approval limits:** siapkan kontrol persetujuan berbasis peran untuk refund, void, dan discount; tindakan berisiko tinggi memerlukan persetujuan supervisor. Jangan tetapkan nominal atau persentase ambang sebelum pemilik menyetujuinya.
- Batas/revisi: Oracle OPERA dirujuk sebagai pola produk, bukan bukti bahwa setiap kontrol cocok untuk hotel ini. Konfirmasi pemilik diperlukan untuk cutoff business date, jadwal dan urutan night audit, aturan pajak/room charge, toleransi selisih, kewenangan supervisor, ambang approval, serta perlakuan transfer dan deposit. Belum ada ambang angka atau aturan pajak lokal yang ditetapkan.

### Sumber eksternal D-010

- Oracle OPERA Cloud End of Day controls: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.2/ocsuh/c_opera_controls_end_of_day.htm
- Closing Cashiers: https://docs.oracle.com/en/industries/hospitality/opera-cloud/24.4/ocsuh/t_cashiers_closure.htm
- Income Audit: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.3/ocsuh/c_endofday_income_audit.htm
- Charges Adjustment and Payments: https://docs.oracle.com/en/industries/hospitality/opera-cloud/26.4/ocsuh/c_postings_adjustments_postings_adjustments_and_payments_ch.htm

## Owner clarifications — 2026-10-25

Keputusan berikut merekam arahan produk pemilik. Default industri yang disebut tetap sementara dan dapat direvisi setelah uji konsumen. Catatan ini bukan pendapat hukum, persetujuan pajak, sertifikasi lisensi, atau bukti acceptance perangkat.

### D-007 — Data tamu

- Pemilik menyatakan PMS dipakai hotel yang ditanganinya untuk data tamu nyata, tidak untuk disebarkan, dengan akses operasional yang dimaksudkan bagi resepsionis, supervisor, dan owner.
- Kebijakan produk yang diminta: data disimpan tanpa batas waktu sebagai default bisnis; penghapusan tidak boleh dilakukan sepihak dan memerlukan persetujuan owner.
- Implementasi wajib menerapkan akses minimum per properti dan per peran, audit akses/perubahan/persetujuan penghapusan, pencatatan tujuan minimum, pengamanan lokal, redaksi log, dan larangan ekspor/sinkronisasi tanpa persetujuan khusus. Penghapusan tidak boleh berupa operasi massal/hard-delete oleh staff; permintaan koreksi/penghapusan, kewajiban retensi, sengketa, dan pemusnahan harus memiliki alur tertinjau dan jejak audit.
- **Status:** keputusan pemilik membuka perancangan dan implementasi terbatas atas kebutuhan operasional; bukan bukti bahwa retensi tanpa batas atau pemrosesan data nyata memenuhi hukum. Jangan masukkan data tamu nyata ke lingkungan uji/repo; validasi hukum/privasi dan keputusan retensi per kategori tetap diperlukan sebelum pilot konsumen.

### D-004 — Mata uang, pajak, pembulatan, timezone, invoice

- Mata uang operasional: IDR. Tarif pajak dan metode pembulatan harus dapat diatur manual pada konfigurasi properti, bukan dianggap sama untuk semua hotel; supervisor menjadi penanggung jawab validasi konfigurasi. Sampai konfigurasi disahkan, sistem tidak boleh mengarang tarif pajak.
- Zona waktu properti: `Asia/Singapore` (UTC+08:00) sebagai default yang diminta. Simpan timestamp teknis dengan konvensi konsisten dan hitung business date menurut timezone properti.
- Invoice mengikuti praktik invoice hotel umum sebagai baseline desain: identitas properti/invoice, tanggal, referensi stay, rincian layanan, subtotal, pajak/biaya yang terkonfigurasi, total, pembayaran dan saldo. Nomor invoice harus unik serta tidak dipakai ulang; persyaratan pajak lokal/faktur pajak perlu divalidasi per lokasi.
- **Status:** arahan pemilik tercatat; D-004 operasional tetap provisional sampai supervisor mengonfirmasi tarif/perlakuan pajak dan pembulatan untuk tiap properti. Tidak ada tarif nasional atau format invoice legal yang diasumsikan di sini.

### D-005 — Reservasi dan front desk

- Pemilik meminta alur PMS hotel umum sebagai default, tanpa menunggu keputusan rinci untuk tiap perilaku. Gunakan OPS-D5 dan D-005 baseline di atas sebagai aturan implementasi sementara: reservasi terkonfirmasi, walk-in, group berisi reservasi individual, overbooking mati secara default, check-in ke kamar eligible dan bersih/ready, semua perpindahan/pembatalan/no-show diaudit, dan checkout meninjau saldo.
- Penalti, penahanan/refund deposit otomatis, checkout dengan saldo terbuka, identitas wajib, serta override tetap memerlukan otorisasi eksplisit; jangan mengarang biaya/ambang.
- **Status:** cukup untuk melanjutkan slice yang sudah memiliki spec/acceptance; default harus diberi provenance dan bisa direvisi setelah uji konsumen.

### D-010 — Kasir dan tutup hari

- Resepsionis boleh menutup shift kasirnya sendiri dan bertanggung jawab memasukkan jumlah aktual serta selisih. Supervisor bertanggung jawab melakukan validasi/approval atas selisih dan tindakan koreksi berisiko. Terapkan jejak audit; jangan menghapus selisih atau mengubah transaksi posted.
- Rekonsiliasi dan night audit mengikuti pola PMS hotel umum dengan business date per properti, ringkasan per tender, exception review, dan close/reopen terotorisasi. Nilai toleransi selisih dan cutoff tetap dapat dikonfigurasi setelah kebutuhan pilot; jangan hard-code ambang yang belum diputuskan.
- **Status:** role intent dari pemilik tercatat; pemetaan final ke role aplikasi dan acceptance supervisor perlu diuji.

### D-006 — Doorlock/RFID

- Pemilik menetapkan kebutuhan RFID, penggunaan oleh resepsionis dengan tap kartu, serta target kompatibilitas Windows 10/11; model reader/lock belum diketahui.
- **Status BLOCKED_EXTERNAL:** identitas perangkat, protokol/kartu, lisensi dan hak penggunaan SDK, serta reader fisik belum dipastikan. Paket LockSDK lokal dan simulator tidak membuktikan dukungan. Lanjutkan persiapan yang tidak mengakses kartu nyata; jangan menulis/membatalkan kartu produksi atau mengklaim plug-and-play sebelum perangkat uji dipilih dan acceptance lulus.

### D-008 — Release/signing

- Owner menjadi pihak bertanggung jawab atas keputusan rilis; pemilik menyatakan belum ada kebijakan signing/entitlement khusus.
- Default keselamatan teknis: private signing key tidak boleh berada di repo/aplikasi/log, gunakan penyimpanan terlindungi dan akses owner; signing/rilis produksi memerlukan jejak persetujuan dan recovery plan. Ini rekomendasi kontrol teknis, bukan aturan legal atau persetujuan distribusi lisensi pihak ketiga.
- **Status BLOCKED_EXTERNAL:** hak redistribusi Flux/SDK dan entitlement belum diverifikasi; belum ada signing custodian/recovery plan formal. Jangan membuat atau mengirim paket produksi bertanda tangan sampai gate ini selesai.

### D-009 — Provider eksternal

- Pemilik meminta opsi provider yang paling mudah sebagai langkah awal; belum menunjuk provider, akun sandbox, atau kontrak.
- **Status:** riset/komparasi provider dan sandbox dapat dilanjutkan sebagai task independen. Integrasi tetap optional dan disabled-by-default; jangan mengirim transaksi/data nyata atau mengklaim integrasi provider selesai sebelum provider dipilih dan kontrak/sandbox disetujui.
