# Technical Specification

Project: Sistem Pusat Mahera Media & CRM
Version: 1.0
Date: 2026-05-23
Status: Draft siap implementasi MVP

## Product Goal

Sistem Pusat Mahera Media & CRM adalah platform terpusat untuk menangkap, mengelola, mendistribusikan, menagih, dan memutakhirkan data calon mahasiswa sampai menjadi mahasiswa aktif yang siap diekspor untuk kebutuhan PDDIKTI/Neo Feeder.

Platform ini menjadi single source of truth untuk:

- Lead dari portal publik, web kampus mitra, iklan Meta/Google, dan input manual.
- Follow-up PMB.
- Invoice pendaftaran dan status pembayaran.
- Kedaluwarsa dan generate ulang tagihan.
- Pemberkasan data calon mahasiswa.
- Pemutakhiran data akademik dan penerbitan NIM.

## MVP Scope

### Included

- Login dashboard internal.
- Role Super Admin, Koordinator PMB, dan Staff PMB.
- Master data kampus, program studi, jalur kelas, dan komponen biaya.
- Form pendaftaran tahap 1.
- Invoice otomatis setelah submit tahap 1.
- Callback/webhook payment gateway.
- Status invoice: pending, paid, expired, regenerated.
- WhatsApp notification hook untuk invoice dan reminder.
- Queue pool per kampus.
- Distribusi lead manual oleh Koordinator PMB.
- Dashboard Staff PMB untuk follow-up lead.
- Tombol generate ulang tagihan.
- Form pemberkasan tahap 2 setelah pembayaran lunas.
- Lifecycle calon mahasiswa sampai mahasiswa aktif.
- Lock data untuk Staff PMB setelah lunas.
- Verifikasi pemutakhiran dan input NIM oleh Super Admin.
- Export CSV/XLSX data mahasiswa aktif.

### Excluded

- Native mobile app.
- Auto-routing lead berbasis algoritma kompleks.
- Integrasi langsung Neo Feeder full API.
- Multi-payment gateway.
- Advanced marketing attribution.
- AI chatbot PMB.
- Sistem akademik lengkap setelah mahasiswa aktif.
- Akuntansi dan rekonsiliasi keuangan mendalam.

## Recommended Stack

- Backend/dashboard: Laravel 11/12, FilamentPHP 3/4, Laravel Sanctum.
- Public frontend: Laravel Blade + Livewire.
- Queue/scheduler: Laravel Queue, Laravel Scheduler, Supervisor.
- Database: PostgreSQL untuk produksi; MySQL masih dapat diterima.
- Cache/queue: Redis jika tersedia.
- Web server: Nginx + PHP-FPM.
- Hosting: VPS Hostinger KVM 2/KVM 4.

## Main Components

- Core CRM/ERP: dashboard internal, database pusat, API ingestion, payment orchestration, lead assignment, lifecycle PDDIKTI.
- Portal Kampus Media: pencarian kampus, filter biaya, detail kampus/prodi, CTA pendaftaran.
- Web Kampus Mitra multi-tenant: subdomain default, custom domain opsional, form pendaftaran kampus tertentu, tampilan biaya transparan.

## Tenant Resolution

Setiap request publik menentukan kampus dengan prioritas:

1. `custom_domain`
2. `subdomain`
3. query parameter `kampus`

Jika tenant tidak ditemukan, sistem menampilkan fallback page.

## Roles

| Capability | Super Admin | Koordinator PMB | Staff PMB |
| --- | --- | --- | --- |
| Kelola semua kampus | Yes | No | No |
| Kelola kampus wilayah sendiri | Yes | Yes | No |
| Kelola user | Yes | Staff sendiri | No |
| Lihat lead | Semua | Kampus/wilayah sendiri | Assigned only |
| Ambil lead dari queue pool | Yes | Yes | No |
| Assign lead ke staff | Yes | Yes | No |
| Follow-up lead | Yes | Yes | Assigned only |
| Generate ulang tagihan | Yes | Yes | Assigned only |
| Edit data lead sebelum lunas | Yes | Yes | Assigned only |
| Edit data setelah lunas | Yes | Terbatas/approval | No |
| Pemutakhiran data | Yes | No | No |
| Input NIM | Yes | No | No |
| Export mahasiswa aktif | Yes | Terbatas | No |

