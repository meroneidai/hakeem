# Hakeem (حكيم) — Project Overview

## 1. What Hakeem Is

Hakeem is a national healthcare booking and management platform for Egypt that connects **patients**, **doctors**, and **clinics**. It centralizes appointment booking, home visits, video consultations, lab testing (in-clinic and at-home), and online psychiatric/mental-health consultations into a single platform with a shared patient medical record.

- **Primary language:** Arabic (RTL layout, Arabic-first content, typography, and SEO)
- **Secondary language:** English (full i18n, LTR layout toggle)
- **Market:** Egypt only at launch — the data model is geography-aware (Governorate → City) so it can expand to other countries later without a redesign.
- **Core idea:** One platform, one patient record, many service types, many clinics — bookable via web, mobile app, live chat, or WhatsApp (through the Hermes booking agent).

## 2. Business Goals

1. Make it trivially easy for a patient to find a doctor/clinic and book — by area, specialty, and service type.
2. Give clinics a single dashboard to manage doctors, addresses, schedules, bookings, and a front-desk queue.
3. Build a longitudinal, portable medical record per patient (analyses, prescriptions, visit history) that follows the patient across clinics, with explicit patient consent required for any clinic outside the original one to view it.
4. Turn discovery into revenue for clinics via strong SEO landing pages (per city, per specialty, per service) and a promotions/offers engine.
5. Reduce no-shows and manual work via automated notifications and a conversational booking channel (chat/WhatsApp).

## 3. Egypt Geography Model

Egypt is divided into **Governorates (محافظات)**, and each Governorate contains multiple **Cities/Markaz (مدن)**. This two-level structure drives:

- Clinic address selection (a clinic address always belongs to one City, which belongs to one Governorate)
- Homepage/search filtering ("Doctors in Nasr City, Cairo" / "Clinics in Alexandria")
- SEO landing pages (see `06-seo-homepage-discovery-ratings.md`) — one page per Governorate × Specialty, and per City × Specialty
- Analytics/reporting (bookings by Governorate, revenue by City, etc.)

Data model reference: `Governorate (id, name_ar, name_en, slug) → City (id, governorate_id, name_ar, name_en, slug)`

## 4. Core Entities at a Glance

- **Patient** — the end user booking services, identified primarily by phone number.
- **Doctor** — a medical professional; can belong to one or more Clinics.
- **Clinic** — a business entity; can have one or more Doctors, and one or more physical Addresses.
- **Clinic Address** — a physical location belonging to a Clinic; has its own working hours/schedule, independent of the clinic's other addresses.
- **Booking** — the central transactional object; always has a Service Type, a Patient, a Doctor/Clinic, a Clinic Address (if applicable), a status, and a payment mode.
- **Medical Record** — a per-patient, cross-clinic file containing analyses/lab results, prescriptions, and visit history, gated by consent.
- **Subscription Plan** — governs what a Clinic can do on the platform (see `03-clinic-doctor-onboarding-subscriptions.md`).

## 5. User Personas

### 5.1 Patient
- Wants to find a nearby doctor/clinic fast, by specialty or symptom, and book without friction.
- Registers with just phone number + password + name (see onboarding flow).
- Completes their profile and uploads existing analyses later, at their own pace, from the dashboard or app.
- Wants to see all past prescriptions and lab results in one place, regardless of which clinic issued them.
- May want to book via WhatsApp/chat instead of the app — this must work identically.

### 5.2 Doctor
- May work solo (their own single-doctor "clinic") or as part of a multi-doctor clinic.
- Needs to see their own schedule/queue, write consultations, issue prescriptions, and view a patient's shared medical history (with consent) during a visit.
- Wants notifications when a new booking arrives or a booking status changes.

### 5.3 Clinic Owner/Admin
- Registers the clinic once, then adds doctors, addresses (each with its own hours), and services offered.
- Chooses a subscription plan (starts on the free tier).
- Manages a **Reception** role/section to triage and manage incoming booking requests day-to-day, without owner involvement.
- Wants to run promotions/offers and see performance (bookings, ratings, revenue).

### 5.4 Reception (Clinic staff role)
- A limited-permission role inside the Clinic dashboard.
- Can view, confirm, reschedule, and cancel bookings for their clinic/address; check patients in; cannot access billing/subscription settings or other clinics' data.

### 5.5 Platform Admin (Hakeem staff)
- Manages Governorates/Cities, specialties, service categories, subscription plans, feature flags, promotions, and support tickets.
- Approves/rejects new clinic registrations if moderation is enabled.
- Has full analytics across the platform (see `04-admin-dashboard-support.md`).

### 5.6 Support Agent
- Handles patient/clinic issues raised via in-app chat or WhatsApp when something breaks (a failed booking, a payment issue, an access request).

## 6. Guiding Product Principles

1. **Registration friction = zero.** Phone + password + name is enough to start; everything else is optional and deferred.
2. **One patient, one record.** The record lives with the patient (keyed by phone/membership number), not with a single clinic. Cross-clinic access always requires an explicit patient approval (push/SMS confirmation).
3. **Every service type shares one booking engine.** Clinic visit, home visit, video consultation, lab test, home lab test, and psychiatric consultation are all "Bookings" with a `service_type`, not six different systems.
4. **Conversational booking is a first-class channel**, not an afterthought — the Hermes agent must be able to do everything the app can do for booking, using the same API.
5. **Trust and legitimacy are visual.** Prescriptions are watermarked/branded with the clinic logo and doctor name; ratings and clinic verification badges are prominent.
