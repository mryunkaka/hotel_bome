# Acceptance: ENG-001 Engineering Work Orders

## Local acceptance

- [x] Additive schema and same-property room/task constraints verified; migration rollback test passes.
- [x] Work order create/assign/start/resolve/cancel lifecycle and immutable history tested.
- [x] Active property and operations membership enforced; cross-property IDs rejected.
- [x] Escaped text, status filter, pagination, responsive UI and validation tested.
- [x] Resolving work order leaves room status events unchanged.
- [x] Asset register create/edit/deactivate, active/inactive filter, property-scoped event history and cross-property work-order asset rejection tested.
- [x] Unknown asset and work-order `status` filters are rejected by server validation; work-order `status=` All option remains accepted.
- [x] Read-only property-scoped work-order detail/history page reachable from mobile and desktop lists; cross-property direct detail denied (`WorkOrderTest`).
- [x] Focused `EngineeringAssetTest|WorkOrderTest`: 23 tests/96 assertions; fresh full `composer test --no-interaction`: 190 tests/955 assertions; Pint 102 files; PHPStan 0 errors.
- [x] Scratch Vite build: 22 modules. Composer dependency deprecation notices remain; `git diff --check` to verify after docs update.

## External acceptance

- [ ] Property-manager review of provisional ENG-D1.
- [ ] MySQL concurrency and target schema validation.
- [ ] Browser, Windows/LAN and pilot acceptance.

Local verification is not approval for production rollout.
