# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Sistem Pusat Mahera Media & CRM (SPMM) — a Laravel + FilamentPHP PMB/CRM platform. It captures leads (public site, partner campus microsites, Meta Lead Ads), takes them through payment/invoicing, document collection (pemberkasan), NIM issuance, and exports active students for downstream academic systems. Indonesian is the primary language for UI copy, docs, and many identifiers (e.g. `pemberkasan`, `pemutakhiran`, `mahasiswa_aktif`) — keep new user-facing strings and enum values in Indonesian consistent with the existing domain vocabulary.

## Commands

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

- App: `http://127.0.0.1:8000`, admin panel: `http://127.0.0.1:8000/admin`
- DB is PostgreSQL by default (`DB_CONNECTION=pgsql`); CI uses SQLite.
- Lint/format: `vendor/bin/pint` (Laravel Pint, dev dependency — no custom `pint.json`, uses defaults).
- **There is no test suite in this repo** (no `tests/` directory, no `phpunit.xml`). CI (`.github/workflows/laravel-ci.yml`) only runs `composer install`, migrations against SQLite, and `config:cache` / `route:cache` / `view:cache` / `optimize:clear` as a smoke check. If you add tests, you'll need to create `tests/` and `phpunit.xml` first.
- Queue worker / scheduler: standard Laravel (`php artisan queue:work`, `php artisan schedule:work`). Scheduled jobs are defined in `routes/console.php`.
- Custom artisan commands live under the `spmm:` namespace (e.g. `spmm:meta-leads:import`, `spmm:invoices:expire-pending`) — see `app/Console/Commands/`.

## Architecture

### This app is the CRM/PMB layer only, not the academic system

SPMM, SIAKAD (academic records), and LMS are deliberately three separate systems (see `docs/separated-systems-architecture.md`). SPMM owns leads, invoicing, pemberkasan, and issuing NIM to activate a student; it hands off to SIAKAD once a student is `mahasiswa_aktif`. The `Lms*` and academic-looking models/resources present in this codebase (`LmsAssignment`, `LmsModule`, `CourseClass`, `StudyPlan`, etc.) are an in-repo **prototype** of what will eventually be split into standalone SIAKAD/LMS Laravel projects — don't assume they're the long-term home for academic/LMS logic, and don't grow them into a full academic system here. See `docs/technical-spec-mvp.md` and `docs/implementation-decisions.md` for the reasoning behind existing structural decisions (mock payment gateway first, `regenerated` as invoice history not a status, explicit policies per sensitive model, webhook payloads persisted before processing, async WhatsApp delivery).

### Core lead lifecycle

`Lead` is the central model, driven by four independent enums (`app/Enums/`): `LeadStatus`, `ProspectStatus`, `PaymentStatus`, `EnrollmentStatus`. The enrollment lifecycle is:

```
calon_mahasiswa → menunggu_pemberkasan → menunggu_pemutakhiran → proses_pemutakhiran → mahasiswa_aktif
```

Flow: public registration creates a `Lead` + `Invoice` → payment webhook marks it paid and unlocks pemberkasan (`student_profiles`) → Super Admin verifies and issues a `StudentNumber` (NIM) → lead becomes `mahasiswa_aktif` and is eligible for CSV/XLSX export (`StudentExportController`). Leads get `locked_at` once paid; edit authorization must check business status (payment/enrollment), not just presence of `locked_at`.

### Multi-tenant public sites

`TenantResolver` (`app/Services/TenantResolver.php`) resolves the current `Campus` per-request in this priority order: `custom_domain` → subdomain → `?kampus=` query param. Partner campus microsites and the main Kampus Media portal are the same Laravel app serving different tenants based on this resolution — check it before assuming a single-tenant public flow.

### Role-based scoping (not full multi-tenancy at the auth layer)

Four roles in `UserRole`: `super_admin`, `direktur`, `koordinator_pmb`, `staff_pmb`. Query scoping by role is centralized in `app/Support/FilamentResourceScope.php` (campus scoping for coordinators, assignment-only scoping for staff) rather than spread across policies — check there first when adding a new scoped Filament resource. `app/Policies/` holds per-model authorization (`Lead`, `Invoice`, `StudentProfile`, `StudentNumber`) for actions beyond visibility scoping.

### Filament admin panel

Single panel at `/admin`, defined in `app/Providers/Filament/AdminPanelProvider.php`. Resources/Pages/Widgets are auto-discovered from `app/Filament/{Resources,Pages,Widgets}` — no manual registration needed, just drop a class in the right directory following Filament's naming convention.

### Pluggable provider abstractions

Payment and WhatsApp integrations follow the same manager/contract pattern, both currently backed by dummy providers pending real integration:

- `App\Services\Payment\PaymentGatewayManager` → `Contracts\PaymentGateway` → currently only `mock` (`MockPaymentGateway`), selected via `spmm.payment.provider`.
- `App\Services\Whatsapp\WhatsappManager` → `Contracts\WhatsappProvider` → currently only `log` (`LogWhatsappProvider`), selected via `spmm.whatsapp.provider`.

Payment webhooks (`PaymentWebhookController` → `Payment\PaymentWebhookService`) persist the raw payload to `payment_events` *before* validating/processing, for auditability and replay. When adding a real provider, implement the contract and register it in the manager's `match`.

App-specific config (payment/whatsapp provider selection, AI news generation prompts/knowledge base, Meta Lead Ads + Meta Conversions API credentials) lives in `config/spmm.php`, not scattered across `config/services.php`.

### Audit logging

Two mechanisms coexist: `AuditModelObserver` (generic model-change observer) and `AuditLogger` (`app/Services/AuditLogger.php`) for explicit event recording (e.g. `record('invoice.paid', $invoice, [...])`), writing to the `audit_logs` table / `AuditLog` model. Use `AuditLogger` for business-meaningful events called out in `docs/technical-spec-mvp.md` (lead created/assigned, invoice created/paid/expired/regenerated, profile completed, enrollment status changed, NIM issued).

### Meta (Facebook) Lead Ads integration

Leads can arrive via Meta webhook (`MetaLeadWebhookController`, real-time) or via the scheduled fallback command `spmm:meta-leads:import` (`ImportMetaLeads` → `ImportMetaFormLeadsJob` → `MetaLeadImportService`), which polls the Graph API in case the webhook missed something. `MetaProspectEventService` / `spmm:meta-prospect-events:retry` handle sending conversion events back to Meta CAPI. Config for both lives under `spmm.meta_leads` / `spmm.meta_conversions`.

### AI-generated SEO content

`AiEducationNewsDraftService` + `spmm:news:generate-ai-draft` (scheduled daily at 07:00) generate `EducationNews` articles via OpenAI, guided by an extensive editorial knowledge base (brand voice, content pillars, SEO/compliance rules) defined inline in `config/spmm.php` under `ai_news.editorial_knowledge`. Edit that config when adjusting article tone/rules rather than the service itself.

## Repo hygiene gotcha

The working tree has many stray `*.bak-*` / `*.backup-*` files sitting next to their real counterparts (e.g. `PublicRegistrationController.php.bak-affiliate-form-...`, `ReferralPartnerResource.php.backup-before-kampus-media`), left over from manual pre-edit backups rather than version control. These are **not loaded by the app** and are easy to mistake for real source when grepping. Ignore them, don't edit them, and don't treat their presence as an indication of how the current code should look — always confirm you're editing the extensionless `.php` file.
