# Rencana Induk Build PMS Hotel Bome

Status: baseline perencanaan operasional; implementasi PMS belum dinyatakan selesai. Tanggal audit: 2026-10-23. Branch saat audit: `work/hermes-full-build`, HEAD `9fb3239`; perubahan lokal banyak dan wajib dipertahankan. Dokumen ini sumber tunggal urutan resume. `docs/AI_WORKFLOW_STATE.md` hanya menunjuk task aktif/berikutnya dan bukti checkpoint. Feature `tasks.md` memuat detail teknis, bukan pemilih task global.

## Cara resume (wajib)

1. Baca `AGENTS.md`, `docs/HERMES_AUTONOMOUS_BUILD.md`, `docs/AI_WORKFLOW.md`, state ini, constitution; cek branch/HEAD/status tanpa menyentuh source.
2. Baca baris checkpoint di `docs/AI_WORKFLOW_STATE.md`, lalu task `MBP-*` pertama berstatus READY di bagian Queue, urut ID; jika task menyebut feature task, baca spec/plan/tasks/acceptance terkait.
3. Status task: `READY`, `IN_PROGRESS`, `DONE` (wajib bukti), `BLOCKED:<ID>` (alasan + pemilik/aksi), `DEFERRED`. Jangan pilih task implementasi kode saat permintaan hanya dokumen. Saat user berkata “lanjutkan”, kerjakan task yang ditunjuk state. Task code baru boleh dipilih setelah audit plan ini selesai dan user memberi mandat implementasi.
4. Sebelum mengedit, catat status, file sasaran, dependency dan syarat penerimaan; lindungi semua perubahan yang telah ada. Perubahan terbatas ke path yang disebut task.
5. Setelahnya, update task plan dan state: status, file yang berubah, command/exit code/count, bukti, keterbatasan, blocker, task berikutnya. Jangan tandai DONE dengan klaim tanpa output.
6. Jika sesi berhenti, task `IN_PROGRESS` tetap task resume; catat sublangkah dan kondisi file, jangan mulai ulang audit. Tidak ada scheduler baru, commit, push, atau perubahan live tanpa izin spesifik.

## Status bukti produk

`EXISTS/PARTIAL` berarti kode/artifact lokal teramati, bukan acceptance produk. `NOT IMPLEMENTED` berarti backlog terencana, bukan fitur tersembunyi. `BLOCKED` wajib rujuk keputusan/akses. Laporan lama di state memberi bukti test historis; angka itu bukan hasil audit ulang sesi ini. Inventaris read-only sesi 2026-10-23 memeriksa seluruh 13 `specs/*/spec.md` yang ditemukan, semua feature tasks 001–013, `routes/web.php`, `routes/settings.php`, daftar Blade views, register/map/roadmap. Bukti route yang dihitung: definisi eksplisit di file-file tersebut; auth scaffolding/route provider dan Livewire dynamic actions tidak seluruhnya terwakili oleh literal route. View inventory tidak otomatis berarti halaman reachable. Status akses/tenant tetap PARTIAL atau UNKNOWN sampai T04 dan pengujian menyeluruh.

| Modul | Status repo terbukti dari register/spec/state | Cakupan yang belum dibuktikan |
|---|---|---|
| Runtime, setup, health | PARTIAL: first-admin/health Linux SQLite tests | Installer/service Windows, LAN/firewall, reboot, recovery, signing |
| IAM, akun, membership | App-defined route/query/role coverage and operator guidance verified on SQLite | MySQL/concurrency, Windows/browser and owner SOP acceptance |
| Property/outlet/room | PARTIAL: master CRUD, status/block history | target DB race, tenant-path lengkap, browser/pilot |
| Audit | PARTIAL: read-only viewer lima sumber | taxonomy/retensi, finance events, skala dan policy |
| Housekeeping | PARTIAL: queue/status minimum | SOP owner, assignment rules/pilot/MySQL |
| Engineering | PARTIAL: work orders dan asset CRUD | SOP, preventive maintenance/cost, MySQL/pilot |
| Guest/privacy | NOT IMPLEMENTED; D-007 terbuka | field/consent/retention/document/export policy |
| Rates/availability/reservation/front desk | NOT IMPLEMENTED; specs ada, tugas menunggu decisions | tax/currency, lifecycle, concurrency dan E2E |
| Folio/payment/cashier/night audit | NOT IMPLEMENTED | tax/invoice/tender/refund/close approval |
| Inventory/procurement/minibar | NOT IMPLEMENTED | valuation, unit, approvals, receiving SOP |
| Event/catering, POS/room service | NOT IMPLEMENTED | commercial workflow, tender, reconciliation |
| Reports/exports | NOT IMPLEMENTED selain audit read view | definitions, permissions, reconciliation, export controls |
| Backup/restore/update/release | NOT IMPLEMENTED/UNKNOWN; plan only | keys, target, restore drill, signer/runtime |
| Accounting | NOT IMPLEMENTED | accountant signoff, chart/journal/legal requirements |
| RFID/doorlock | NOT IMPLEMENTED | hardware, SDK/license, security contract, physical tests |
| Sync/OTA/payment/guest connectors | NOT IMPLEMENTED | privacy, providers, contracts, conflict policy |
| UI navigation | PARTIAL: responsive property, IAM, housekeeping, audit, dashboard changes in state | full route/navigation inventory, accessibility/browser/device acceptance |

