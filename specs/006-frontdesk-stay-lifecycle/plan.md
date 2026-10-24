# Implementation Plan: Front Desk and Stay Lifecycle

Feature ID: FO-001 | Branch: feat/fo-001-frontdesk-stay

Design a server-authoritative state machine for arrival → checked-in → in-house → checked-out. Use transaction locks/uniqueness for room occupancy, explicit assignment events and property authorization. Keep folio postings immutable and separate hardware issuing from operational stay state. UI pages must cover lists/filters, empty/error states, validation and role actions. Target DB must be tested for concurrency.

Constitution: LAN-local, no WAN dependency; role/property isolation; retain history; no direct physical key claim before hardware tests.
