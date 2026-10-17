# Implementation Plan: Rates, Availability, and Reservations

Feature IDs: RATE-001, RES-001 | Branch: feat/rates-reservations

Do not finalize rate/tax columns until D-004 signoff. Define a single availability service used by board, reservation create/edit and reports; enforce transactional capacity checks. Persist quote snapshots for confirmed bookings. Group/walk-in and cancellation are independent tasks only after D-005. Use decimal currency math and test boundary dates, timezone, concurrency, duplicate requests, tenant policies and historical snapshot stability.

Constitution: local-first availability; no silent overbooking; approved policy only; history preserved; property isolation across search/selectors/actions.
