# Privacy & Compliance Plan (Australian Context)

Status: **draft — not yet implemented**
Last updated: 2026-06-14

This document captures what MacArthur Infusions' booking system needs to do to comply with Australian privacy and health-records law, and a prioritised plan of work. Scope is the booking system only (bookings, payments, customer contact details, notes, service metadata) — not a full clinical EMR.

---

## Legal context

Booking data here includes name, email, phone, notes, and the linked service. Because the service is healthcare, this constitutes **health information** under the Privacy Act 1988 (Cth), which is treated as "sensitive information" — the highest privacy bar.

### Primary legislation

1. **Privacy Act 1988 (Cth) & Australian Privacy Principles (APPs)** — applies to private health providers regardless of size. Key APPs for us: APP 1 (open management), APP 3 (collection), APP 5 (notification), APP 6 (use/disclosure), APP 10 (quality), APP 11 (security), APP 12 (access), APP 13 (correction).
2. **My Health Records Act 2012 (Cth)** — only if we integrate with My Health Record.
3. **Healthcare Identifiers Act 2010 (Cth)** — only if we handle IHI/HPI identifiers.
4. **State/Territory health records laws** — NSW (Health Records and Information Privacy Act 2002), VIC (Health Records Act 2001), ACT (Health Records (Privacy and Access) Act 1997). Other states rely on the federal Act.

### Core obligations summary

- **Retention**: adult records ≥ 7 years from last entry; minors until age 25.
- **Accuracy**: records must be accurate, up-to-date, complete.
- **Audit trail**: who accessed/modified what, when — immutable.
- **Consent**: explicit consent at collection; separate consent for marketing.
- **Access & correction**: patients can request their record (response within 30 days).
- **Notifiable Data Breaches (NDB) scheme**: notify OAIC and affected individuals of eligible breaches.

### Security (APP 11)

- Encryption at rest and in transit (TLS 1.2+).
- Role-based access — staff see only what they need.
- MFA for clinical/admin users.
- Secure backups, tested restoration.
- AU data residency preferred (required for My Health Record).
- Secure destruction/de-identification when no longer needed.

### Professional standards (clinical)

- AHPRA / Medical Board record-keeping guidelines.
- RACGP Standards (if GP).
- NSQHS Standards if accredited.

---

## Current state (as of 2026-06-14)

The `bookings` table stores:
- `customer_name`, `customer_email`, `customer_phone`, `notes` — all plaintext
- `service_id` (linked to the booked health service)
- `starts_at`, `ends_at`, `status`, `reference`
- Payment link fields, reminder timestamps

There is **no**: consent capture, audit log, MFA, retention/anonymisation job, encrypted PII columns, published privacy policy, breach response plan, documented data residency.

---

## Priority plan

Recommended order to implement:

1. Encrypt `notes` + force HTTPS *(few hours)*
2. Add consent capture + collection notice to booking form *(half day)*
3. Write & publish Privacy Policy *(half day, mostly content)*
4. Add audit log table + log access to bookings/notes *(1–2 days)*
5. MFA for admin users *(half day)*
6. Document retention policy + breach response plan *(half day, mostly writing)*
7. Verify AU data residency with hosting + SMS provider *(check, document)*
8. Build retention/anonymisation job *(1 day)*
9. Build patient access/correction admin workflow *(1 day)*

Items 1–3 close the highest-risk gaps and are patient-visible. Items 4–6 are the structural ones an auditor would expect.

---

## Detailed specs

### 1. Encrypt `notes` (+ contact fields)

**Schema change** — `bookings` table:
- `notes` → cast as `encrypted` in the Eloquent model. Existing rows need a one-off backfill migration (plaintext → encrypted).
- `customer_email` → either keep plaintext for lookup **or** split into `customer_email_encrypted` + `customer_email_hash` (SHA-256 of lowercased email) for searchability.
- `customer_phone` → encrypted cast; add `customer_phone_hash` if search is needed.
- `customer_name` → encrypted cast (low search need).

**App config:**
- `APP_KEY` rotated out of any shared/dev environments before going live.
- Documented key rotation procedure (Laravel `APP_PREVIOUS_KEYS`).
- Force HTTPS in production.

**Gotchas:**
- Encrypted columns can't be indexed or `WHERE`-searched — hence the hash columns.
- Backfill in a transaction with row locks, or during a maintenance window.

### 2. Consent capture

**New table: `booking_consents`** (one-to-one with booking, separate so consent history survives booking edits):

| column | type | notes |
|---|---|---|
| id | bigint pk | |
| booking_id | fk bookings | restrict on delete |
| privacy_policy_version | string | e.g. `"2026-06-14"` |
| collection_notice_version | string | same scheme |
| consented_at | timestamptz | server time |
| consent_ip | inet | from request |
| consent_user_agent | string | from request |
| marketing_opt_in | boolean | default false |
| sms_opt_in | boolean | default true — required for reminders |
| created_at / updated_at | | |

**Versioning:** keep `resources/legal/privacy-policy-YYYY-MM-DD.md` and `collection-notice-YYYY-MM-DD.md` in repo. The version stored is the filename date. Never edit a published version — publish a new one.

