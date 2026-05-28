# SVNC Admissions Portal

NEP-2020-compliant, single-college online admission management system built for **National College, Birbhum, Kolkata**. End-to-end: student registration, DigiLocker-verified documents, application & payment flow, eligibility engine, admission tests with admit cards, merit lists with tie-breakers, seat allocation with acceptance windows, withdrawal & refund (UGC-slab compliant), notifications (SMS / WhatsApp / Email), AISHE & operational reports, DPDP-compliant audit log.

---

## Tech Stack

- **PHP 8.3 · Laravel 13 · Inertia v3 · Vue 3 · Tailwind v4**
- **MySQL 8** (dev runs against Herd-managed Windows env; tests use SQLite `:memory:`)
- **Modular monolith** via `nwidart/laravel-modules` — 14 domain modules (Users, Students, Academics, Admissions, Documents, Fees, Payments, Tests, Merit, Seats, Notifications, Reports, Audit, Support)
- **Spatie permission + activitylog + medialibrary**, Pest 4, DOMPDF, league/csv, Maatwebsite Excel
- Driver-pattern integrations: **Razorpay** (payments), **Msg91** (SMS), **Gupshup** (WhatsApp) — all run in stub mode out of the box

---

## Quick Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# request the schema dump (see "Database" below)
php artisan migrate
php artisan db:seed

npm run build              # or `npm run dev` for HMR
php artisan serve          # or use Herd / Valet
```

Default dev credentials (seeded):

- Super Admin — `superadmin@svnc.test / password`
- Demo Staff — `staff@svnc.test / password`
- **Universal OTP (non-production only):** `123456`

---

## Database

The migrations and seeders **are not bundled in this repository**. They are the proprietary part of the project.

**Request access here:** [https://forms.gle/XYjKgvRaw1ZSZ8Cv7](https://forms.gle/XYjKgvRaw1ZSZ8Cv7)

State your intended use case (non-commercial / commercial — see License below) in the form. The schema dump + seeders will be shared with you on approval.

---

## Project Conventions

- All `php`, `artisan`, `composer`, `npm` commands on Windows + Herd run via `cmd //c "..."` in Bash (not PowerShell).
- `uploaded_documents.disk` is mandatory — disk migration command supports moving files between drivers.
- CSRF is exempted ONLY for `webhooks/*` and `payments/callback/*`.
- Encrypted at rest: DigiLocker tokens, payment gateway credentials, last-4 of Aadhaar.
- Sensitive routes are gated by named rate limiters (`login`, `register`, `otp-send`, `otp-verify`, `password-reset`).
- 136 Pest feature tests cover the critical flows.

---

## License

Copyright © Subhojit Sarkar. All rights reserved.

This source is released under a **dual-track license**:

### Non-Commercial — free
You may use, modify, and self-host this software **without payment** if **all** of the following apply:
- You are a non-profit educational institution, registered NGO / trust, government department, or individual student / researcher.
- The deployment does not generate revenue (no paid subscriptions, no per-application fees collected beyond statutory government dues).
- This `README.md` and the copyright notice above are preserved in the source.
- Modifications are not redistributed for profit.

### Commercial — royalty required
Any of the following triggers commercial terms:
- For-profit institutions, private universities / colleges, ed-tech vendors, SaaS resellers.
- Paid subscriptions, white-label licensing, or charging students above statutory fees.
- Bundling this software (or any derivative) into a paid product.

Commercial users must obtain a written royalty agreement from the author **before** production deployment. Submit the licensing request via [https://forms.gle/XYjKgvRaw1ZSZ8Cv7](https://forms.gle/XYjKgvRaw1ZSZ8Cv7) — include organisation, intended deployment scope, and expected user volume.

Unauthorised commercial use is a copyright violation under the Indian Copyright Act, 1957 and applicable international treaties.

**THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED.**

---

## Contact

**Subhojit Kundu**
Database access, commercial licensing, and bug reports — all routed through one form:

[https://forms.gle/XYjKgvRaw1ZSZ8Cv7](https://forms.gle/XYjKgvRaw1ZSZ8Cv7)
