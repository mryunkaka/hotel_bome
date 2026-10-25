# Workflow state and Hermes handoff

## Current progress snapshot — 2026-10-25

- **Overall project phase:** Phase 5, Foundation acceptance — PARTIAL. Phase 6 started with RATE-001-T01 rate-plan master CRUD only; core PMS (guest/reservation/folio/cashier) is NOT IMPLEMENTED. D-004/D-005/D-010 remain provisional, not owner/accountant SOP approval. GUEST-D7 remains open.
- **Current feature checkpoint:** RATE-001 COMPLETE. RES-001 COMPLETE. FO-001-T01/T02/T04/T06 implemented; T03/T05 PARTIAL/BLOCKED by missing front-desk authorization grant, room-share SOP, target-DB concurrency evidence and actual housekeeping integration. FO tests 40/100; full suite 343 tests/1424 assertions; `composer test --no-interaction` exit 0 (Pint, PHPStan, PHPUnit). SQLite only.
- **Decision log additions:** D-004 (IDR, PBJT, pembulatan, timezone, invoice), D-005 (reservation lifecycle, cancellation, no-show, deposit, walk-in, group, overbooking=off, check-in/out), D-010 (business date, cashier shift, night audit, void/reversal, approval limits) — semua sementara dan berprovenance, bukan persetujuan pemilik/accountant. GUEST-D7 is corrected: `display_name` and free-text `notes` may contain personal data; implementation is prototype-only and D-007 remains open.
- **OPS-001 status:** app-level first-admin and health slices T03a–T03c implemented. Windows pilot/runtime/signing remain BLOCKED_EXTERNAL.
- **UI-001 status:** T001–T005 DONE; T006 visual browser review remains open (no browser connector).
- **RATE-001-T02 readiness audit (2026-10-25):** NOT READY. T02 requires designing a rate quote service that calculates taxes/rounding per D-004 rules. D-004 is unresolved (no owner/accountant signoff on tax, currency, rounding, or invoice rules). spec.md states `BLOCKED:D-004`; plan.md says "Do not finalize rate/tax columns until D-004 signoff"; master build plan confirms `RATE-001-B01 BLOCKED:D-004`. T02 cannot proceed without D-004.
- **All feature task scan (2026-10-25):** IAM-001 all DONE, AUD-001 all DONE, HK-001 all DONE, ENG-001 all DONE, PROP-001 T01–T04b5 DONE (T04b3 BLOCKED_EXTERNAL:MySQL), GUEST-001 T00/T01a DONE (T01/T02/T03 BLOCKED:D-007), RATE-001 all DONE, RES-001 all DONE, FO-001-T01/T02/T04/T06 implemented; T03/T05 PARTIAL/BLOCKED (authorization grant, target-DB concurrency, HK integration), OPS-001 T03a/b/c DONE (T01/T02/T04/T05/T06 BLOCKED_EXTERNAL), UI-001 T001–T005 DONE (T006 BLOCKED_EXTERNAL:browser).
- **Next action:** Resolve FO authorization grant with IAM scope; then implement service authorization. In parallel, next clear feature needs readiness scan.
- **Code checkpoint:** `composer test --no-interaction` exit 0: PHPUnit 343 tests/1424 assertions; Pint and PHPStan passed; SQLite. `git diff --check` passed before docs update; rerun after final changes.
- **Blockers:** FO role grant absent per `specs/003-identity-property-access/permission-matrix.md`; room-share/unpaid-checkout policy not decided; MySQL occupancy race/restart and actual HK workflow integration unverified. Other blockers: UI-001-T006 browser; OPS-001 Windows/runtime/signer; guest real-data BLOCKED:D-007; PROP-001-T04b3 MySQL.
- **Resume:** Review FO partial task scope and permission matrix; no guest real data.
- **Report hygiene:** Hermes continuation prompt now distinguishes interim progress from a final report and requires final Git/state/test verification before it reports completion.

Keep this snapshot at the top of the file current. Update it after each completed task and before any session stops, with the exact phase, active task, next TODOs, verification evidence, blockers, commit SHA, and a concrete resume action. Do not invent percentages; calculate them only from explicit task status.

This snapshot supersedes older branch, checkout, and handoff instructions in the historical entries below whenever they conflict. All entries below it are historical checkpoints unless explicitly labeled otherwise.

## UI-001 implementation evidence — 2026-10-25

- Owner clarified the visual reference as `D:\Project\hotel-bome` and requested a visible Light/Dark/System default selector plus dark-mode contrast fixes. The reference checkout is read-only; the implementation target remains the Laravel + Livewire + Tailwind + Flux rebuild.
- At initial UI diagnosis, local `main` was `d86b264`; the implementation and follow-up checkpoint were later committed as `7dfbdf0`. Preserve untracked `docs/product/BASELINE_EVIDENCE.md`, `packages/flux-pro-2.20.1.zip`, and local `tmp/`.
- Root cause evidence for `/admin/users/memberships`: Blade has `dark:bg-amber-950 dark:text-amber-100`, but there is no `public/hot`; the served `public/build/assets/app-aFrivcIn.css` was last written 2026-10-17 and lacks both selectors. The latest membership view source is from 2026-10-24. Existing Settings already has Light/Dark/System controls via Flux; global desktop/mobile menus do not.
- Added `specs/014-ui-appearance/{spec,plan,tasks,acceptance}.md` and shared `theme-mode-menu` used by desktop/mobile account menus. Flux Light/Dark/System default choices now appear in both; the settings label is “System default”.
- TDD evidence: the new `AppearanceThemeTest` first failed on the missing menu choices and label, then passed 3 tests/13 assertions after implementation. Full `composer test --no-interaction` passed 194 tests/979 assertions; Pint passed and PHPStan reported 0 errors.
- Local PHP CLI ran `npm run build` via Node 24.22.1; Vite/Vite Plus passed 22 modules. The refreshed app CSS contains `dark:bg-amber-950` and `dark:text-amber-100`. `/login` references the new hash and the CSS asset returns HTTP 200. `git diff --check` passed.
- T006 remains open for visual authenticated-browser review (dark/light/system at desktop/mobile); no Windows computer-use connector is available in this session, so do not claim visual acceptance. `public/build` is ignored and must not be staged. User-owned baseline evidence, Flux archive, and `tmp/` remain untouched.

## Historical checkpoint — single mainline consolidation before VPS cleanup (2026-10-25)

- Owner directs routine work on one active branch, `main`, with tests before direct push. Canonical VPS checkout `/home/saidadam28/workspace/hotel_bome_workflow` is on `main`; local `D:\Project\hotel_new` is synchronized to the same remote commit.
- The GitHub `main` snapshot already contained the `/setup/first-admin` wizard and the current application/test files. The old VPS `work/hermes-full-build` working tree was compared file-by-file; application code, views, migrations, feature specs, and tests matched `main`. Its two newer documentation lines were corrected and committed.
- Verification on VPS: `composer test --no-interaction` exit 0, Pint and PHPStan included, PHPUnit 191 tests / 966 assertions using SQLite in-memory. Composer emits PHP 8.4 deprecation notices from system dependencies. This does not cover browser, MySQL, Windows/LAN, SOP, or hardware acceptance.
- Pushed `09ee3e8` (`docs-sync-verified-mainline-checkpoint`) and `36f692b` (`remove-obsolete-flux-package-artifact`) directly to `origin/main`. The obsolete tracked Flux Pro 2.20.0 archive was removed from the current tree; the owner’s 2.20.1 archive remains local/untracked and was not staged.
- At that checkpoint, `/home/saidadam28/workspace/hotel_bome` still had staged Spec Kit/workflow changes. The owner later explicitly requested its deletion on 2026-10-25; that checkout is now absent. Treat any later references below to preserving it as historical and superseded.