## Domain Model

### Master Data

- `campuses`: name, slug, subdomain, custom_domain, status, address, city, province, logo_path.
- `study_programs`: campus_id, code, name, degree_level, accreditation, status.
- `class_tracks`: campus_id, name, description, status.
- `fee_schemes`: campus_id, nullable study_program_id, nullable class_track_id, registration_fee, building_fee, monthly_tuition_fee, total_initial_payment, description, is_active.

### User & Access

- `users`: name, email, phone, password, role, status.
- `user_campuses`: user_id, campus_id.

### Lead & Enrollment

- `leads`: campus_id, study_program_id, class_track_id, assigned_to_user_id, source_channel, source_detail, full_name, whatsapp_number, origin_school, graduation_year, lead_status, payment_status, enrollment_status, locked_at.
- `student_profiles`: lead_id, PDDIKTI-required identity/address/family/school fields, pddikti_payload_json, completed_at, verified_at.
- `student_numbers`: lead_id, nim, issued_by_user_id, issued_at.

Recommended statuses:

- `lead_status`: new, in_pool, assigned, contacted, interested, not_qualified, lost, converted.
- `payment_status`: unpaid, pending, paid, expired, refunded.
- `enrollment_status`: calon_mahasiswa, menunggu_pemberkasan, menunggu_pemutakhiran, proses_pemutakhiran, mahasiswa_aktif.

### Payment

- `invoices`: lead_id, invoice_number, payment_gateway, gateway_reference, amount, payment_method, payment_url, qr_string, va_number, status, expires_at, paid_at, regenerated_from_invoice_id.
- `payment_events`: invoice_id, gateway_reference, event_type, payload_json, processed_at.

Recommended invoice statuses:

- `pending`
- `paid`
- `expired`
- `cancelled`

The UI can display `regenerated` as an invoice history state derived from `regenerated_from_invoice_id`, instead of storing it as a separate invoice status.

### CRM Activity

- `lead_activities`: lead_id, user_id, activity_type, note, next_follow_up_at.
- `whatsapp_messages`: lead_id, invoice_id, recipient_number, template_key, message, provider_reference, status, sent_at, failed_reason.

Recommended activity types:

- `call`
- `whatsapp`
- `note`
- `status_change`
- `invoice_regenerated`
- `assignment`

## Core Workflows

### Registration Step 1

1. Visitor opens public portal or partner campus site.
2. System resolves campus and displays fee information.
3. Visitor submits required registration fields.
4. System creates lead with `lead_status = in_pool`.
5. System creates invoice through payment service.
6. System shows thank-you page with payment URL/VA/QRIS.
7. System sends WhatsApp invoice notification.

### Invoice Expiry & Reminder

1. Invoice expires 24 hours after creation.
2. Scheduler sends WhatsApp reminder 2 hours before expiry.
3. Scheduler marks pending invoices expired after `expires_at`.
4. Paid invoices are never expired by the job.

### Payment Callback

1. Gateway sends webhook to provider endpoint.
2. System stores raw payload in `payment_events`.
3. System validates signature.
4. System resolves invoice by gateway reference.
5. On success: invoice becomes paid, lead payment_status becomes paid, enrollment_status becomes menunggu_pemberkasan.
6. Staff edit access is locked.
7. System sends payment success and pemberkasan WhatsApp messages.

### Manual Pool & Assignment

1. Koordinator sees unassigned leads by campus.
2. Koordinator assigns one or more leads to Staff PMB.
3. System updates lead owner and records assignment activity.
4. Staff PMB sees only assigned leads.

