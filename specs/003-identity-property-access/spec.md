# Feature Specification: Identity and Property Authorization

**Feature ID**: IAM-001 | **Created**: 2026-10-17 | **Status**: Planning baseline

## Ringkasan

Sediakan akun lokal, self-registration pending approval, one-time first-admin wizard, role dan membership pada beberapa hotel.

## User Scenarios & Testing

### User Story 1 — Admin mengendalikan akun dan property access (P1)

Sebagai admin, saya ingin menyetujui akun dan menetapkan properti/role agar staf hanya melihat data yang menjadi tugasnya.

Independent test: buat admin awal, register staf kedua, buktikan pending tidak bisa login, approve pada satu property, lalu uji IDOR lintas property.

Acceptance scenarios:
1. Given registrasi mandiri, when user submit, then akun pending dan login ditolak sampai approval admin.
2. Given admin approved, when membership hanya mencakup hotel A, then hotel B selalu ditolak via UI, URL, action, export dan API.
3. Given first-admin wizard selesai, when request wizard dikirim ulang, then operasi ditolak.
4. Given role/membership berubah atau dicabut, when akses berikut dilakukan, then perubahan berlaku dan tercatat audit.

## Functional Requirements

- FR-001: Self-registration MUST berakhir pending approval dan tanpa akses property.
- FR-002: First-admin bootstrap MUST lokal, one-time, tanpa default password, dan terkunci sesudah berhasil.
- FR-003: Admin MUST dapat assign role dan satu atau lebih hotel memberships.
- FR-004: Server MUST memeriksa authorization pada setiap query/action/job/export/API, bukan hanya menyembunyikan menu.
- FR-005: Approval, rejection, role, membership dan access revoke MUST diaudit.

## Key Entities

User, role, permission, property membership, registration approval, audit event.

## Success Criteria

- SC-001: Pending user tidak dapat login dan tidak punya akses data.
- SC-002: Multi-property membership bekerja dan tenant isolation lulus semua direct access tests.
- SC-003: First-admin hanya bisa dibuat satu kali.
- SC-004: Approval/revoke memiliki audit evidence tanpa menyimpan secret.

## Dependencies and readiness

D-001 multi-hotel, D-002 LAN, D-003 self-registration pending dan first admin via one-time wizard sudah diputuskan pemilik. IAM-D1 dalam `docs/product/DECISION_LOG.md` merekam default sementara untuk status/approval transitions dan role profile boundaries. Ini bukan persetujuan SOP hotel. Permission matrix tindakan per modul tetap deny-by-default dan harus ditetapkan ketika modul terkait ada. IAM implementation bergantung pada bootstrap primitive OPS-001; validation install/Windows tetap BLOCKED_EXTERNAL.

### Default sementara IAM-D1

Akun registrasi baru berstatus pending, tanpa membership dan tidak dapat login. Hanya admin berwenang dapat approve/reject. Approval harus sekaligus memberi membership dan role terbatas; role/property scope wajib dicek server-side pada setiap request. Penolakan, suspend, perubahan role atau membership harus tercatat audit. IAM-D5 menggunakan `auth_version` untuk menolak sesi web lama pada protected request berikutnya setelah perubahan membership atau suspension; reactivation memerlukan login baru. Cookie/record sesi fisik tidak langsung dihapus. Self-approval/self-escalation dilarang. Peran lintas modul memakai deny-by-default sampai permission mapping spesifik disetujui.

Lihat `docs/product/DECISION_LOG.md` untuk provenance, pembatasan, dan risiko keputusan sementara.

#### Acceptance tambahan

5. Given pending registration, when admin tidak memiliki scope pengelolaan akun pada property target, then approval ditolak dan tidak mengubah status atau membership.
6. Given membership berubah/dicabut atau akun suspended, when an old web session makes its next protected request, then `auth_version` mismatch logs it out. Physical session record/cookie deletion is not promised.
7. Given user meminta action yang belum punya permission mapping, then server menolak akses secara default.
8. Given admin approval, when actor attempts self-approval or assigning a privilege beyond their scope, then the operation is denied without creating membership.