## Historical checkpoint — Work-order detail page and CRUD/filter safeguards (2026-10-23)

- Branch `work/hermes-full-build`, HEAD `9fb3239`; preserve pre-existing dirty worktree. No stage/commit/push/live changes. Port 8083 and Flux archive untouched.
- IAM-001-T04/T05b/T06 local scope now recorded complete: app-defined route/query/Volt inventory, active/inactive membership tests, cross-property tests, session revocation, acceptance matrix, and provisional operator guidance. No app export/import/job/API route or property-data Volt action exists in current tree. Target MySQL concurrency/constraints, Windows/browser and owner SOP remain external gates; this is not full production IAM acceptance.
- `PropertyInventoryTest::test_active_non_admin_memberships_cannot_read_or_mutate_property_master_inventory` passed after expanding to active `hotel_admin`, housekeeping, operations, front-desk and finance memberships (1 test/12 assertions). Direct inventory list and cross-property update denied; protected rows unchanged (SQLite).
- Property master CRUD remains platform-admin-only by provisional matrix; do not grant hotel-admin/staff permissions without approved policy. `PropertyManagementTest|PropertyInventoryTest` passed 25 tests/146 assertions; browser, MySQL race, Windows/pilot and owner SOP remain external. Guest PII/rates/reservation/finance are blocked by D-007/D-004/D-005/D-010; do not implement speculative rules.
- No scheduler or deployment changes. Keep route inventory re-audit on any new endpoint/action; no commit/push.
- Local follow-up `PROP-001-B01` regression passed `sudo -u saidadam28 php artisan test --filter='PropertyManagementTest|PropertyInventoryTest'`: 25 tests/146 assertions (SQLite). Master plan and feature register now record IAM-001-T04 and PROP-001-B01 as locally verified, not full product acceptance.
- GUEST-001-T00 local design checkpoint completed: no guest/customer processing found under `app/`; added GUEST-D7 with JDIH BPK UU 27/2022 source, conservative no-document/no-export/no-sync/no-retention-duration defaults, and explicit owner/privacy-counsel questions. Updated `specs/004-property-guest-foundation/tasks.md`, master queue and feature register. No schema, route, app code, or guest data added. T01–T03 remain gated pending policy; document is not legal advice/approval.
- Detail-page gap filled from ENG-001 acceptance: `GET /properties/{property}/work-orders/{workOrder}` plus read-only detail/history Blade page and links from mobile/desktop lists. Same-property operations member can read; other property direct ID returns 404. `WorkOrderTest`: 15 tests/58 assertions, detail case 6 assertions. Initial test failed because route absent; after implementation passed.
- Detail query exposed stale PHPStan shape on `WorkOrders::order()`; annotations updated to actual row fields. One intermediate PHPStan run failed on stale type; fresh full check now passes.
- Earlier filter/code safeguards retained: asset status accepts `active|inactive`; work-order `open|in_progress|resolved|cancelled`, empty `status=` means All; unknown values rejected. Asset code normalization precedes uniqueness validation, so lowercase duplicates return validation error instead of DB unique exception.
- Fresh final `sudo -u saidadam28 composer test --no-interaction`: exit 0; 191 tests/965 assertions; Pint 102 files; PHPStan 0 errors. Composer dependency deprecation notices remain. Scratch Vite build with Node v22.23.3 passed, 22 modules. Browser click-through still not run. MySQL/Windows/pilot/SOP acceptance remain external.
- Follow-up room-board filter hardening: server validates `q` string/max 40, housekeeping status and lifecycle enums; invalid status/lifecycle, array search and overlong search return validation errors. UI search input now declares maxlength 40. RED reproduced invalid input acceptance. `PropertyInventoryTest`: 18 tests/117 assertions; Pint passed both changed PHP files. Full suite above includes change. `git diff --check` pending final check.
- Focused `EngineeringAssetTest|WorkOrderTest`: 23 tests/96 assertions; `WorkOrderTest`: 15/58. Housekeeping latest: 10 tests/27 assertions.

## Previous checkpoint — Master plan and ENG-001 acceptance (historical)

- Added `docs/product/MASTER_BUILD_PLAN.md`: phases/dependencies, feature acceptance, local verification/release flow, risk controls and external gates; linked conceptually to roadmap/register and task docs. No task scheduler exists (`cronjob_manage list`: 0 jobs); no duplicate scheduler created.
- Finished provisional engineering asset CRUD and work-order linkage: property-scoped create/edit/deactivate, active/inactive filter, immutable visible event history, responsive UI; work orders reject cross-property/inactive assets. Fixed active-list filter bug found by RED test.
- Verification: `sudo -u saidadam28 composer test --no-interaction` passed 175 tests/885 assertions (includes Pint 102 files and PHPStan 0 errors); scratch Vite `vp build` passed, 22 modules; `git diff --check` clean. `pnpm` is unavailable; existing `./node_modules/.bin/vp` worked.
- Remaining external: ENG-D1/ENG-D2 owner SOP, MySQL concurrency/schema, browser/Windows/LAN/pilot. No commit/push; worktree has pre-existing and concurrent changes. Never touch live app/8083 or Flux archive.
- Next local task at that time: IAM-001-T04 tenant-path authorization/inventory in `specs/003-identity-property-access/tasks.md`; superseded by current planning-only user instruction above.

## Superseded next-task note — IAM-001-T04

The historic T04 reference below is retained as audit trail only and is superseded by latest checkpoint: T04 remains open in feature task file but is next READY in master queue after MBP planning completion. Do not infer completion from either status; verify latest checkpoint for resume order.

<!-- historical body continues -->

## Previous checkpoint — HK-001 provisional work queue (historical)

- Implemented `specs/012-housekeeping-work-queue`; full suite then passed 161 tests/823 assertions and scratch Vite build passed 22 modules.
- HK-D1 is provisional, not owner/property-manager SOP approval. MySQL, browser/Windows/LAN and pilot acceptance remain external.


## Previous checkpoint — Windows baseline and hotel SOP research / IAM matrix ready (historical)

- Added `docs/product/MASTER_BUILD_PLAN.md`: phases/dependencies, feature acceptance, local verification/release flow, risk controls and external gates; linked conceptually to roadmap/register and task docs. No task scheduler exists (`cronjob_manage list`: 0 jobs); no duplicate scheduler created.
- Finished provisional engineering asset CRUD and work-order linkage: property-scoped create/edit/deactivate, active/inactive filter, immutable visible event history, responsive UI; work orders reject cross-property/inactive assets. Fixed active-list filter bug found by RED test.
- Verification: `sudo -u saidadam28 composer test --no-interaction` passed 175 tests/885 assertions (includes Pint 102 files and PHPStan 0 errors); scratch Vite `vp build` passed, 22 modules; `git diff --check` clean. `pnpm` is unavailable; existing `./node_modules/.bin/vp` worked.
- Remaining external: ENG-D1/ENG-D2 owner SOP, MySQL concurrency/schema, browser/Windows/LAN/pilot. No commit/push; worktree has pre-existing and concurrent changes. Never touch live app/8083 or Flux archive.
- Next local task: continue IAM-001-T04 tenant-path authorization/inventory in `specs/003-identity-property-access/tasks.md`; preserve open guest privacy/rate/reservation/finance policy boundaries.

