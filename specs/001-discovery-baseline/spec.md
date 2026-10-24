# Feature Specification: Discovery and Baseline Evidence

**Feature ID**: DISC-001
**Created**: 2026-10-17
**Status**: Planning baseline

## Ringkasan

Catat fakta repository yang diverifikasi, keputusan pemilik, serta blocker bisnis/teknis sebelum modul PMS dikembangkan.

## User Scenarios & Testing

### User Story 1 — Menyiapkan baseline yang dapat dipercaya (P1)

Sebagai pemilik produk, saya ingin fakta dan pertanyaan bisnis terpisah agar asumsi tidak menyamar sebagai keputusan.

**Independent Test**: Bandingkan laporan baseline dengan Git, manifest, source terpilih, dan command yang dijalankan.

**Acceptance Scenarios**:

1. Given repository dan branch terverifikasi, when baseline dibuat, then branch, commit, status, stack dan source yang diperiksa tercatat.
2. Given keputusan pemilik dan pertanyaan terbuka, when task readiness ditentukan, then setiap task berstatus READY atau BLOCKED dengan rujukan.
3. Given kebutuhan menjalankan check, when Hermes menjalankan command, then command, environment, exit code dan gap dicatat tanpa secret.

### Edge cases

- Working tree memuat perubahan lokal/proprietary: tidak boleh ikut stage/commit.
- Command gagal atau environment tidak tersedia: status tetap belum lulus dan blocker dicatat.
- Sumber legacy bertentangan dengan pilihan baru: keputusan owner/constitution lebih dahulu, konflik dicatat.

## Functional Requirements

- **FR-001**: Laporan MUST menyatakan repository, branch, commit dasar, perubahan lokal yang dikecualikan dan source yang diperiksa.
- **FR-002**: Decision log MUST membedakan keputusan pemilik, asumsi sementara, dan open question.
- **FR-003**: Setiap fitur MUST memiliki dependency, gate, acceptance dan task IDs yang dapat ditelusuri.
- **FR-004**: Hermes MUST mencatat hasil check aktual serta tidak mengklaim lulus jika command belum dijalankan.
- **FR-005**: Perubahan discovery MUST tidak mengubah source/dependency aplikasi atau menyertakan secret/data customer.

## Key Entities

Decision, evidence record, feature, task, acceptance criterion, blocker.

## Success Criteria

- **SC-001**: Semua task aktif dapat ditelusuri ke spec, acceptance, dependency dan bukti.
- **SC-002**: Keputusan D-001 multi-hotel, D-002 LAN, dan D-003 self-registration pending plus wizard admin tercatat.
- **SC-003**: Tidak ada perubahan app/secret pada commit baseline.
- **SC-004**: Semua hasil test memiliki command, exit status dan environment.

## Dependencies and readiness

Read-only baseline evidence READY. Wawancara SOP, tax/accounting, privacy, hardware, licensing dan release priority tetap menunggu pihak terkait. Lihat `docs/product/OPEN_DECISIONS.md`.