**Booking form additions:**
- Collection notice text shown inline (not just a link).
- Required checkbox, not pre-ticked: *"I have read the Privacy Policy and Collection Notice and consent to MacArthur Infusions collecting and storing my personal and health information for the purposes of providing this service."*
- Required checkbox: *"I agree to receive booking confirmations and reminders by SMS and email."*
- Optional, default-off: *"I'd like to receive occasional updates from MacArthur Infusions."*

### 3. Privacy Policy + Collection Notice (content brief)

**Privacy Policy** (public page, footer link) must state:
- Legal entity name, ABN, contact.
- What info is collected (identifiers, contact, health/service info, payment metadata).
- Why (provide services, manage bookings, payments, reminders, legal obligations).
- Who it's shared with — name processors: payment gateway, SMS provider, hosting, accountant.
- Where it's stored (country — AU preferred).
- Retention periods.
- Patient rights: access, correction, complaint to OAIC.
- Contact for privacy requests (email + postal).
- Version date.

**Collection Notice** — shorter, point-of-collection version, per APP 5 elements.

### 4. Audit log

**New table: `audit_logs`** — append-only, never updated:

| column | type | notes |
|---|---|---|
| id | bigint pk | |
| actor_type | string | `user`, `system`, `guest` |
| actor_id | bigint nullable | fk users when actor_type=user |
| actor_ip | inet | |
| action | string | see list below |
| subject_type | string | `booking`, `payment`, `user` |
| subject_id | bigint | |
| metadata | jsonb | action-specific (exported_fields, changed_columns, etc.) |
| occurred_at | timestamptz | indexed |

**Actions to log (minimum):**
- `booking.viewed` — admin opens booking detail page
- `booking.notes_viewed` — separate event; notes are the sensitive bit
- `booking.created` / `booking.updated` / `booking.cancelled` — include diff in metadata
- `booking.exported` — CSV/PDF export
- `booking.access_requested` / `booking.access_fulfilled` — patient APP 12 requests
- `user.login` / `user.login_failed` / `user.logout`
- `user.mfa_enabled` / `user.role_changed`
- `audit.exported` — log exports of the audit log itself

**Implementation notes:**
- Laravel observer on each Eloquent model, plus explicit calls for view actions.
- Append-only — no UI to edit/delete. Retention at least as long as the records they describe.
- Nightly job ships logs to off-server location (S3 with object-lock or equivalent) for tamper resistance.

### 5. MFA for admin users

**Approach:** Laravel Fortify with TOTP (Google Authenticator / 1Password / Authy).

- Columns on `users`: `two_factor_secret` (encrypted), `two_factor_recovery_codes` (encrypted), `two_factor_confirmed_at`.
- Any user with role `admin` or `staff` must have `two_factor_confirmed_at` set before accessing admin routes. Middleware redirects to setup if missing.
- Recovery codes shown once at setup, downloadable.
- Session timeout: 30 minutes idle for admin routes.
- Login throttling: 5 attempts / 15 min for admin login.

### 6. Retention policy

| Data | Retain for | Then |
|---|---|---|
| Booking + notes (adult) | 7 years from `bookings.ends_at` | anonymise |
| Booking + notes (minor) | until patient turns 25 (requires DOB capture) | anonymise |
| Payment records | 7 years (ATO requirement) | retain financial fields, anonymise PII |
| Audit logs | 7 years minimum | retain |
| Marketing contact (no booking) | until opt-out, max 2 years inactive | delete |

**Anonymisation job** (scheduled daily):
- Find bookings where `ends_at < now() - 7 years` AND not yet anonymised.
- Null `notes`; replace `customer_name` with `"Anonymised"`; hash email/phone to non-reversible placeholders; set `anonymised_at` timestamp.
- Keep `service_id`, dates, status, payment link — needed for reporting.
- Log each anonymisation to audit log.

**Note:** capturing DOB is required for the minor rule. Add `customer_dob` (date, encrypted) to bookings, or to a patient table if patients are split out later.

### 7. Breach response plan (document)

To be written as `docs/breach-response-plan.md`:

- **Detection sources**: error monitoring spike, unusual export volume, failed login spike, third-party notification, staff report.
- **Roles**: named Incident Lead, decision-maker for notification, drafter of patient comms.
- **Triage (1 hour)**: contain (revoke keys/sessions), preserve (snapshot logs), assess scope.
- **Eligible breach test** (OAIC): likely to result in serious harm → notify.
- **Timeline**: OAIC notification within 30 days of awareness; patient notification as soon as practicable.
- **Templates**: OAIC notification form pre-filled with entity details; patient email template.
- **Post-incident review** within 2 weeks.

---

## Operational hygiene (ongoing)

- `.env` and DB credentials out of source control.
- Production logs must not contain PII — scrub `notes`, names, phones from log lines and exception reports.
- Sentry/error tracking — same PII scrubbing rule.
- Staff training documented (even informally).

## Third parties — agreements needed

Every external service touching patient data needs a written privacy agreement, AU residency preferred:

- **Hosting** — verify DB region (AU).
- **Payment processor** — usually Stripe; document the flow.
- **SMS/email provider** — what provider, where do they store data?
- **Backups** — encrypted, AU-resident, access-controlled, tested restore.

---

## References

- OAIC: *Guide to health privacy* — https://www.oaic.gov.au/privacy/privacy-guidance-for-organisations-and-government-agencies/health-and-medical-research/guide-to-health-privacy
- Australian Digital Health Agency: My Health Record conformance.
- RACGP: *Standards for general practices* (5th edition).