## Previous checkpoint — HK-001 provisional work queue

- Implemented `specs/012-housekeeping-work-queue`; full suite then passed 161 tests/823 assertions and scratch Vite build passed 22 modules.
- HK-D1 is provisional, not owner/property-manager SOP approval. MySQL, browser/Windows/LAN and pilot acceptance remain external.


## Previous checkpoint — Windows baseline and hotel SOP research / IAM matrix ready

- Research at 2026-10-23: provisional Windows pilot target is Windows 11 Pro GAC current supported release; Windows 10 22H2 regular support ended 2025-10-14, while ESU is limited transition. Microsoft release lifecycle pages report 26H2 availability 2026-09-29, Home/Pro support through 2028-10-10 and Enterprise/Education through 2029-10-09; recheck before image release. IIS PHP FastCGI is a feasibility option only, not selected/runtime-accepted.
- Added temporary decision OPS-D4 in `docs/product/DECISION_LOG.md`: Windows 11 pilot baseline, staged updates, pilot acceptance matrix, signing-key custody controls, unresolved runtime and explicit Windows 10 ESU/LTSC caveats. No signing custodian named; owner/org must designate custodian and approver.
- Added temporary research-informed OPS-D5: least-privilege role defaults, separate room cleanliness/occupancy/availability, front desk/housekeeping/finance/property-manager boundaries, audited exception/financial correction; tax, finance thresholds, PII, consent and retention remain undecided. Oracle/AHLA material demonstrates product patterns, not universal hotel SOP or Hotel Bome approval.
- Updated OPS-001 task readiness: app slices implemented; prototype can target Windows 11 Pro GAC, while exact pilot machine, runtime/service wrapper, licensing/signing custody and physical acceptance remain blocked.
- Updated IAM-001 T01b to define and test the provisional role/action/property matrix for currently implemented routes, deny all unlisted actions, and avoid granting powers for unimplemented modules. This specification task is unblocked; it does not authorize enabling new capabilities without route-level test evidence. Owner/property-manager review remains required before production acceptance.
- External research sources are linked in OPS-D4/OPS-D5 in `docs/product/DECISION_LOG.md`; no files outside repo or credentials changed.
- Previous code commit remains `9fb323902198cc45db4271c151f29de220293568`; current docs edits are uncommitted. Branch `work/hermes-full-build`; preserve all changes. Do not push because Flux Pro archive exists in ancestry and no upstream is configured.
- IAM-001-T01b route matrix drafted at `specs/003-identity-property-access/permission-matrix.md`; independent read-only route/middleware/test audit corroborated current gates. Added tests: `PasskeyDiscoveryTest` bounds public response to two fixed URLs; `PropertyRoleAuthorizationTest` verifies revoked membership denies next GET/PATCH and cross-property room ID returns 404 without event mutation. Keep T01b unchecked until complete route-by-route security matrix/gaps are closed.
- Automated UI/CRUD coverage: property tests exercise create, update, deactivate (not destructive delete), validation, rendering and unauthorized mutation; inventory tests exercise CRUD/edit/deactivate paths. `php artisan test --filter='PropertyManagementTest|PropertyInventoryTest'`: 21/109. Route tests/checks: focused property/housekeeping/passkey 47/236; `vendor/bin/pint --test` passed. Full `composer test --no-interaction`: 144 tests/677 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Database tests use isolated testing setup; scratch UI DB was separately migrated in `/home/saidadam28/.hermes/cache/scratch/hotel-bome-ui-test.sqlite`, and dummy-admin server was stopped after browser blocked.
- Browser UI click-testing attempted on local scratch app but Chromium would not launch: missing `libatk-1.0.so.0`. No UI click path claimed. No production/hosted database/password was touched. Scratch DB remains at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-ui-test.sqlite`; scratch server stopped. Do not clean scratch DB without explicit housekeeping request.
- Responsive change: `resources/views/properties/index.blade.php` now renders mobile cards below `md` and retains desktop table at `md+`, with larger action targets and wrapping. Regression `PropertyManagementTest::test_property_list_provides_mobile_cards_and_desktop_table` passes. This is one page only; other pages/device/browser combinations not yet validated.
- Build: `pnpm run build` unavailable (`pnpm: command not found`). `./node_modules/.bin/vp build --configLoader runner` initially failed because `public/build/assets` is not writable; scratch build passed to `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build`, 22 modules. Optional `fontaine` notice remains; no new dependency installed.
- Final verification after responsive change: `composer test --no-interaction` passed 145 tests/683 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Composer emits deprecation notices from system Composer/PHP dependencies.
- Additional HTTP smoke attempt against scratch local app: login and property create/read/update routes responded, but deactivation runner first omitted method spoofing (405), then next login got 419 CSRF due cookie/token mismatch. No CSRF bypass attempted; no claim of successful end-to-end HTTP CRUD. Scratch server stopped. Feature tests cover CRUD logic independently.
- IAM authorization continuation (2026-10-23): added `PropertyRoleAuthorizationTest::test_inactive_property_blocks_housekeeping_board_and_status_write`, `PropertyRoleAuthorizationTest::test_pending_and_inactive_housekeeping_users_cannot_read_or_write_property`, and `UserAccountLifecycleTest::test_inactive_user_cannot_access_dashboard_or_personal_settings`. Latest focused tests passed 3 tests/20 assertions. The property test initially failed as intended because the test used mass assignment on guarded `is_active`; corrected test setup to update the DB directly, then observed expected 404 GET/PATCH and no event write. No production authorization code changed. Matrix updated; IAM-001-T01b remains open for exhaustive route/method/property test mapping and inactive membership/user method coverage.
- Latest full verification after tests/docs: `composer test --no-interaction` passed 148 tests/703 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Composer emits deprecation notices from system Composer/PHP dependencies. No build rerun because application code unchanged from previous verified Vite scratch build.
- Continuation verified correct checkout `/home/saidadam28/workspace/hotel_bome_workflow`, branch `work/hermes-full-build`, HEAD `9fb3239`; existing user modifications preserved. `composer test --no-interaction` run as repo owner passed: 153 tests/802 assertions; Pint 87 files; PHPStan no errors. First run as root aborted because Composer disallows plugins as root; rerun as `saidadam28` succeeded. No browser, MySQL, Windows, or production acceptance.
- Corrected stale `PROP-001` status in `docs/product/FEATURE_REGISTER.md`: D-001 is settled; property/outlet/room CRUD and room-status/block history are implemented; remaining gates point to IAM tenant-path inventory, MySQL concurrency, and target acceptance. No product behavior changed.
- IAM-001-T04 route/data-path inventory audit in progress. Keep IAM-001-T04/T05b/T06 open until evidence maps every property-bearing path and tests/acceptance close the required scope; no exports/jobs/API found in initial route inventory, but broader source audit remains pending.
- Next: finish code/data-path audit; advance only implementation tasks already documented READY or scoped via existing specs. Retain unresolved tax, reservation, PII/privacy, external hardware/provider, and deployment decisions as explicit blockers. Do not stage, commit, or push user changes or Flux archive.
- Responsive continuation: `resources/views/properties/inventory/index.blade.php` now provides mobile cards for rooms, outlets, and room types while preserving desktop tables. Room cards retain housekeeping status actions, room-block details/cancel reason input, edit and deactivate; all input buttons use comfortable mobile touch targets. Tests added for all listing variants; focused inventory/list tests passed 4/4 then full `composer test --no-interaction` passed 150 tests/719 assertions; Pint passed; PHPStan 0 errors; `git diff --check` clean.
- Build after inventory responsive work: `./node_modules/.bin/vp build --configLoader runner --outDir /home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-2` passed, 22 modules. Existing optional `fontaine` notice only. Output in scratch; no public assets overwritten. Browser and device testing remain deferred as requested.
- User preference clarified: build pages, inputs, CRUD, features, and flows before final comprehensive testing. Continue feature implementation in bounded slices; retain only lightweight regression checks during build and do full acceptance later. Do not claim full PMS complete; browser/Windows/MySQL/pilot and external owner/domain decisions still open.
- Responsive continuation: mobile cards + desktop tables added for housekeeping property selector, housekeeping board, room history, and audit history (`resources/views/properties/housekeeping/{properties,board}.blade.php`, `resources/views/properties/inventory/history.blade.php`, `resources/views/audit/history.blade.php`). Existing room/room-type/outlet and property lists also responsive. Inputs/actions preserved; audit data stays escaped and read-only. Related assertions added to feature tests.
- Latest verification after responsive slice: `composer test --no-interaction` passed 150 tests/727 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Vite+ scratch build passed 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-3`; optional `fontaine` notice. Browser and device tests remain deferred per user instruction until feature build phase concludes.
- Identity responsive continuation: account lifecycle screen now has mobile cards for account actions and recent access events; desktop tables remain. Test verifies layout markers and account data; suspension/reactivation CRUD route check passes.
- Latest verification after identity view changes: `composer test --no-interaction` passed 150 tests/731 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Vite+ scratch build passed 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-4`; optional `fontaine` notice only.
- Identity responsive continuation: account lifecycle screen shows account actions and recent account events as mobile cards; membership history now also has mobile cards while retaining desktop audit table. Membership manager uses existing responsive user cards/forms. Tests confirm page layout markers, escaping, suspension/reactivation flow, and membership assignment. Settings forms/layout already use fluid width and stacked mobile navigation.
- Latest verification after identity responsive changes: `composer test --no-interaction` passed 150 tests/734 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Vite+ scratch build passed 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-5`; optional `fontaine` notice.
- Membership history continuation: user/property/actor/change/reason rows now render mobile cards; desktop audit table remains. Membership add/change/revoke forms retain responsive stacked fields. Existing escaping regression plus focused membership assignment tests pass.
- Latest verification after account/membership responsive slice: `composer test --no-interaction` passed 150 tests/734 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Vite+ scratch build passed 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-6`; optional `fontaine` notice.
- Settings/security responsive pass: passkey list rows stack on narrow screens, wrap long names, and use named 44px remove controls; passkey registration action buttons stack/full-width on mobile. Security view regression passed.
- Latest full verification: `composer test --no-interaction` passed 150 tests/736 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Vite+ build passed 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-8`; optional `fontaine` notice.
- Auth/setup/dashboard pages checked: setup, registration, auth split, dashboard currently use responsive single-column/stacked/grid patterns; no browser/device acceptance claimed.
- IAM-001-T01b local route-matrix work completed: `PropertyAdminRouteAuthorizationTest::test_all_platform_admin_routes_deny_active_non_admin_for_all_methods` checks exact 36 `EnsurePlatformAdmin` routes and denies active non-admin across each HTTP method (36 denials + count assertion). `permission-matrix.md` includes route inventory and evidence; task marked [x] for current app-defined routes/SQLite only. T04/T05b/T06 and global IAM acceptance remain open.
- Latest full regression after route-matrix test: `composer test --no-interaction` passed 151 tests/773 assertions; Pint passed; PHPStan 0 errors; `git diff --check` passed. Composer deprecation notices come from system Composer/PHP libraries. No app code changed since Vite+ scratch build 22 modules at `/home/saidadam28/.hermes/cache/scratch/hotel-bome-responsive-build-8`.
- Dashboard continuation: replaced unconnected occupancy/arrival/departure/revenue placeholders with responsive links gated by platform-admin vs assigned-user role and an accurate implementation-status notice. Corrected false starter-kit copy that said rooms were still to be added. Added admin and non-admin shortcut visibility assertions. `php artisan test --filter='PropertyManagementTest|UserApprovalTest'`: 23 tests/98 assertions PASS; Pint PASS; PHPStan 0 errors; `git diff --check` PASS. Property/housekeeping regression: 41 tests/239 assertions PASS. No browser acceptance claimed.
- Independent IAM-001-T04 route audit found no demonstrated authorization flaw for current inventory routes. Added active housekeeping membership to `PropertyAdminRouteAuthorizationTest::test_all_platform_admin_routes_deny_active_non_admin_for_all_methods`; all 36 admin route-method denials pass (37 assertions). Updated T04 with current route evidence and explicit absence of inventory export/job/API paths; T04 remains open for complete data-path inventory and target DB review. No production authorization code changed.
- Final post-audit `composer test --no-interaction`: 151 tests/789 assertions PASS; Pint PASS; PHPStan 0 errors; `git diff --check` PASS. Composer emitted system-library PHP deprecations; commands exited 0.
- Latest dashboard update: added admin-only shortcuts for membership and account lifecycle alongside property inventory, approvals and audit. Tests assert admin sees these links and ordinary staff does not; targeted dashboard/IAM tests 3/55 PASS. Full tests 151/791 PASS; Pint/PHPStan/diff check PASS. Vite scratch build `/home/saidadam28/.hermes/cache/scratch/hotel-bome-dashboard-final-1`: 22 modules PASS.
- Continued IAM matrix/test alignment: `active-member` description now explicitly says membership alone grants no hotel data access; only housekeeping has `/my/properties` visibility. Existing `front_desk` membership denial test now asserts `/my/properties` returns no assigned property. Focused test 1/7 PASS; subsequent full suite 151/789 PASS. No change to authorization implementation.
- OPS-001-T03c health readiness correction: test-first reproduced `/operator/health` returning 200 when `rooms` schema was absent and separately when database-backed `sessions` was absent; endpoint now checks required tables from app migrations and configured session table only when `session.driver=database`, with generic response. `HealthControllerTest`: 5/18 PASS. Follow-up full suite after registration-message change: 152/793 PASS; Pint PASS; PHPStan 0 errors; `git diff --check` PASS. Vite scratch build `/home/saidadam28/.hermes/cache/scratch/hotel-bome-health-build-2`: 22 modules PASS. SQLite/Linux only; no Windows acceptance.
- Registration user-flow improvement: native HTML registration clearly states admin approval is required before sign-in; after submit, login shows pending approval message. JSON response stays HTTP 201; created user remains pending and unauthenticated. `RegistrationTest` includes page-content, redirect/flash, full login rendering, JSON and pending-state checks; `FirstAdminBootstrapTest::test_first_admin_is_created_once_and_bootstrap_locks` confirms first-admin completion flash appears on login while remaining guest; targeted registration + bootstrap tests 14/65 PASS. Full suite `composer test --no-interaction`: 153/802 PASS; Pint PASS; PHPStan 0 errors; `git diff --check` PASS. Vite build `/home/saidadam28/.hermes/cache/scratch/hotel-bome-auth-final-1`: 22 modules PASS.
- Landing page at `/` and dashboard responsive shortcuts previously built and tested; earlier scratch builds remain available. Browser visual acceptance not performed.
- Next: continue remaining ready implementation tasks; guest PII/rates/reservations, Windows pilot, signing custodian, tax/accounting and SOP acceptance remain blocked by owner/domain input or environment. Full device/MySQL/Windows acceptance still deferred.

