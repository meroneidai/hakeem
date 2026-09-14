# Technical Architecture

## 1. Stack Summary

| Layer | Technology |
|---|---|
| Backend framework | Laravel |
| Server-rendered frontend | Blade + Alpine.js + Tailwind CSS (custom design, not a stock template) |
| Database | PostgreSQL |
| Auth | Laravel Sanctum (token-based, shared by web, mobile, and the Hermes agent) |
| Background jobs | Laravel Queues (notification dispatch, result-processing, report generation) |
| Scheduled tasks | Laravel Scheduler (reminders, subscription renewals, promotion expiry) |
| Domain events | Laravel Events (booking status changes trigger notification + audit-log listeners) |
| Notifications | Laravel Notifications (multi-channel: push/SMS/WhatsApp/email/in-app) |
| Realtime | Laravel Reverb (live queue updates in the Reception dashboard, live chat) |
| Mobile app | React Native + Expo (hybrid — see §3) |
| Push notifications | Firebase Cloud Messaging (FCM) |
| Conversational agent | Hermes — separate service, calls the platform's public API |
| File storage | Local storage at launch (lab result files, prescription PDFs, clinic logos); designed to swap to S3-compatible storage later without code changes (Laravel's storage abstraction) |

## 2. High-Level System Diagram

```
                        ┌─────────────────────────┐
                        │      Laravel API         │
                        │  (Sanctum-authenticated) │
                        └────────────┬─────────────┘
                                     │
          ┌──────────────┬──────────┴───────────┬───────────────────┐
          │              │                       │                    
   Web app (Blade/   Mobile app (React      Hermes Agent         Admin/Clinic
   Alpine/Tailwind)  Native + Expo)      (WhatsApp + Web Chat)     Dashboards
          │              │                       │                    │
          └──────────────┴───────────────────────┴────────────────────┘
                                     │
                        ┌────────────┴─────────────┐
                        │        PostgreSQL          │
                        └────────────────────────────┘
```

## 3. Mobile App — Hybrid Approach

Rather than rebuild every screen natively, the mobile app is a **hybrid**:

- **Native React Native/Expo screens** for anything needing device hardware: push notification handling, camera/gallery (uploading lab result photos, prescription photos), file upload, biometric login, deep links (from WhatsApp/SMS notifications straight into a booking), background tasks (reminders).
- **WebView-embedded screens** for the bulk of business UI (dashboard, bookings list, medical record, doctor/clinic browsing) that reuse the same server-rendered Blade views used on desktop web — meaning most screens are built **once** and shared between web and mobile, and only device-dependent flows get fully native screens.
- Both native and WebView layers authenticate against the same Sanctum-issued token and call the same `example.com/api/v1` endpoints — there is one backend, one business-logic layer, one source of truth.

## 4. URL / API Surface Map

| Path | Purpose |
|---|---|
| `example.com` | Desktop/mobile web (Blade/Alpine SPA-like experience, SEO pages server-rendered) |
| `example.com/mobile` | Mobile-optimized web views embedded inside the React Native WebView shell |
| `example.com/api/v1` | Public API used by web, mobile app, and any future integrations |
| `example.com/api/agent/v1` | API surface tailored for the Hermes agent (conversation-state-aware endpoints, e.g., "get next available slots for X given a partial conversation") — built on top of, not a replacement for, `api/v1` |

## 5. What Lives in Laravel vs. Firebase

**Kept in Laravel (source of truth for everything business-critical):**
Users, Clinics, Doctors, Addresses/Schedules, Bookings, Medical Records, Prescriptions, Subscriptions/Billing, Permissions/Roles, Promotions, Ratings/Reviews, Support Tickets, Queues/Jobs, Scheduled Tasks, API, WebSockets (Reverb).

**Delegated to Firebase:**
- Push notification delivery (FCM) and device token management
- Optionally, phone-number OTP verification at signup, if Firebase's Egypt SMS pricing is cost-effective at Hakeem's expected volume — otherwise this stays on a local/telecom SMS gateway
- App analytics/crash reporting, if desired

## 6. Design System

- Fully custom Tailwind design (no off-the-shelf admin template) — see `frontend-design` guidance for tokens/typography.
- Palette: calm/muted blues as primary, neutral white/gray backgrounds, a single warm accent color reserved for CTAs (Book Now, Confirm) — avoiding "alarming" reds except for cancel/error states.
- Arabic typography: a proper Arabic-optimized web font (e.g., IBM Plex Sans Arabic, Cairo, or Tajawal) paired with a matching Latin font for English content; full RTL layout mirroring (not just text-direction flipping) for both the Blade views and the React Native screens.

## 7. Security & Compliance Considerations

- All medical record access is authorized server-side per the consent model in `04-admin-dashboard-support.md` §3 — never trust client-side role checks alone.
- Full audit logging on: record views, prescription issuance, consent grants/denials, and admin actions on clinic/doctor accounts.
- Prescription documents are tamper-evident (verification code/QR resolving back to Hakeem's canonical record) to prevent forged dispensing.
- PII (phone numbers, national ID if collected later, medical data) encrypted at rest where the database/storage layer supports it; TLS everywhere in transit.