## Roadmap fase dan queue master

Task master berukuran satu artefak/keputusan atau satu bounded audit. Setiap fase selanjutnya harus memiliki task implementasi feature-level di `specs/*/tasks.md`; task tabel ini tidak menggantikan test plan tersebut. Sasaran file dapat diperluas setelah inspeksi kode pada task implementasi.

| Fase | Master task(s) | Hasil / dependency / status |
|---|---|---|
| 0. Reconcile baseline | MBP-001 | Validasi plan vs PRD, domain, decisions, DoD; DONE (checkpoint sebelumnya). |
| 1. Cakupan modul & halaman | MBP-002 | Matriks semua 24 register entry, route/page journey, bukti/gap dan batas inventaris; DONE sesi ini. |
| 2. Detail backlog | MBP-003 | Slice per feature dengan ID, target awal, dependencies, measurable acceptance/verifikasi/risiko/status; DONE sesi ini. |
| 3. Sinkronisasi | MBP-004 | Master queue + register/map/roadmap/state disejajarkan; DONE sesi ini. |
| 4. IAM tenant audit | IAM-001-T04 → T05b → T06 | Local app-defined route/query/Volt inventory and SQLite regressions COMPLETE; operator guidance recorded. MySQL concurrency/constraints, Windows/browser and owner SOP remain external. `specs/003-identity-property-access/`; local evidence ≠ production acceptance. |
| 5. Foundation acceptance | OPS-001, PROP-001, AUD-001, HK-001, ENG-001 | Implementasi partial; MySQL/Windows/browser/SOP gates. Tidak menyatakan selesai. |
| 6. Core PMS | GUEST → RATE → RES → FO → FIN-001 → FIN-002 | Dependency serial; decisions D-004/005/007/010. Acceptance per feature. |
| 7. Operasi bisnis | INV, EVT, POS, RPT, OPS-002 | Bergantung foundation; finance/report policy dan target backup. Dikerjakan slice independen setelah readiness. |
| 7. Accounting & device | ACC, HW | External accountant/vendor/hardware/legal; adapter/simulator tidak menggantikan acceptance nyata. |
| 8. Connectors & release | SYNC, EXT, REL | Core lokal stabil dan privacy/provider/signing resolved. Disabled-by-default. |
| 9. Pilot | QA-001 | Seluruh mandatory local acceptance, Windows pilot, MySQL, restore, security, WAN-off; port 8083 tetap app lama sampai gate terpisah disetujui. |

## Registry task perencanaan yang siap

| ID | Status | File sasaran | Prasyarat | Acceptance terukur & verifikasi |
|---|---|---|---|---|
| MBP-001 | DONE | MASTER_BUILD_PLAN.md, source docs | Baca workflow + PRD/decision/roadmap | konflik status dicatat; source docs dirujuk. Bukti checkpoint sebelumnya; bukan audit ulang sesi ini. |
| MBP-002 | DONE | MASTER_BUILD_PLAN.md §§Status evidence, matrix, manual journeys | MBP-001 | 24 register entry dipetakan; operasi dan evidence/gap diisi; route/view boundary dicatat; manual journeys route/page grouped. Bukti: read-only audit seluruh 13 specs, tasks 001–013, `routes/web.php` (101 baris), `routes/settings.php`, 56 view paths, feature indexes; dynamic/framework ceiling disebut. |
| MBP-003 | DONE | MASTER_BUILD_PLAN.md §Backlog slicing | MBP-002 | Tiap feature register ID memiliki slice; IDs unik, dependencies, paths awal, measured acceptance, verifikasi, risks, status/blocker. Bukti ID/reference audit inline Python; backlog tanpa klaim implementation completion. |
| MBP-004 | DONE | MASTER_BUILD_PLAN.md, ROADMAP.md, FEATURE_REGISTER.md, FEATURE_SPEC_MAP.md, AI_WORKFLOW_STATE.md | MBP-002/003 | Queue tunggal, urutan dan resume disinkronkan; IAM-001-T04 kini local done dengan target acceptance eksternal tetap terbuka. `git diff --check` dan audit diff pass; tidak stage/commit/push. |
| IAM-001-T04 | LOCAL DONE; external gates open | `specs/003-identity-property-access/tasks.md`, permission matrix, route/model/query/job/export/API | MBP-004; follow feature spec/acceptance and preserve user changes | 114 runtime routes (98 named; 36 admin, 6 housekeeping, 9 operations) after adding work-order detail; app controllers/services and Volt actions inventoried; no app export/import/job/API route or tenant-data Volt action found; SQLite role, selector, inactive-principal, session, direct-ID and detail scope regressions pass. MySQL/Windows/browser/SOP remain BLOCKED_EXTERNAL. Refresh inventory when surfaces change. |