## Previous checkpoint — post-AUD-001 / phase readiness review (historical)

- Research decisions supersede blocked status only for bounded prototype planning; they do not accept target hardware or production rollout.


- Checkout `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; HEAD `9fb323902198cc45db4271c151f29de220293568` (`feat: add audit history and account lifecycle`). Worktree clean at checkpoint. Commit includes local IAM-D5 and AUD-001 implementation/docs, 39 files. No push; `packages/flux-pro-2.20.1.zip` exists in branch ancestry and branch has no upstream. Do not rewrite history or push the commercial archive.
- Fresh verification before commit: `composer test --no-interaction` exit 0, 141 tests/667 assertions; staged `git diff --cached --check` clean. Commit completed; Git used auto-detected identity `Ubuntu <saidadam28@Saidadam-studio.fgaykgnpegvuvlpgjhq3ixzqqd.rx.internal.cloudapp.net>` (not separately approved/configured).
- Read `AGENTS.md`, workflow/state, constitution, feature register, roadmap and active OPS/IAM/AUD artifacts. No next production-code task is READY without an unresolved decision or unavailable acceptance environment. OPS-001 app-level first-admin and health slices are implemented; remaining tasks require Windows release/runtime/service wrapper, signing custody and pilot machine. IAM full permission matrix and cross-property paths require hotel SOP/decision; other product modules are gated by D-004..D-010, privacy/retention policy, pilot workflow or hardware/provider.
- No active DB, remote hosted DB, Windows target, or MySQL was inspected/changed. Local environment previously reported SQLite and an active admin account; this is not proof that hosted `hotel.saijaan.my.id` uses that SQLite file. No admin password was changed by this session.
- Next valid action: obtain owner decisions/resources that unblock a bounded feature task, or explicitly authorize safe Flux archive history remediation plus target remote before delivery. Preserve all unrelated user files; no reset/clean/stash/mass stage.

## Previous autonomous checkpoint — AUD-001 audit history viewer (historical)



- Checkout `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; base HEAD `cc78010`. Preserve all existing IAM, product docs, Flux Pro, `.env`, temporary files and unrelated local changes. No staging, commit, push, reset, clean, stash or database migration performed.
- AUD-001 artifacts: `specs/011-audit-foundation/{spec.md,plan.md,tasks.md,acceptance.md}`. Prerequisite script resolves feature dir; no `.specify/extensions.yml` hook found. Analysis mapped FR-001..FR-006 to tests/tasks and confirmed migration columns. Plan now specifies source-local filters before `UNION ALL`.
- Implemented read-only `/admin/audit` with active platform-admin route middleware, five existing sources, minimized union projection, inclusive app-timezone date filters (maximum date difference 365 days = at most 366 calendar dates), property/source filters, stable time/source/id order and fixed page size 50. Added desktop/mobile links. No new schema, writer, export or retention/purge.
- `tests/Feature/Audit/AuditHistoryTest.php`: 6 tests/75 assertions pass. Covers guest/non-admin/inactive admin, all sources and displayed source IDs, nullable actor/property, escaped names/reasons, secret-field minimization, source/property filters, paired/invalid/reversed/overlong dates, inclusive boundaries, 50-row simple pagination, source/id tie-break ordering and unchanged source rows. Initial RED found missing route; further tests found outer-UNION property filter and unpaired date range gaps, fixed with source-local predicates and `required_with`. PHPStan return shape corrected from list to keyed array.
- Full verification after latest code changes: `composer test --no-interaction` exit 0; Pint PASS, PHPStan 0 errors, PHPUnit 141 tests/667 assertions. `composer validate --no-check-publish --no-interaction` exit 0. Vite scratch build exit 0, 22 modules, output `/home/saidadam28/.hermes/cache/scratch/hotel-bome-audit-build`. `git diff --check` exit 0. System Composer emits upstream PHP 8.4 deprecation notices.
- Independent review: no Critical/Important findings. Reviewer said source IDs missing, but implementation now renders `source:source_id` and test asserts all five pairs. Reviewer noted exact pagination count and unindexed large-history ordering; exact count removed using `simplePaginate()`, remaining sort-volume risk documented until representative history is measured. T04 is complete. All AUD-001 work remains uncommitted and unstaged.
- SQLite/Linux evidence only. No MySQL, Windows UI/service/pilot, active database migration, legal/privacy retention approval, purge or exports tested/approved. No claim for those gates. Do not push: `packages/flux-pro-2.20.1.zip` remains in ancestry.

