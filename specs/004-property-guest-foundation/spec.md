# Feature Specification: Property Inventory and Guest Privacy

**Feature IDs**: PROP-001, GUEST-001 | **Status**: Planning baseline

## Ringkasan

Membuat property/outlet/room type/room/status board untuk beberapa hotel, serta profil guest dengan minimisasi PII dan property-scoped access.

## User Scenarios & Testing

### User Story 1 — Menyiapkan inventaris dan guest yang aman (P1)

Sebagai property admin/resepsionis, saya ingin mengelola kamar serta menemukan guest yang diizinkan pada hotel aktif.

Acceptance scenarios:
1. Given admin hanya assigned hotel A, when membuka room board, then hanya data A tampil.
2. Given room unavailable block, when menghitung availability untuk tanggal terkait, then kamar dikecualikan dan histori block tersimpan.
3. Given role tidak memiliki izin guest PII, when melihat guest atau export, then identifier/dokumen tidak tersedia.
4. Given master sudah direferensikan oleh stay/history, when disable dilakukan, then histori tetap terbaca dan delete fisik ditolak.

## Functional Requirements

- FR-001: Property/outlet/room type/room MUST berada dalam property boundary dan mengikuti assignment.
- FR-002: Status kamar, maintenance block dan unavailable dates MUST memiliki histori.
- FR-003: Guest search dan duplicate matching MUST scoped dan tidak auto-merge irreversibly.
- FR-004: Field PII/documents MUST mengikuti minimization, permission, consent and retention policy.
- FR-005: Access/change/export PII MUST diaudit; secrets and unnecessary PII MUST tidak muncul di log.

## Key Entities

Property, outlet, room type, room, room status history, room block, guest, guest document, consent, audit event.

## Success Criteria

- SC-001: Cross-property direct access ditolak untuk inventaris dan guest.
- SC-002: Room block mengubah availability tanpa menghapus histori.
- SC-003: Guest PII tidak tampil kepada role/export yang tidak diizinkan.
- SC-004: Merge/retention menjaga business history.

## Dependencies and readiness

D-001 multi-hotel dan IAM-001 design. Property slice **READY for design**; guest PII implementation **BLOCKED:D-007** sampai privacy/consent/retention disetujui.
