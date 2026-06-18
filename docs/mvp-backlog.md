# MVP Backlog

Project: Sistem Pusat Mahera Media & CRM
Version: 1.0
Date: 2026-05-23

## Milestone 0: Project Foundation

### M0.1 Setup Laravel Project

Acceptance criteria:

- Laravel project runs locally.
- Database connection works.
- Environment file is prepared.
- Queue and scheduler can run.

### M0.2 Install Filament Dashboard

Acceptance criteria:

- Admin panel is accessible.
- First admin user can log in.
- Basic dashboard layout exists.

### M0.3 Setup Roles & Permissions

Acceptance criteria:

- Super Admin, Koordinator PMB, and Staff PMB roles exist.
- Basic permissions can be used in Filament.
- Users can be linked to campuses.

## Milestone 1: Master Data

### M1.1 Campus Management

Acceptance criteria:

- Super Admin can create, edit, and deactivate campuses.
- Campus has slug, subdomain, and optional custom domain.
- Koordinator only sees assigned campuses.

### M1.2 Study Program Management

Acceptance criteria:

- Study program belongs to campus.
- Study program can be active or inactive.
- Active study programs appear in the public registration form.

### M1.3 Class Track Management

Acceptance criteria:

- Class track belongs to campus.
- Class track can be active or inactive.
- Active class tracks appear in the public registration form.

### M1.4 Fee Scheme Management

Acceptance criteria:

- Registration fee, building fee, and monthly tuition fee can be managed.
- Fee scheme can be linked to campus, study program, and class track.
- Public form displays matching fee information.

## Milestone 2: Public Registration

### M2.1 Tenant Resolver

Acceptance criteria:

- System resolves campus from subdomain.
- System resolves campus from custom domain.
- Unknown tenant shows fallback page.

### M2.2 Public Campus Listing

Acceptance criteria:

- Visitor can see active campuses.
- Visitor can filter by fee or study program.
- Transparent fee details are visible before registration.

### M2.3 Registration Step 1

Acceptance criteria:

- Form captures required step 1 fields.
- Validation works.
- Lead is created after submit.
- Lead enters campus queue pool.

### M2.4 Thank You Page

Acceptance criteria:

- User sees registration status after submit.
- Payment link/VA/QRIS is visible.
- Payment instruction is clear.

## Milestone 3: Payment Automation

### M3.1 Payment Gateway Service

Acceptance criteria:

- Service can create invoice.
- Provider response is stored.
- Invoice expires in 24 hours.

### M3.2 Payment Webhook

Acceptance criteria:

- Provider webhook is accepted.
- Signature is validated.
- Payload is stored.
- Invoice becomes paid after successful payment.
- Lead payment_status follows invoice status.

### M3.3 Invoice Expiry Job

Acceptance criteria:

- Pending invoice automatically expires after 24 hours.
- Job can be run by scheduler.
- Paid invoice is never expired by the job.

### M3.4 WhatsApp Invoice Notification

Acceptance criteria:

- New invoice is sent via WhatsApp.
- Reminder is sent 2 hours before expiry.
- Message history is stored.

## Milestone 4: CRM Routing

### M4.1 Queue Pool Dashboard

Acceptance criteria:

- Koordinator sees new leads per campus.
- Unassigned leads appear in pool.
- Campus and date filters are available.

### M4.2 Manual Assignment

Acceptance criteria:

- Koordinator can assign leads to Staff PMB.
- Staff only sees assigned leads.
- Assignment activity is recorded.

### M4.3 Staff Lead Dashboard

Acceptance criteria:

- Staff sees assigned leads.
- Staff can record follow-up.
- Staff can change lead_status according to access rules.

### M4.4 Regenerate Invoice

Acceptance criteria:

- Regenerate button appears for expired invoices.
- New invoice is created.
- Old invoice remains as history.
- New payment link is sent via WhatsApp.

## Milestone 5: PDDIKTI Data Lifecycle

### M5.1 Registration Step 2

Acceptance criteria:

- Pemberkasan form is only accessible after paid payment.
- Required PDDIKTI fields are available.
- Data is stored in student_profiles.

### M5.2 Data Locking

Acceptance criteria:

- Staff cannot edit data after paid status and pemutakhiran queue entry.
- Super Admin can still edit.
- UI shows read-only state for Staff.

### M5.3 Pemutakhiran Queue

Acceptance criteria:

- Super Admin sees queue for `menunggu_pemutakhiran`.
- Super Admin can mark data as `proses_pemutakhiran`.
- Status change is recorded.

### M5.4 NIM Issuance

Acceptance criteria:

- Super Admin can enter NIM.
- NIM is unique.
- After NIM is saved, status becomes `mahasiswa_aktif`.

## Milestone 6: Reporting & Export

### M6.1 Lead Report

Acceptance criteria:

- Super Admin can see total leads by campus, study program, source, and status.
- Koordinator only sees assigned campus scope.

### M6.2 Payment Report

Acceptance criteria:

- Report shows pending, paid, and expired invoices.
- Date filter is available.
- Total paid amount is visible.

### M6.3 Student Export

Acceptance criteria:

- Active student data can be exported to CSV/XLSX.
- Export includes main PDDIKTI fields.
- Export is restricted to Super Admin or explicitly permitted role.

## Suggested Implementation Order

1. Setup Laravel, Filament, auth, and roles.
2. Build master data: campus, study program, class track, fee scheme.
3. Build registration step 1 without real payment gateway.
4. Add mock payment service to test invoice flow.
5. Build lead pool and manual assignment.
6. Build Staff dashboard and follow-up activity.
7. Integrate real payment gateway.
8. Add payment webhook and expiry job.
9. Add WhatsApp notification service.
10. Build pemberkasan step 2.
11. Add locking and pemutakhiran flow.
12. Add NIM issuance.
13. Add exports and basic reports.