## Prior checkpoint — IAM-D5 account lifecycle and Windows/PHP baseline

- IAM-D5 local slice: admin-only suspend/reactivate with reason, immutable same-transaction audit, self/platform-admin target denial, account auth-version session snapshot and logical invalidation at next protected request. Membership assignment/change/revoke increments auth-version. Follow-up RED test found middleware checked incorrect hard-coded `login_web`; fixed with `Auth::guard()->getName()`. Independent review found remember-me recaller could restore a session after reactivation; suspend and membership changes now rotate `remember_token` atomically with auth-version. Tests cover cookie revocation, rollback and no-op behavior; server-side session rows are not physically removed.
- Added health regression for successful DB `SELECT 1` with missing required schema: generic 503, `database=ok`, `schema=unavailable`. `HealthControllerTest`: 4 tests/17 assertions.
- PHP 8.3.6 runtime cannot boot this checkout: Composer platform check fails because locked Symfony 8.1 packages require PHP `>=8.4.1`. Root `composer.json` now states `^8.4.1`; lock metadata updated without changing dependency versions. A PHP 8.3.6 standalone harness checked only MySQL SSL attribute fallback, not application compatibility.
- Windows is the only owner-confirmed OS family. Exact release/edition, runtime, service wrapper, signer and pilot machine unresolved. No Windows-host, MySQL-target, active DB migration or pilot acceptance was run.
- Do not push: commercial Flux Pro archive exists in branch ancestry.


## Previous checkpoint — IAM-D4 property membership lifecycle

