# Implementation Decisions

Project: Sistem Pusat Mahera Media & CRM
Date: 2026-05-23

## Decisions

### 1. Start SPMM as the PMB/CRM center

Use Laravel, Filament, Blade, and Livewire for the SPMM MVP. SPMM is the central PMB/CRM system for lead capture, payment registration, student onboarding, PDDIKTI preparation, and export.

SIAKAD and LMS are separate systems. They may be prototyped in the same local workspace while the product is still being shaped, but their production direction is separate application, separate domain/subdomain, and explicit integration API.

Target system ownership:

- SPMM owns PMB, CRM, registration invoice, lead lifecycle, student onboarding, and PDDIKTI readiness.
- SIAKAD owns academic terms, courses, classes, schedules, KRS, KHS, grades, transcripts, and active student academic lifecycle.
- LMS owns learning modules, course materials, assignments, submissions, attendance learning activity, and online class delivery.

SPMM should not become the long-term place for full SIAKAD/LMS operations. It only needs integration status, data sync controls, and links into those systems.

### 2. Use a mock payment provider first

Build the payment abstraction and a mock provider before integrating Midtrans, Xendit, or Tripay. This allows the lead-to-invoice-to-paid workflow to be tested without external dependency risk.

### 3. Treat `regenerated` as history, not invoice status

Invoice statuses should remain operational: `pending`, `paid`, `expired`, `cancelled`.

Regeneration is represented by a new invoice pointing to `regenerated_from_invoice_id`. The UI can label this history as regenerated.

### 4. Normalize enrollment statuses

Use these statuses for MVP:

- `calon_mahasiswa`
- `menunggu_pemberkasan`
- `menunggu_pemutakhiran`
- `proses_pemutakhiran`
- `mahasiswa_aktif`

This resolves the naming conflict between `menunggu_pemutakhiran`, `menunggu_pemberkasan`, and `calon_mahasiswa_lunas`.

### 5. Lock by business status, not only by `locked_at`

`locked_at` records when the lock happened, but authorization should check payment and enrollment status too. This avoids data becoming editable if a timestamp is missing.

### 6. Use explicit policies for every sensitive model

Laravel policies should be created for:

- Campus
- StudyProgram
- ClassTrack
- FeeScheme
- Lead
- Invoice
- StudentProfile
- StudentNumber
- User

### 7. Store webhook payloads before processing

Every payment webhook should create a `payment_events` row first, then validate and process. This supports auditability, duplicate handling, and debugging.

### 8. Keep WhatsApp delivery async

WhatsApp notifications should be queued. The public registration and webhook requests should not block on provider latency.

## First Engineering Tasks

When Composer/PHP project tooling is available, start with:

1. Create Laravel project.
2. Configure database and environment.
3. Install Filament.
4. Create auth roles and campus scoping.
5. Generate migrations and models for master data.
6. Add seeders for first Super Admin and sample campus.
7. Build Filament resources for master data.

## Known Setup Blocker In Current Workspace

Composer is not currently available in this environment, so the Laravel project cannot be scaffolded locally yet.
