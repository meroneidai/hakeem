# Clinic & Doctor Onboarding, Subscriptions, Payments

## 1. Registration Entry Point

Doctors and Clinics register through **the same registration panel/flow**. The very first question determines the shape of the account:

> "Does this clinic have one doctor, or more than one doctor?"

- **Single doctor** → the doctor's profile *is* the clinic profile (a "solo clinic"); still uses all Clinic features (addresses, subscription, etc.) under the hood, but the UI presents it as one doctor profile.
- **Multiple doctors** → a Clinic entity is created first; doctors are added as members of it afterward, and each doctor gets their own public profile that also appears in the platform's global Doctors directory.

### Required fields at registration
- Clinic/doctor name (Arabic + English)
- **Email** — required (used for account recovery, invoices, official communication)
- **Phone number** — required (used for notifications on new bookings and status changes, and as the primary WhatsApp/Hermes contact channel)
- Governorate + City for the first address
- Specialty/specialties offered

## 2. Doctors, Clinics, and Addresses — Data Relationships

- A **Clinic** has one or more **Doctors** (many-to-many: a doctor could in theory work across more than one clinic, though v1 can start with one-clinic-per-doctor and extend later).
- A **Clinic** has one or more **Clinic Addresses**.
- Each **Clinic Address** has its **own independent working-hours schedule** (days open, time ranges, break times, slot duration) — Address A of a clinic can be open Sat–Thu 9–5 while Address B is open Sun–Fri 12–8.
- Each Doctor can be linked to specific Addresses (a doctor might only see patients at one of the clinic's two branches) with their own per-address availability layered on top of the address's hours.

## 3. Subscription Plans

At registration, every Clinic must choose a subscription plan. There are **3 plans**:

| Plan | Price (launch) | Bookings | Services Available | Notes |
|---|---|---|---|---|
| **Starter (Free)** | Free | Unlimited | All services, fully unlocked | Default/active plan at launch — everything is free and unlimited to drive adoption |
| **Growth** | TBD by admin | Configurable cap or higher tier | Configurable subset/full | Admin can edit limits & price at any time |
| **Pro** | TBD by admin | Highest/unlimited | Full + premium features (e.g., advanced analytics, promoted placement) | Admin can edit limits & price at any time |

Key requirement: **plan limits and contents are not hardcoded** — the Platform Admin dashboard must let admins:
- Rename plans, change pricing (monthly/yearly), and set a yearly discount %
- Set the booking cap per plan (or mark "unlimited")
- Toggle which services/features are included per plan (feature flags per plan)
- Apply discount/campaign codes platform-wide or per plan

This mirrors the existing AI-agent SaaS plan/module/limits pattern already used on other Hakeem-style projects — plans are data-driven from the admin panel, not code-level constants.

## 4. Payment Configuration

Payment behavior is **configurable per clinic from the Admin panel** (and optionally overridden per clinic from the clinic's own settings, subject to what admin allows):

- **Online payment**: enabled/disabled platform-wide or per clinic; when enabled, integrates a local Egyptian payment gateway (e.g., Paymob/Fawry-class provider) for card/wallet payments.
- **Pay at clinic**: patient pays in person at the front desk.
- **Pay after service**: clinic marks the booking paid once the service is delivered (invoice/reconciliation later).

Reception/clinic dashboard shows, per booking, which payment mode applies and its paid/unpaid status.

## 5. Front-Desk / Reception

Every clinic has a **Reception** capability — a scoped dashboard role (see `04-admin-dashboard-support.md` §3 for the general role/permission model) whose job is to:

- View the live queue of today's bookings across all the clinic's addresses (or one address, if reception staff is address-scoped)
- Confirm / reschedule / cancel bookings
- Check patients in on arrival, mark no-shows
- Create manual bookings (walk-ins, phone calls)
- Trigger payment status updates for "pay at clinic" bookings

Reception does **not** have access to: subscription/billing settings, other clinics' data, or platform-wide analytics.

## 6. Notifications on Registration & Status Changes

- On successful registration, clinic/doctor gets a welcome notification + email with next steps (add addresses, add doctors, set schedule).
- Every booking status change notifies both the patient and the relevant doctor/clinic (see `05-notifications-agent-whatsapp.md` for channels).

## 7. Onboarding Order (build sequence)

Per the agreed rollout plan (also see `07-roadmap-phases.md`): the **Admin Dashboard is built and fully configured first** (governorates/cities, specialties, plans, service categories), and only afterward does the team build and open the **Clinic registration & dashboard**, since clinics depend on admin-defined reference data (specialties list, plans, cities) to even complete registration.