- Repo `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; IAM-D4 implementation and feature docs committed locally as `b3ee640` (`feat(iam): manage audited property memberships`). Workflow state checkpoint update remains uncommitted until its own local docs commit. No push; do not push because Flux Pro archive exists in ancestry. Only `docs/AI_WORKFLOW_STATE.md` remains modified after feature commit.
- Added platform-admin-only `/admin/users/memberships` view and actions: assign, change role, revoke with required reason and append-only event audit in same transaction. Rejects self-target; assignment/change only for active non-platform-admin accounts and active properties. Revocation permits cleanup of inactive users/properties and other platform admins' legacy memberships; never changes global admin flag.
- Audit schema adds `property_membership_events` with restrictive foreign keys and SQLite/MySQL triggers rejecting update/delete. Database implementation/test coverage is SQLite only; no active database migration, MySQL DDL/trigger behavior, concurrency, or rollback on production DB verified.
- UI lists memberships for cleanup including inactive property/user and admin legacy rows; hide assignment/change actions where invalid. Banner states role profiles do not imply full permission matrix; non-housekeeping roles still denied property routes except exact implemented platform-admin routes. Membership takes effect on next property-scoped request; global session revocation across all backends is not implemented.
- Verification after final changes: `composer test` exit 0 (Pint PASS, PHPStan 0 errors, PHPUnit 123 tests/519 assertions); focused membership + migration `php artisan test --filter='UserMembershipManagementTest|PropertyMembershipEventsMigrationTest'` PASS 10 tests/69 assertions; `./node_modules/.bin/vp build --configLoader runner --outDir /home/saidadam28/.hermes/cache/scratch/hotel-bome-build` exit 0 (22 modules); `git diff --check` exit 0. Vite warns optional `fontaine` missing and external `outDir` is not emptied. Independent fresh-context review is pending.
- Updated IAM-D4 decision, task/acceptance evidence and feature register. Task IDs now unique. Full IAM permission matrix, session invalidation, cross-tenant exports/jobs/API, MySQL/concurrency, SOP, production DB remain open. Do not claim full IAM/security completion.
- Independent read-only security review completed: no confirmed implementation/security findings. One low-severity acceptance-doc count mismatch (66 vs actual 69 assertions) was confirmed and corrected. Full review covered authorization, IDOR/cross-property, self/admin target limits, audit atomicity/immutability, validation, XSS, migration portability, tests/docs.
- Commit only the verified IAM-D4 paths locally; no push.

## Previous checkpoint — IAM-D3 housekeeping access slice


- Repo `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; IAM-D3 implementation committed locally as `f9e137e` (`feat(iam): add assigned housekeeping workspace`). State checkpoint update remains uncommitted until its own local docs commit. No push; preserve unrelated files and Flux Pro archive.
- IAM-D3 is a temporary technical least-privilege default, not hotel SOP approval: platform-admin keeps existing property routes; only active-property `housekeeping` members may open their assigned room/status board and update housekeeping status. `hotel_admin`, `front_desk`, `finance`, and `operations` remain denied all property/inventory routes. Membership itself grants no other permission.
- Added `EnsurePropertyRole` route middleware; independent role-scoped `/properties/{property}/housekeeping` board and status update endpoint; `/my/properties` shows only active housekeeping assignments. Staff board query returns active rooms/status only and does not load block/history/master fields or expose admin controls. Desktop/mobile menu links to assigned workspaces.
- `PropertyRoleAuthorizationTest`: initial RED confirmed missing routes (4 errors); current focused test PASS (5 tests/30 assertions). Covers assigned property, direct cross-property board/status/foreign room denial, inactive property, invalid status transition, denied old admin board/history/block/room-create routes, block-data minimization, and scoped assignment list.
- Fresh `composer test` PASS: Pint PASS, PHPStan 0 errors, PHPUnit 113 tests/450 assertions. Fresh `git diff --check` PASS. Vite scratch build PASS (22 modules) with `--configLoader runner`, output `/home/saidadam28/.hermes/cache/scratch/hotel-bome-build`; optional `fontaine` package warning. `pnpm` absent from PATH; direct build failed due unwritable `node_modules/.vite-temp`, alternate config loader succeeded without changing permissions. Composer prints upstream PHP 8.4 deprecations.
- Independent read-only review of IAM-D3 found no confirmed issue. MySQL, concurrent membership revocation/session invalidation, full IAM role matrix, export/job/API property boundaries, real SOP, production DB not verified. Production DB unchanged. `database/database.sqlite` absent.
- Changes remain local/uncommitted on `work/hermes-full-build`; no push. Preserve unrelated changes and Flux archive.

## Previous checkpoint — IAM-001 account approvals

- Local approval slice commit `6b78454` and checkpoint `97ed2c2`; do not push due commercial Flux Pro archive in ancestry. A separate read-only IAM approval review found no confirmed defects; SQLite 16 tests/60 assertions passed. MySQL trigger/locking remains unverified.

## Historical checkpoint — IAM-001 account approvals (2026-10-18)

- Workspace `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; current HEAD `6b78454` (`feat(iam): add audited pending account approvals`). IAM approval slice is committed locally; not pushed.
- Added additive migration `2026_10_18_000003_create_user_access_tables.php` for unique `property_user` memberships and immutable `user_access_events` approval/rejection history. SQLite migration rollback verified; role/event values constrained; MySQL triggers/locking unverified.
- Added platform-admin-only `/admin/users` pending queue, approve and reject actions. Approval requires pending account, active property, IAM-D1 profile and reason; membership, active status and append-only audit are one transaction. Rejection requires reason and audits status change.
- Membership/profile is not a permission grant. Existing property/inventory routes remain platform-admin-only; no hotel-admin delegation, staff permission, membership editing/revocation, suspend/reactivation, or concurrent-session invalidation is implemented.
- Fortify replacement registration explicitly saves pending status and uses Laravel throttle 5/minute/IP; pending/rejected accounts cannot log in. Existing `active` middleware ends a non-active user's current session on that user's next protected request; it does not prove revocation of every session.
- Independent review found no confirmed vulnerability. Reviewer requested a direct non-admin POST-approval regression; added and verified. MySQL DDL/trigger/partial-failure behavior remains unverified without target DB.
- Verification after review fix: `composer test` PASS — Pint PASS, PHPStan 0 errors, PHPUnit 108 tests/420 assertions. Target IAM/registration tests 23 tests/89 assertions; IAM-only `UserApprovalTest|UserAccessMigrationTest` 16 tests/60 assertions. Migration rollback and failed-audit rollback covered. Vite scratch build PASS (22 modules); `git diff --check` PASS. Font plugin warns optional `fontaine` missing; Composer emits system PHP 8.4 deprecations.
- Production database not changed. `database/database.sqlite` is absent, so local migration status cannot be reported. Do not push due Flux Pro archive in ancestry.
- Keep T01b full role/action matrix and full membership/session revocation incomplete; IAM-D3 only grants one temporary housekeeping route/action slice.

- Workspace `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; current HEAD `6b78454` (`feat(iam): add audited pending account approvals`). IAM approval slice is committed locally; not pushed.
- Added additive migration `2026_10_18_000003_create_user_access_tables.php` for unique `property_user` memberships and immutable `user_access_events` approval/rejection history. SQLite migration rollback verified; role/event values constrained; MySQL triggers/locking unverified.
- Added platform-admin-only `/admin/users` pending queue, approve and reject actions. Approval requires pending account, active property, IAM-D1 profile and reason; membership, active status and append-only audit are one transaction. Rejection requires reason and audits status change.
- Membership/profile is not a permission grant. Existing property/inventory routes remain platform-admin-only; no hotel-admin delegation, staff permission, membership editing/revocation, suspend/reactivation, or concurrent-session invalidation is implemented.
- Fortify replacement registration explicitly saves pending status and uses Laravel throttle 5/minute/IP; pending/rejected accounts cannot log in. Existing `active` middleware ends a non-active user's current session on that user's next protected request; it does not prove revocation of every session.
- Independent review found no confirmed vulnerability. Reviewer requested a direct non-admin POST-approval regression; added and verified. MySQL DDL/trigger/partial-failure behavior remains unverified without target DB.
- Verification after review fix: `composer test` PASS — Pint PASS, PHPStan 0 errors, PHPUnit 108 tests/420 assertions. Target IAM/registration tests 23 tests/89 assertions; IAM-only `UserApprovalTest|UserAccessMigrationTest` 16 tests/60 assertions. Migration rollback and failed-audit rollback covered. Vite scratch build PASS (22 modules); `git diff --check` PASS. Font plugin warns optional `fontaine` missing; Composer emits system PHP 8.4 deprecations.
- Production database not changed. `database/database.sqlite` is absent, so local migration status cannot be reported. Do not push due Flux Pro archive in ancestry.
- Next: commit only this reviewed IAM slice locally. Keep T01b permission mapping and full membership/session revocation blocked pending policy/tests.

## Previous checkpoint — PROP-001 interim room operations and history