### Regenerate Invoice

1. Staff opens an assigned lead with latest invoice expired or cancelled.
2. Staff clicks regenerate invoice.
3. System creates a new invoice linked to the old invoice.
4. System stores timeline activity.
5. System sends new payment link via WhatsApp.

### Registration Step 2

1. Paid lead opens tokenized pemberkasan link.
2. System validates payment status and token.
3. Student completes PDDIKTI-required fields.
4. System stores `student_profiles`.
5. Enrollment status becomes `menunggu_pemutakhiran`.
6. Staff edit access remains locked.

### Pemutakhiran & NIM

1. Super Admin reviews queue.
2. Super Admin marks status `proses_pemutakhiran`.
3. Super Admin enters unique NIM.
4. System creates `student_numbers`.
5. Enrollment status becomes `mahasiswa_aktif`.
6. Student is available for export.

## Public Endpoints

- `GET /`
- `GET /kampus`
- `GET /kampus/{slug}`
- `GET /daftar`
- `POST /daftar`
- `GET /thank-you/{lead}`
- `GET /pemberkasan/{token}`
- `POST /pemberkasan/{token}`

## Webhook Endpoints

- `POST /webhooks/payment/{provider}`
- `POST /webhooks/leads/meta`
- `POST /webhooks/leads/google`

## Filament Resources

- Campuses
- Study Programs
- Class Tracks
- Fee Schemes
- Leads
- Invoices
- Student Profiles
- Student Numbers
- Users
- Reports

## Service Abstractions

### Payment Gateway

```php
createInvoice(Lead $lead, array $payload): InvoiceResult
getInvoiceStatus(Invoice $invoice): PaymentStatusResult
validateWebhook(Request $request): bool
parseWebhook(Request $request): PaymentWebhookResult
```

MVP should start with a mock provider, then replace it with one real provider such as Midtrans, Xendit, or Tripay.

### WhatsApp Provider

```php
sendInvoiceMessage(Lead $lead, Invoice $invoice)
sendExpiryReminder(Lead $lead, Invoice $invoice)
sendPaymentSuccess(Lead $lead, Invoice $invoice)
sendPemberkasanLink(Lead $lead)
```

Initial templates:

- `invoice_created`
- `invoice_expiry_reminder`
- `payment_success`
- `document_completion_required`
- `invoice_regenerated`

## Key Business Rules

- WhatsApp numbers must be normalized to international format.
- Campus, study program, and class track must be active.
- Displayed fee must match invoice amount.
- One lead can have many invoices.
- Only one active pending invoice is allowed per lead.
- Regeneration is allowed only if the latest invoice is expired or cancelled.
- Paid invoices cannot be mutated.
- Pemberkasan is only available after payment is paid.
- Required PDDIKTI fields must be complete before pemutakhiran.
- Staff cannot edit locked leads.
- Only Super Admin can activate student status.
- NIM is required and unique for active students.

## Security & Audit

MVP requirements:

- Role-based access control.
- Campus scoping for Koordinator PMB.
- Assignment scoping for Staff PMB.
- Payment webhook signature validation.
- Audit log for important status changes.
- Rate limiting for public and webhook endpoints.
- CSRF protection for web forms.
- Unique token for pemberkasan links.

Audited events:

- Lead created.
- Lead assigned.
- Invoice created.
- Invoice paid.
- Invoice expired.
- Invoice regenerated.
- Student profile completed.
- Enrollment status changed.
- NIM issued.

## Definition of Done

MVP is complete when:

- Public registration creates leads.
- Invoice is automatically created and sent.
- Successful payment updates invoice and lead statuses.
- Leads can be manually assigned to Staff PMB.
- Staff can follow up and regenerate expired invoices.
- Paid students can complete pemberkasan.
- Staff is locked out after pemutakhiran queue entry.
- Super Admin can verify, issue NIM, and activate students.
- Active student data can be exported.
