# Implementation Progress

Date: 2026-05-23

## Implemented

- Laravel-ready application skeleton.
- Environment example and core app/database/cache/session/queue config.
- Filament admin panel provider at `/admin`.
- User model with internal panel access control.
- Domain enums for campus, user, lead, payment, enrollment, invoice, and activity statuses.
- Database migrations for:
  - Framework tables.
  - Users and campus access.
  - Campus, study program, class track, fee scheme.
  - Leads, student profiles, student numbers.
  - Invoices and payment events.
  - CRM activities and WhatsApp message history.
  - Audit logs.
  - Sanctum personal access tokens.
- Eloquent models and relationships.
- Public registration step 1 with fee lookup and invoice creation.
- Mock payment gateway abstraction.
- Payment webhook handler with raw event storage.
- WhatsApp provider abstraction with log-backed message history.
- Tenant resolver with priority: custom domain, subdomain, query parameter.
- Pemberkasan step 2 endpoint with paid-token validation.
- Invoice expiry and reminder scheduler commands.
- Lead assignment service.
- Invoice regeneration service.
- Student number issuance service.
- Active student CSV export.
- Public campus portal page.
- Public campus detail page.
- Public registration form page.
- Public thank-you/payment instruction page.
- Public pemberkasan form page.
- Dashboard overview widgets.
- Filament resources for:
  - Campuses.
  - Study programs.
  - Class tracks.
  - Fee schemes.
  - Leads.
  - Invoices.
  - Student profiles.
  - Student numbers.
  - Users.
  - Audit logs.
  - Payment events.
  - WhatsApp message history.
- Prototype SIAKAD data model and admin resources:
  - Tahun Akademik.
  - Mata Kuliah.
  - Kelas & Jadwal.
  - KRS & Nilai.
- Prototype LMS data model and admin resources:
  - Modul Pembelajaran.
  - Materi Kuliah.
  - Tugas Kuliah.
  - Pengumpulan Tugas.
- Student portal prototype pages for:
  - Materi Kuliah.
  - Tugas Kuliah.

## Architecture Direction Update

SIAKAD and LMS are not part of SPMM in production. They are separate systems with separate domains and databases, integrated through APIs/webhooks. The current SIAKAD/LMS implementation in this workspace is treated as a prototype for model and UI direction before extraction into separate applications.

## Local Test Helper

Mock payment can be marked paid in local environment only:

```text
/mock-payment/{gateway_reference}/pay
```

The response includes the pemberkasan URL.

## Still Pending

- Install PHP, Composer, and Git on this machine.
- Run `composer install`.
- Run Laravel migrations and seeders.
- Publish/install final Filament assets if required by the chosen Filament version.
- Add automated tests after PHP runtime is available.
- Integrate a real payment gateway.
- Integrate a real WhatsApp provider.
- Add XLSX export using `maatwebsite/excel`.