- Workspace `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`; current HEAD `048c361` (`feat(properties): add room operations history and board`), following base `5c588c2` (`feat(properties): add property inventory masters`). Room-operations slice committed locally; no push performed.
- Committed base `5c588c2` plus `fc89bbb` provide admin-only property/outlet/room-type/room master CRUD and offline auth behavior. No user-property membership yet; interim access remains platform-admin only.
- This slice adds provisional `PROP-D3` housekeeping semantics (`dirty`/`clean`/`inspected`), append-only status events, additive block/cancellation event tables with migration backfill, room status/block board, filters, create/cancel actions, and property-scoped paginated history.
- Review follow-up fixed nonpersistent old room-type selection and ambiguous joined `property_id` from prior slice. This slice fixed service/schema mismatch by storing block cancellation in append-only `room_block_cancellations`; status history and cancellation remain linked with restrictive/composite FKs. Migration/backfill/rollback covered by SQLite test.
- Latest verification after review fixes: `composer test` PASS — Pint PASS, PHPStan 0 errors, PHPUnit 91/91 tests and 349 assertions. Vite scratch build PASS (22 modules); `git diff --check` PASS. Composer prints upstream PHP 8.4 deprecation notices; Vite notes optional `fontaine` missing. Build output `/home/saidadam28/.hermes/cache/scratch/hotel-bome-build` outside repo.
- Independent reviews found no remaining blocker: custom composite FK name `rbc_block_fk` stays under MySQL identifier limit; SQLite/MySQL triggers reject update/delete on all operational event tables; regression verifies an uncancelled active block without child FK; expired uncancelled blocks remain in historical list with explicit label; duplicate open task rows removed. Focused recheck passed 20 tests/99 assertions. MySQL behavior/concurrency remain unverified because target DB is unavailable.
- `specs/004-property-guest-foundation/tasks.md`: T01b2/T02b2/T03b2/T04b2 local SQLite work complete. T04b3 target-DB locking/concurrency remains BLOCKED_EXTERNAL (no approved MySQL/pilot DB). Reservation availability/conflict, staff membership and guest PII remain out of scope/gated by D-005, IAM matrix and D-007.
- Feature 005 reservation/rates tasks remain blocked on D-004/D-005; OPS target deployment waits on Windows/signing/pilot; IAM permission tasks wait on role/action mapping. No next production-code task is currently ready without those decisions/environments.
- Do not push: ancestry contains commercial `packages/flux-pro-2.20.1.zip`; preserve it locally. Do not reset, clean, stash, or stage unrelated files. Slice `048c361` is local-only; do not push.
- Next: no further implementation task in the active feature is READY. Keep PROP-001 T04b3 BLOCKED_EXTERNAL until approved MySQL/pilot DB exists; continue rates/reservation, membership and PII only after their documented decision gates clear. Slice commit `048c361` remains local; do not push because Flux Pro archive exists in ancestry.

---

---


## Active handoff override — 2026-10-18

- Owner requested autonomous planning/research/implementation/testing and periodic pushes; see `docs/HERMES_AUTONOMOUS_BUILD.md` for the complete execution mandate. This supersedes the baseline-only handoff below on the integration branch.
- Integration branch: `work/hermes-full-build`, starting at `ca295269766b482df89b78358a3c154a3f159c3d` plus the autonomous mandate commit. Use the actual current remote tip and record every verified checkpoint; do not require HEAD to stay at the starting SHA.
- Checkout for current unattended VPS run: `/home/saidadam28/workspace/hotel_bome_workflow`. Preserve pre-existing baseline evidence, Flux Pro archive and `tmp/`.
- Latest verified slice: branch `work/hermes-full-build`, HEAD `220e61d266758da313c043953f6cd1bad31c6f24` matches `origin/work/hermes-full-build` (verified with `git ls-remote`); PHP 8.4.26; runtime paths owned by `saidadam28`. Preserved baseline evidence, Flux archive and `tmp/`.
- Current run: `composer test` exit 0; Pint PASS, PHPStan 0 errors, PHPUnit 36/36 tests and 90 assertions PASS. Composer emits PHP 8.4 deprecation notices from system Composer dependencies. `bootstrap/cache`, `storage/framework`, `storage/logs` ownership/modes checked; all owned by `saidadam28`, writable mode 775. No `.env` contents read.
- Read OPS-001 and IAM-001 active artifacts. No implementation task presently marked READY: OPS-001 T01 needs Windows/runtime/signing/pilot decisions; IAM-001 T01 needs role/approval transitions, and T02-T06 depend on it. Remaining feature queues are explicitly gated by D-004..D-010 or per-feature SOP/pilot. Do not create code slice until readiness artifacts identify one.
- Existing IAM T03 login fix remains verified; full suite still green. Next action: owner/requirements planning must make a bounded task READY (e.g. role matrix / bootstrap policy); continue with independent READY work after task artifacts are updated.
- IAM pending login regression addressed: Fortify authentication rejects non-active accounts; registration now uses `App\\Http\\Controllers\\Auth\\PendingRegisteredUserController` so pending registrations are stored without establishing a session. `routes/web.php` shadows Fortify POST endpoint; no vendor edits retained.
- `php artisan test --filter=RegistrationTest`: 4 tests/12 assertions PASS. Full `composer test`: Pint PASS, PHPStan 0 errors, PHPUnit 36/36 tests, 90 assertions PASS; Composer emits PHP 8.4 deprecation notices from system Composer libraries. `git diff --check` PASS before state update.
- IAM remains incomplete: admin approval/bootstrap UI, membership/tenant enforcement, and audit tasks not implemented. Next: inspect readiness and continue independent foundation slices. Windows/LAN/hardware/signing acceptance stays BLOCKED_EXTERNAL.
- Worktree also contains user-owned `docs/product/BASELINE_EVIDENCE.md` and `packages/flux-pro-2.20.1.zip`; never stage either. No commit/push recorded in this checkpoint.

## Historical unattended run — 2026-10-18

- Checkout `/home/saidadam28/workspace/hotel_bome_workflow`; branch `work/hermes-full-build`, HEAD `0fad6c3`; `git ls-remote origin refs/heads/work/hermes-full-build` returned same SHA. No tracked code changes existed at start. Preserved untracked owner files `docs/product/BASELINE_EVIDENCE.md` and `packages/flux-pro-2.20.1.zip`; neither staged.
- Runtime ownership check: `bootstrap/cache`, `storage/framework`, `storage/logs` all `saidadam28:saidadam28`, mode 775; `.phpunit.result.cache` owner `saidadam28`, mode 644. `.env` not read.
- `composer test` exit 0: `config:clear` succeeded; Pint passed; PHPStan 0 errors; PHPUnit 36/36 tests, 90 assertions. System Composer dependencies emitted PHP 8.4 deprecations; no failure.
- Re-run in this continuation: `composer test` exit 0; Pint PASS, PHPStan 0 errors, PHPUnit 36/36 tests and 90 assertions PASS. Runtime ownership/modes reconfirmed; `.env` not read. `git diff --check` exit 0. No code implementation READY: OPS-001 still requires Windows/runtime/signing/pilot selections; IAM depends on bootstrap and its permission matrix. Existing untracked `docs/product/DECISION_LOG.md` is referenced by IAM spec but not tracked; preserve owner files, so do not stage these planning edits partially or commit a dangling reference. Remote branch remains at `0fad6c3bb7ebd4c65df676aa0f7bebc6c079e303`; no commit/push this continuation.
- IAM discovery advanced: `docs/product/DECISION_LOG.md` records IAM-D1 conservative, provisional approval transitions, role boundaries and deny-by-default; `specs/003-identity-property-access/spec.md` adds security acceptance; tasks splits IAM-001-T01 into discovery complete and module-specific permission map still blocked. Implementation remains gated by OPS-001 bootstrap primitive and exact per-module permission mapping; no code changes made.
- `git diff --check` initially flagged an extra blank line at EOF in IAM spec; removed. Final check result recorded after rerun. No commit/push this run; planning changes remain local pending final consistency/readiness analysis. External Windows installer, signing and hotel pilot remain BLOCKED_EXTERNAL.
- Continuation verification: checkout `/home/saidadam28/workspace/hotel_bome_workflow`, branch `work/hermes-full-build`; HEAD and `origin` tip both `0fad6c3bb7ebd4c65df676aa0f7bebc6c079e303` at initial check. Runtime directories remain owned by `saidadam28` mode 775; `.phpunit.result.cache` owned by `saidadam28` mode 644; PHP 8.4.26. `.env` not read. Re-ran `composer test`: exit 0, Pint PASS, PHPStan 0 errors, PHPUnit 36/36 and 90 assertions; Composer emitted PHP 8.4 deprecation notices from system libraries. `git diff --check` exit 0. Reviewed queues 001–010: no implementation task READY; OPS-001 remains gated on target/runtime/signing/pilot; IAM-001 T01b–T06 depend on specific permission mapping and bootstrap. Preserved owner files `docs/product/BASELINE_EVIDENCE.md`, untracked `docs/product/DECISION_LOG.md`, `packages/flux-pro-2.20.1.zip`, and `tmp/`; no commit/push this continuation.