## Matriks cakupan modul x operasi

Legenda matrix: `PARTIAL` = ada route/view/test lokal terbatas, bukan release pass; `NONE` = tak ditemukan implementasi pada audit statis; `GAP:<ID>` = diblokir decision/resource; `UNKNOWN` = bukti belum cukup. I/E/R menyatakan capability teramati, bukan assumption berdasarkan kebutuhan. Perm/tenant menyebut cakupan teruji pada route tertentu saja. Dasar: feature register/map/spec acceptance, route/view inventory dan feature task files.

| Modul (Feature ID) | CRUD/lifecycle | Import | Export | Report/read view | Permission / tenant isolation | Bukti awal / gap utama |
|---|---|---|---|---|---|---|
| Discovery/baseline (DISC-001) | N/A | N/A | N/A | PARTIAL planning evidence | N/A | `specs/001-discovery-baseline`; owner decisions and pilot scope remain open |
| Runtime/bootstrap (OPS-001) | PARTIAL first-admin | NONE | NONE | PARTIAL health | PARTIAL loopback/bootstrap; LAN boundary unknown | `routes/web.php`, OPS task T03a-c; Windows/service/pilot GAP OPS-D4 |
| IAM/accounts/membership (IAM-001) | PARTIAL approvals, membership, suspend/reactivate | NONE | NONE | PARTIAL account/membership/audit views | Current app-defined route/query/Volt tenant scope verified on SQLite | `specs/003.../permission-matrix.md`, `acceptance.md`; MySQL/Windows/browser/SOP gates remain |
| Property/outlet/room (PROP-001) | PARTIAL CRUD/deactivate/status/block/history | NONE | NONE | PARTIAL history | PARTIAL platform-admin & limited housekeeping/operations; global paths open | `PropertyController`, `PropertyInventoryController`, task evidence; MySQL race GAP |
| Guest/privacy (GUEST-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-007 | spec FR-003..005, tasks T01 blocked; no guest route/view observed |
| Rates/availability (RATE-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-004 | no route/view observed; tasks T01..04 |
| Reservation/group/walk-in (RES-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-005 | no route/view observed; tasks T01..04 |
| Front desk/stay (FO-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-005 | no route/view observed; tasks T01..06 |
| Folio/payment/invoice (FIN-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-004/D-010 | no route/view observed; immutable ledger acceptance not implemented |
| Cashier/night audit (FIN-002) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-010 | no route/view observed; close/reopen policy absent |
| Audit (AUD-001) | PARTIAL read-only (no mutation) | NONE | NONE | PARTIAL five-source viewer | PARTIAL admin-only viewer; full sources/roles/tenant open | `/admin/audit`, `specs/011...`; retention/export excluded, taxonomy open |
| Housekeeping (HK-001) | PARTIAL queue lifecycle | NONE | NONE | PARTIAL queue/history | PARTIAL role/property checks tested on SQLite | `/properties/{property}/housekeeping/*`; SOP/MySQL/pilot GAP |
| Engineering (ENG-001) | PARTIAL assets/work-orders | NONE | NONE | PARTIAL queue/history | PARTIAL operations role/property checks tested on SQLite | `/assets`, `/work-orders`; SOP/MySQL/pilot GAP |
| Inventory/procurement/minibar (INV-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP SOP/valuation | no route/view observed; movement ledger absent |
| Event/catering (EVT-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP commercial SOP | no route/view observed |
| POS/room service (POS-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-009/D-010 | no route/view observed; tender/provider unresolved |
| Reports/exports (RPT-001) | N/A | NONE | NONE | NONE (outside audit viewer) | UNKNOWN/GAP definitions/privacy/finance | no report/export routes observed; audit viewer is not general reporting |
| Backup/recovery/update (OPS-002) | NONE | N/A | NONE | NONE | UNKNOWN/GAP OS/key/operator | no app page/route observed; runbook/restore unverified |
| Accounting (ACC-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP accountant | no route/view; chart/journal approval absent |
| Doorlock/RFID (HW-001) | N/A | N/A | NONE | NONE | UNKNOWN/GAP D-006/vendor/device | no route/view; physical hardware absent |
| Sync/outbox (SYNC-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-007 | no routes/views observed; privacy/conflict policy absent |
| OTA/payment/portal (EXT-001) | NONE | NONE | NONE | NONE | UNKNOWN/GAP D-009/contracts | no routes/views observed; provider contract/access absent |
| Release/license (REL-001) | N/A | N/A | N/A | NONE | UNKNOWN/GAP D-008/signer/entitlement | no in-app routes; target release not verified |
| Pilot acceptance (QA-001) | N/A | N/A | N/A | N/A | UNKNOWN; target evidence absent | tasks blocked on Windows/MySQL/operator/pilot |

CRUD acceptance minimum: list/detail, create, server validation, update, search/filter/sort/pagination sesuai volume, lifecycle non-destruktif (deactivate/restore untuk master, void/reversal untuk transaksi posted), audit actor/time/reason, duplicate/concurrency/transaction test, authorization/IDOR/property A-B. Import/export: preview, schema/size limits, validation/error report, duplicate/idempotency, transaction/rollback, permission, audit, escaping/PII minimization, rate/size limits. Reports: metric/source/date/timezone/financial snapshot reconciliation, role/property filter, deterministic total, export equivalence dan audit. `NONE` untuk import/export berarti tak ada capability produk ditemukan pada route/view/spec evidence; tidak membuat klaim absennya semua code path dinamis.
## Navigasi dan manual acceptance per halaman/alur

Inventaris sumber: 101 baris `routes/web.php` (definisi literal; route ada yang dinamis), `routes/settings.php` dibaca sebagai sumber tambahan, 56 Blade view files terdaftar via `resources/views/**/*.blade.php`. Laravel auth/verification/password/2FA routes dapat berasal dari package/route provider; Livewire component actions tidak menjadi route literal. Karena itu daftar ini mencakup setiap route literal yang ditemukan dengan grouping berdasarkan destination/page; bukan bukti seluruh runtime route. Validasi final gunakan `php artisan route:list` pada lingkungan aplikasi yang tersedia sebelum QA-001. Views seperti `welcome`, partial, dan component bukan halaman mandiri kecuali route/runtime membuktikan.

| Route / page group (sumber) | Journey manual wajib |
|---|---|
| `/`, `/login`, `/register`, auth verify/forgot/reset/confirm-password/2FA (home/auth views + framework routes) | guest/auth transitions, pending approval, valid/invalid, throttling, reset/verify/2FA, session/CSRF, keyboard/errors |
| `/setup/first-admin` GET/POST (loopback) | first create, invalid data, replay locked, proxy/header spoof denial, no session issuance |
| `/operator/health` (admin health) | healthy/DB/schema/storage failures, generic response, no secrets, inactive/non-admin denial |
| `/dashboard` | role-specific shortcuts, assigned property, no fabricated metrics, empty/errors |
| `/admin/users` GET and `/{user}/approve|reject` POST | approve/reject, reason validation, target/self/admin boundary, audit, pending login denied |
| `/admin/users/memberships` GET; `/{user}/memberships` POST; `/{user}/memberships/{property}` DELETE | assign/change/revoke, role/property select, inactive cleanup, audit and auth-version/session impact |
| `/admin/users/accounts` GET; `/{user}/suspend|reactivate` POST | lifecycle, reason, self/platform-admin denial, remember-me and active sessions |
| `/my/properties`, `/my/operations-properties` | show only correct active role memberships; empty state; direct cross-property denial |
| `/properties` GET/GET create/POST; `/{property}/edit` GET, `/{property}` PUT, `/{property}/deactivate` PATCH | list/create/edit/deactivate, validation/duplicate, retention, role denial |
| `/properties/{property}/outlets[/{outlet}/edit]`, POST/PUT/deactivate PATCH | CRUD lifecycle, property selectors, cross-property FK/IDOR, validation/history |
| `/properties/{property}/room-types[/{roomType}/edit]`, POST/PUT/deactivate PATCH | CRUD lifecycle, references, cross-property scope, inactive constraints |
| `/properties/{property}/rooms`, `/room-history`; room create/edit/update/deactivate/status PATCH; room-block POST/cancel PATCH | CRUD/status lifecycle, transitions, date bounds/overlap, reason, immutable history, concurrent race |
| `/properties/{property}/housekeeping` board/status; `/housekeeping/tasks` list/create/assign/transition | assigned role/property only, filters/paging, create/assign/start/complete/cancel, no unintended room status effect |
| `/properties/{property}/assets`, `/work-orders`, `/work-orders/{workOrder}` detail and dynamic actions | asset CRUD/deactivate/history, order create/detail/history/assign/start/resolve/cancel, same-property active assignee/asset, no room-status effect |
| `/admin/audit` | source/property/date filters, date bounds, pagination, escaping/minimization, read-only, authorization |
| `/settings/profile`, `/settings/password`, `/settings/appearance`, `/settings/two-factor*`, passkey discovery/registration/removal, delete account (settings/auth components) | route-list exactness first; CSRF/session, own-account scope, passkey lifecycle, generic discovery response, keyboard/responsive |
| Future GUEST/RATE/RES/FO/FIN-001/FIN-002 journeys (no observed route) | after feature routes exist: privacy/search, quote/race, booking/group/walk-in/change/cancel, arrival/check-in/move/share/checkout, folio/payment/refund/close/reopen/reconciliation |
| Future INV/EVT/POS/RPT/OPS-002/ACC/HW/SYNC/EXT/REL/QA pages (no observed route) | after routes exist: ledger/lifecycle, capacity/tender, report reconciliation/export abuse, backup/restore/update, accountant/device/provider/offline/pilot journeys |

Global acceptance: route-list compared to registry at QA start; every reachable page/Livewire action checked for visible navigation, role/tenant direct URL, keyboard/focus/labels, 320px/mobile and desktop, loading/empty/error, offline assets. Manual/browser/Windows tests only reported after execution; unit assertions do not substitute. Dinamis route/model bindings, framework package routes and component actions remain explicit inventory ceiling; no guess that undocumented pages are reachable.

## Backlog slicing sampai PMS end-to-end

Semua ID pada tabel backlog unik terhadap ID feature task saat audit (validasi MBP-003). Paths adalah target awal, bukan bukti file sudah ada. Gate blocker bernama di §Gates. Status: READY = planning/safe audit atau bounded slice dengan syarat tersedia; BLOCKED = menunggu sumber eksternal bernama; NOT_STARTED = dependency belum terpenuhi. `DONE` hanya dipakai bila acceptance bukti tersedia. Setiap implementation task memerlukan spec/plan/tasks/acceptance lulus analisis sebelum kode.

| ID | Feature slice / target awal | Dependencies / preconditions | Measurable acceptance & verification | Risk / blocker / status |
|---|---|---|---|---|
| MBP-002 | Matrix + route/page/journey inventory, bagian dokumen ini | MBP-001; register/spec/map/roadmap/routes/views/task audit | satu baris per register feature; I/E/R/CRUD/permission status+bukti/gap; route groups + manual journey; boundary dicatat; script audit | route package/dynamic ceiling; DONE bukti sesi 2026-10-23 |
| MBP-003 | Unique feature backlog, bagian dokumen ini | MBP-002 | setiap register ID punya slices dan acceptance/verifikasi/status; validasi IDs/ref | docs-only; DONE bukti validasi read-only |
| MBP-004 | Sinkronisasi master/index/state | MBP-002/003 | satu queue/resume source; konsistensi ID/status; diff check | docs-only; DONE bukti final |
| DISC-001-B01 | Owner decision register, pilot charter, site workflow & scope; specs/001 + DECISION_LOG | MBP-002/003; owner discovery | every D-* decision has named owner, evidence, due/status; pilot scope/mandatory journeys signed or explicit blocker | Owner confirmation required; BLOCKED:MBP-D1 |
| DISC-001-B02 | Close approved planning baseline and versioned requirements; specs/001 + REBUILD_PLAN | B01; external decisions resolved | all modules trace to approved acceptance, no unresolved critical assumptions; spec consistency check | decisions still pending; NOT_STARTED |
| IAM-001-T04 / T05b / T06 | Current app-defined IAM route/query/Volt inventory, regression matrix and operator notes; `specs/003-identity-property-access/` | MBP-004; current provisional IAM matrix | 113 runtime routes inventoried; focused 133 tests/772 assertions; app A/B property paths and supported roles denied/allowed as mapped; SQLite only | Local scope DONE; MySQL/Windows/browser/owner SOP remain `BLOCKED_EXTERNAL`; refresh inventory when surfaces change |
| OPS-001-B01 | Windows/runtime/service/signing/pilot decision packet; specs/002, DECISION_LOG | OPS-D4 owner/resource | named supported OS/runtime/service/signer/pilot; source-evidence + approval or named blocker | Windows host/owner not available; BLOCKED:OPS-D4 |
| OPS-001-B02 | Signed offline install, service, firewall, reboot; specs/002 source/deployment | B01 | install/reboot/WAN-off/unauthorized-LAN acceptance on clean pilot | pilot/signing; BLOCKED:OPS-D4 |
| IAM-001-B01 | Local permission/route policy, acceptance and operator docs; specs/003 | T04/T05b/T06 evidence | current runtime app surfaces mapped and negative tests listed in acceptance; SQLite evidence | LOCAL DONE; property-manager/owner confirmation and MySQL/Windows/browser acceptance remain external |
| IAM-001-B02 | Close membership/session revocation and target DB semantics; specs/003 migrations/services/tests | B01; approved MySQL | active/inactive membership/user/property, stale session and concurrency tests pass on SQLite+target | target MySQL unavailable; BLOCKED:DB-01 |
| IAM-001-B03 | User/account/membership UI full browser acceptance; views/QA docs | B01/B02; browser available | all pages/forms/list/action flows per manual matrix all roles; defects closed | browser lib missing, pilot unknown; BLOCKED:QA-01 |
| PROP-001-B01 | LOCAL DONE: property/inventory tenant boundary and CRUD regression; specs/004, controllers/views/tests | IAM B01 | `PropertyManagementTest|PropertyInventoryTest`: 25 tests/146 assertions; create/edit/deactivate, validation, cross-property IDs and role denial pass on SQLite | browser/device and target-DB acceptance remain BLOCKED_EXTERNAL |
| PROP-001-B02 | Room block/status locking and concurrency | B01; approved MySQL | simultaneous conflicting updates single valid result; immutable history; full regression | target DB absent; BLOCKED:DB-01 |
| GUEST-001-B01 | LOCAL DONE: data-flow inventory and provisional technical guardrails; specs/004 + DECISION_LOG | D-007 decision remains external | No guest/customer flow currently in `app/`; GUEST-D7 records PDP source, no-schema/no-PII guardrails and required approval matrix; T01 remains BLOCKED_EXTERNAL until owner/privacy counsel names fields, purpose, basis, retention and rights handling | legal/owner approval required before guest-data implementation |
| GUEST-001-B02 | Guest profile/search/dedup/access audit; specs/004 guest paths | B01 | CRUD/search/duplicate review; role/property/PII masking and audit tests | D-007; BLOCKED:D-007 |
| GUEST-001-B03 | Guest document lifecycle/export safeguards; specs/004 | B01/B02 | upload limits/scan policy/retention/delete/export tests, no unauthorized PII | legal/security policy; BLOCKED:D-007 |
| RATE-001-B01 | Currency/tax/rounding/rate-rule decision + model; specs/005 | D-004 owner/accountant | approved table test vectors and timezone/effective-date semantics | no accountant approval; BLOCKED:D-004 |
| RATE-001-B02 | Rate calendar/quote/availability engine; specs/005 models/services/routes/views | B01, PROP B02 | date matrix, room block/stay availability, deterministic quote and role tests | requires DB concurrency; BLOCKED:D-004/DB-01 |
| RATE-001-B03 | Concurrent availability reservation guard | B02; target MySQL | race test never oversells except explicit audited exception | MySQL unavailable; BLOCKED:DB-01 |
| RES-001-B01 | Booking lifecycle SOP; specs/005 + DECISION_LOG | D-005 owner/property manager | approve cancellation/no-show/deposit/waitlist/group/walk-in/overbooking states | SOP absent; BLOCKED:D-005 |
| RES-001-B02 | Individual/group/walk-in create/read/change/void lifecycle | GUEST B02, RATE B02, B01 | E2E flows; immutable snapshot, rooming list/history, idempotency, IDOR tests | upstream blocked; BLOCKED:D-005 |
| RES-001-B03 | Reservation concurrency and confirmation | B02; target DB | parallel requests bounded, no oversell; full fake-data lifecycle | DB absent; BLOCKED:DB-01 |
| FO-001-B01 | Stay/check-in/out policy; specs/006 | RES B02 + D-005 | approved identity, early/late, room readiness, unpaid checkout acceptance vectors | owner SOP; BLOCKED:D-005 |
| FO-001-B02 | Arrival/in-house/departure pages and check-in/out | B01 | each transition links stay/room/folio and housekeeping handoff; role/tenant tests | prerequisites; BLOCKED:D-005 |
| FO-001-B03 | Room move/share lifecycle | B02 | transactionally revalidate occupancy, preserve history/folio and reason | policy + target DB; BLOCKED:D-005/DB-01 |
| FO-001-B04 | Reservation-to-checkout E2E/offline | FO B02/B03, RES | full manual + automated fake data, WAN disconnected, audit consistent | pilot needed for offline acceptance; BLOCKED:QA-01 |
| FIN-001-B01 | Finance decision packet, folio ledger and invoice policy; specs/007 | D-004/D-010 accountant+owner | signed currency/tax/tender/refund/numbering rules and example reconciliation | approvals absent; BLOCKED:D-004/D-010 |
| FIN-001-B02 | Folio charges/deposits/payments/refunds/void | B01 + FO B02 | ledger balances, idempotent retries, append-only reversal, role/property tests | finance policy; BLOCKED:D-004/D-010 |
| FIN-001-B03 | Invoice snapshot/numbering/export | B01/B02 | immutable issued document, jurisdiction vectors, permission/audit/export tests | legal/accountant; BLOCKED:D-004 |
| FIN-002-B01 | Business date/cashier/variance/close SOP; specs/007 | D-010 accountant/owner | signed close/reopen thresholds and reconciliation cases | approval absent; BLOCKED:D-010 |
| FIN-002-B02 | Shift/day close + reopen workflow | FIN-001 B02, B01 | duplicate close safe, snapshot stable, reopen revision audited | policy + target DB; BLOCKED:D-010/DB-01 |
| FIN-002-B03 | Reservation-to-cashier close E2E | FO/FIN slices | totals independently recompute to ledger and tests; fake-data acceptance | prior dependencies; BLOCKED:D-010 |
| AUD-001-B01 | Taxonomy, all producers, role scope, retention decision; specs/008/011 | IAM; privacy owner | source inventory incl financial/security events; retention/deletion policy approved | legal/privacy; BLOCKED:AUD-D1 |
| AUD-001-B02 | Remaining authorized audit views and controlled export | B01 | source IDs/filter/property boundary, minimized PII, export audit tests | policy; BLOCKED:AUD-D1/D-007 |
| HK-001-B01 | Owner SOP/assignment/room-ready policy; specs/012/008 | OPS-D5/HK owner | checklist, assignment, lost property, ready transition approved | provisional only; BLOCKED:HK-D1 |
| HK-001-B02 | Close missing queue migration/route/UI/browser acceptance | B01; migration target | migrate/rollback + all state journeys and authorized roles | MySQL/browser; BLOCKED:DB-01/QA-01 |
| ENG-001-B01 | Work-order/asset/down-room/preventive SOP; specs/013/008 | ENG owner | asset lifecycle, severity, downtime/cost, schedule rules approved | provisional only; BLOCKED:ENG-D1 |
| ENG-001-B02 | Scheduling, cost/history and target acceptance | B01; MySQL/pilot | idempotent schedules, property boundary, resolution and device/browser tests | dependencies; BLOCKED:ENG-D1/DB-01 |
| INV-001-B01 | Unit/cost/valuation/approval/minibar SOP + spec split | specs/008; owner/accountant | approved units, costing, receive/issue/adjust/count/void rules | policy absent; BLOCKED:INV-D1 |
| INV-001-B02 | Stock movement ledger, purchase/receive/issue/count lifecycle | B01 | ledger recomputes balances, idempotency, concurrency, tenant/auth tests | needs approval/DB; BLOCKED:INV-D1/DB-01 |
| INV-001-B03 | Minibar posting and reconciliation | B02 + FO/FIN | consumed items post once to correct folio and reconcile stock | finance dependencies; BLOCKED:D-010 |
| EVT-001-B01 | Event/BEO/capacity/quote/deposit policy + bounded spec | specs/008 | signed capacity, conflict, pricing/cancellation lifecycle | commercial owner; BLOCKED:EVT-D1 |
| EVT-001-B02 | Event booking/resource/deposit workflows | B01, FIN-001 | capacity race, audit, deposit/reversal and role/property tests | policy+finance; BLOCKED:EVT-D1/D-010 |
| POS-001-B01 | Outlet/order/tender/provider decisions + spec | specs/008 | owner signs offline scope, tender and reconciliation examples | D-009/D-010; BLOCKED:D-009 |
| POS-001-B02 | Local order/room charge/idempotent payment adapter | B01, FIN-001, FO | WAN-off ordering, retry no duplicate charge, property/role tests | provider/finance; BLOCKED:D-009 |
| RPT-001-B01 | Metrics, date basis, role/export/privacy specification | specs/008 | every report defines source/metric/timezone/reconciliation/owner | finance/privacy; BLOCKED:RPT-D1 |
| RPT-001-B02 | Operational and financial reports + export | B01, source modules | totals reconcile, A/B tenant denials, export escaping/size/audit | upstream features; BLOCKED:RPT-D1 |
| OPS-002-B01 | Backup key/operator/retention decision and spec split | specs/008; OPS-D4 | named custodian, encryption/key recovery, retention, RPO/RTO | owner/security; BLOCKED:OPS-D5 |
| OPS-002-B02 | Encrypted backup/restore, update rollback and runbook | B01 + OPS B01 | second-host restore integrity; signed update fail-safe on pilot | Windows/key/pilot; BLOCKED:OPS-D4 |
| ACC-001-B01 | Jurisdiction/chart/posting map decision + bounded specs | specs/009; accountant | approved chart, source mappings, period/adjustment rules | accountant absent; BLOCKED:ACC-D1 |
| ACC-001-B02 | AR/AP/GL/subledger slices | B01 + FIN ledger | debits=credits, reconciliation, reversal, role/property tests | approval; BLOCKED:ACC-D1 |
| HW-001-B01 | Device contract/threat model/SDK rights and physical inventory | specs/009; D-006 | vendor/license/auth/replay/failure contract approved | external vendor/device; BLOCKED:D-006 |
| HW-001-B02 | Device agent adapter + simulator contract tests | B01 | allowlist, credential rotation, replay/idempotency negative tests | no physical access; BLOCKED:D-006 |
| HW-001-B03 | Physical lock/card acceptance | B02 | exact model offline valid/invalid/revoked tests, network isolation | device absent; BLOCKED:D-006 |
| SYNC-001-B01 | PII ownership/sync direction/conflict/consent policy | specs/010; D-007 | approved field-level scope and retention/consent | privacy owner; BLOCKED:D-007 |
| SYNC-001-B02 | Outbox/idempotent sync, bounded retry/reconcile UI | B01 | duplicate/reorder/offline/recovery tests; local core unaffected | provider/DB; BLOCKED:D-007 |
| EXT-001-B01 | Connector priority/provider/account/contract decision | specs/010; D-009 | named provider/sandbox/contract, fields and owner | account/contract not available; BLOCKED:D-009 |
| EXT-001-B02 | Provider adapters for each approved connector | B01; SYNC when relevant | contract tests, disabled-by-default, outage isolation, redacted logs | provider access; BLOCKED:D-009 |
| REL-001-B01 | Entitlement/grace/Flux rights/signer custody decision | specs/010; D-008 | named protected custodian+approver and redistribution terms | owner/legal/licensor; BLOCKED:D-008 |
| REL-001-B02 | Signed bundle/SBOM/verify/staged update/rollback | B01 + OPS-002 | tampered artifact rejected; supported Windows migration/recovery pass | signer/pilot; BLOCKED:D-008 |
| QA-001-B01 | Build route-complete manual + automated acceptance matrix | all feature slices; browser/DB pilot | every register feature/journey linked to evidence or blocker; no orphan routes | runtime route list/browser unavailable; READY after implementation |
| QA-001-B02 | Full pilot install/offline/tenant/finance/security/restore acceptance | B01 + all mandatory feature gates | zero unexplained failures; exact OS/MySQL/device results; evidence signed | target Windows/MySQL/site needed; BLOCKED:QA-01 |
| QA-001-B03 | Independent final audit and disposition | B02; clean reviewed diff | all mandatory criteria PASS or explicit BLOCKED; no false FINAL PASS | review/owner acceptance; NOT_STARTED |

Masing-masing slice di atas memerlukan feature-level spec/plan/tasks/acceptance yang dipisah jika belum tersedia; path code target awal adalah hasil audit/sumber aktual, bukan janji kode tersedia. Keputusan/akses eksternal tidak menghalangi slice planning independen; ia menahan hanya implementasi terdampak.

## Gates, risks, external backlog

| Gate/blocker | Evidence / options | Recommendation / unblock condition |
|---|---|---|
| MBP-D1 scope/priorities | PRD describes broad PMS; pilot scope not signed | Treat roadmap as target, release scope chosen in pilot charter before deployment. |
| D-004 tax/currency/rounding | OPEN_DECISIONS/feature tasks | Accountant + jurisdiction approval; until then no final invoice/tax claim. |
| D-005 booking/stay SOP | cancellation, deposit, overbooking, room-share/checkin rules unresolved | Owner/property manager approves state transitions; no assumed overbooking. |
| D-007 privacy/retention | PII, consent, document/export deletion unresolved | Privacy/legal owner specifies fields, purpose, retention, access and DSAR; minimize and defer docs/sync. |
| D-008 release entitlement | license/entitlement and signer custody open | Owner designates signer/approver and entitlement terms; protected key + staged signed update. |
| D-009 providers | OTA/payment/portal priorities/contracts unknown | Select provider and sandbox/contract; integrations disabled meanwhile. |
| D-010 finance controls | cashier, close, tender, adjustment approvals unresolved | Accountant/owner signs policy and reconciliation examples. |
| OPS-D4/D5, HK/ENG SOP | provisional research defaults, not approval | Owner/property manager confirms pilot SOP and role matrix. |
| MySQL target | only SQLite/Linux evidence recorded | disposable approved MySQL version; run migration/locking/trigger/concurrency suite. |
| Windows pilot/runtime/signing | Windows 11 Pro GAC provisional; device/runtime/service/signer unknown | named pilot, runtime, service owner, signing custodian and two-person release approval. |
| Hardware | lock/card/SDK/license/physical device unavailable | vendor contract, test hardware and replay/network acceptance. |
| Browser/device | prior Chromium launch missing `libatk-1.0.so.0`; no visual acceptance | install/authorize test browser environment separately; execute role-based matrix. |
| Live routing | port 8083 currently app lama | Never change until separate acceptance/authorization, rollback plan and maintenance window. |
| Licensing/secrets | Flux Pro commercial archive in history; no upstream configured per memory/state | Never stage/push archive/secrets; resolve safe distribution/history policy separately. |

Risks: tenant IDOR through non-route pathways; stale permissions/sessions; financial mutation/replay; privacy leakage through logs/export/sync; migration portability; offline dependency; weak backup key custody; concurrency and recovery. Mitigation must be testable at each feature and not deferred solely to final release.

## Definition selesai

Per fase: setiap scope dicocokkan ke feature tasks dan halaman; semua task required DONE dengan bukti command/exit/count; external gates explicitly BLOCKED, bukan pass; focused/full regression, Pint, PHPStan, build dan diff check sesuai project; permission/property negative tests; manual UI jika relevant; docs/decision/state konsisten; no unreviewed diff side effects.

Produk: semua required pilot journeys end-to-end dari install sampai close; CRUD/lifecycle, audit, privacy, roles/property isolation, import/export/report dan operational recovery teruji; financial reconciliation tepat; security checks dan migration/backup/restore/upgrade lulus di target Windows + approved MySQL; WAN-offline core; hardware/provider tests nyata untuk claims terkait; operator docs dan rollback siap; seluruh gate legal/owner/vendor ditandatangani. Hanya `FINAL PASS` bila semuanya dibuktikan. Jika hanya external acceptance tersisa: `READY_FOR_EXTERNAL_ACCEPTANCE` dengan daftar nyata; selain itu `IN PROGRESS`.