## Historical repository and branch snapshot — superseded 2026-10-25

- Local project: `D:\Project\hotel_new`
- Remote: `https://github.com/mryunkaka/hotel_bome.git`
- Existing workflow branch remains `chore/spec-kit-workflow` at base `a2ce0c13bd002679a1b9f08bc0c9135d2e10a7db`; `main` is untouched.
- Planning artifacts are pushed on `docs/pms-project-plan`; package commit: `69a7faac831a8014a792c695c326a82b66110369`.
- The active read-only task branch is `feat/disc-001-baseline`, based on the latest pushed tip of `docs/pms-project-plan`; the exact base SHA is recorded in the dispatch message.
- Future implementation must use separate feature/implementation branches from the latest approved planning SHA; never work on main and never force-push.

## Owner decisions confirmed 2026-10-17

- One installation manages multiple hotels.
- Staff access the local server from multiple PCs over the hotel LAN; allowed subnet/firewall is site-configured and must be pilot-tested.
- Self-registration remains enabled, but new accounts remain pending and cannot log in until admin approval.
- First admin is created through a local one-time setup wizard; the wizard is locked permanently after successful creation.
- UI stack remains Laravel 13, Livewire 4, Tailwind CSS 4 and Flux Pro 2.20.1; do not use Filament.

## Historical planning package snapshot

- Product requirements, scope: `docs/product/PRD.md`
- Architecture/domain: `docs/product/ARCHITECTURE.md`, `docs/product/DOMAIN_MODEL.md`
- Roadmap and module/task IDs: `docs/product/ROADMAP.md`, `docs/product/FEATURE_REGISTER.md`, `docs/product/FEATURE_SPEC_MAP.md`
- Open business decisions: `docs/product/OPEN_DECISIONS.md`
- Master tests/deployment/DoD: `docs/product/TEST_PLAN.md`, `docs/product/DEPLOYMENT_PLAN.md`, `docs/product/DEFINITION_OF_DONE.md`
- Ten feature packages, each with spec/plan/tasks/acceptance: `specs/001-discovery-baseline/` through `specs/010-connectors-release-acceptance/`.
- No feature implementation has started in this planning package.

## Open gates

- D-004: tax, jurisdiction, currency, invoice numbering and rate rules (hotel owner/accountant).
- D-005: cancellation, no-show, deposit, room move/share, waitlist, overbooking, checkout exceptions (hotel SOP).
- D-006: lock/reader/card model, vendor SDK and redistribution rights; physical pilot device.
- D-007: guest PII fields, consent, retention, export and permitted central sync.
- D-008: licensing/offline grace/signing key custody/support; Flux commercial entitlement for customer distribution.
- D-009: which connector/accounting/POS/OTA/QR/guest features are release-critical and provider choices.
- D-010: approval thresholds, tender/cash variances, invoice correction, close/reopen and reporting SOP.
- Windows target version/runtime/service/signing resources are not yet chosen or verified.

## Historical Hermes handoff — DISC-001 only, superseded by owner mandate

Only `DISC-001` read-only baseline evidence is READY. See `specs/001-discovery-baseline/tasks.md` and `acceptance.md`.

Handoff instructions:

1. Use checkout `/workspace/hotel_bome_workflow`; explicitly `cd` there. Use `/opt/data/.local/bin/specify` if CLI is needed. Hermes managed Spec Kit integration remains prohibited because `multi_install_safe: false`; use installed global skills and shared Bash adapters only.
2. Confirm branch `feat/disc-001-baseline`, base SHA recorded in the task handoff, and clean worktree before any check.
3. Run only `composer test` for the baseline; do not change app code, dependencies, workflows, `.specify/integration.json`, or other feature artifacts.
4. Update only `docs/product/BASELINE_EVIDENCE.md` with actual test command, environment, exit code, counts, sanitized result and any blocker. Never include secrets.
5. Review and commit only that file on `feat/disc-001-baseline`; push only that task branch. Report exact SHA and stop because implementation tasks remain gated.

## User-owned local changes to preserve

The Windows checkout has pre-existing uncommitted changes: `composer.lock`, `packages/composer.json`, deletion of `packages/flux-pro-2.20.0.zip`, untracked `packages/flux-pro-2.20.1.zip`, and `tmp/pdfs/`. Never stage, read archive contents, alter, or push these files. They are not present in the planning commit. The Flux artifact/license distribution path must be resolved before a dependency task.

## Hermes environment

- Hermes Agent 0.21.6; Spec Kit CLI 1.2.1 and uv 0.13.1 under persistent `/opt/data`.
- Project checkout: host `/home/saidadam28/workspace/hotel_bome_workflow`, container `/workspace/hotel_bome_workflow`.
- Ten Spec Kit skills active under `/opt/data/skills/speckit-*`; global Hermes managed integration must not be installed in project metadata.
- SSH host fingerprint was verified from the VPS console in earlier session. Use configured SSH key auth; never echo credentials.

## Historical verification state — superseded by current progress snapshot

- Initial repository check: branch `chore/spec-kit-workflow`, HEAD `a2ce0c1`, synced with `origin/chore/spec-kit-workflow` at check time.
- `specify check` local: PASS (CLI available; Codex CLI detected).
- `git diff --check`: run before planning commit; record result after validation.
- Application tests, static analysis, MySQL, Windows installer, LAN scan, hardware, backup/restore, update and final audit: NOT RUN. Do not report PASS.
- Hermes readiness/read-only acceptance test succeeded in earlier workflow session; this does not verify the new planning branch or application tests.

## Completion and audit protocol

Hermes may continue each child task only when its feature gate is READY, run and record tests, and commit/push only the explicitly handed feature branch. It may work through independent ready slices without asking Codex to review each ordinary task. It must stop at unresolved business/security/licensing/device/OS decisions or unavailable access. When Hermes reports completion/blocker, Codex fetches the exact branch, audits diff and acceptance evidence independently, reports PASS/FAIL/BLOCKED, and returns concrete defects to Hermes for correction. No final audit has been performed.
