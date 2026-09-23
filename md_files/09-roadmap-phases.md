# Roadmap & Build Phases

Sequencing follows the agreed principle: **build the Admin Dashboard completely first**, since Clinics/Doctors depend on admin-defined reference data (geography, specialties, plans) to even complete registration. Clinics come next, then patient-facing discovery/booking, then the conversational agent and advanced features.

**Current status (2026-09-18):** Phases 0–4 website/backend paths are live (clinic booking with slots, home visit address, video join page, labs checkout, medical library, specialty×city and service×city). Ratings stay visit-verified only. `/ar`/`/en` prefixes stay out. The payment gateway is not charged yet — online stays unpaid until Paymob/Fawry is keyed in. Native app and Hermes admin LLM remain later. See `PLATFORM_STATUS.md`.

## Phase 0 — Foundations
- Laravel project scaffold, PostgreSQL setup, Sanctum auth, base Tailwind/Blade design system, Arabic/English i18n scaffolding, RTL layout base.
- Core tables: users, roles, governorates, cities.
- CI-ready local dev environment.

## Phase 1 — Admin Dashboard (build fully before anything else)
1. Governorates & Cities management
2. Specialties & Service Types management
3. Subscription Plans management (3-plan system, pricing, booking caps, feature flags, discount codes)
4. Payment configuration (global default modes, gateway setup)
5. Admin roles/permissions for internal staff (Platform Admin, Support Agent)
6. Promotions & Offers management (platform-wide)
7. SEO page management (meta overrides, sitemap generation groundwork)
8. Support ticket inbox (structure ready, even before chat channel is live)
9. Analytics dashboard shell (wired up as data starts flowing in later phases)

**Exit criteria:** an internal admin can fully configure the platform's reference data and plans with zero clinics/doctors existing yet.

## Phase 2 — Clinic & Doctor Onboarding
1. Combined registration flow (single-doctor vs multi-doctor branching)
2. Clinic profile, logo, email/phone
3. Clinic Addresses + per-address working-hours schedules
4. Doctor profiles + clinic-doctor linking + per-doctor availability
5. Subscription selection at registration (defaults to free Starter plan)
6. Clinic dashboard shell: doctors, addresses, services, subscription status
7. Reception role and scoped permissions

**Exit criteria:** a clinic can fully self-register, add doctors/addresses/schedules, and choose a plan.

## Phase 3 — Core Booking Engine
1. Booking data model + status machine (`08-database-schema.md`) — **tables live; public request creates `pending` + history**
2. Clinic Appointment flow end-to-end (the "most common path") — **patient can submit a pending request from `/book/doctors/{slug}` and review it at `/appointments`**
3. Reception queue UI (confirm/reschedule/cancel/check-in, manual booking creation) — **live at `/clinic/queue`**
4. Notifications on booking create/status change (push + SMS to start; WhatsApp/Hermes added in Phase 6) — **named events log via `NotificationDispatcher`; channels stay env-gated**
5. Payment mode selection per booking (online/at-clinic/after-service) — **patient and walk-in pick from admin-allowed modes; clinic override when unlocked; gateway collection still later**

**Exit criteria:** a patient can register, find a clinic, and complete a clinic-appointment booking end-to-end; clinic can manage it from Reception.

## Phase 4 — Remaining Service Types
1. Home Visit (patient address capture, service radius/surcharge)
2. Video Consultation (video room provisioning, in-call chat)
3. Lab Test + Home Lab Test (test catalog, result upload, structured results)
4. Online Psychiatric Consultation (extra privacy rules)
5. Physical Therapy sessions (mandatory first evaluation session logic)
6. Dental/Cosmetic/Beauty service categories (catalog/tagging, no new mechanics)

**Exit criteria:** all six service types are fully bookable and manageable from both patient and clinic sides.

## Phase 5 — Medical Record & Consent
1. Unified patient Medical Record view (results, prescriptions, visit history)
2. Prescription generation (branded document + verification code)
3. Cross-clinic access request/consent flow + audit log
4. Patient-facing "who accessed my record" log

**Exit criteria:** prescriptions and lab results reliably land in one patient record, and cross-clinic access is fully consent-gated.

## Phase 6 — Hermes Conversational Agent & Notification Channels
1. WhatsApp Business API integration
2. Hermes deployed as a separate service, wired to `api/agent/v1`
3. Full booking flow achievable via WhatsApp/chat (create, reschedule, cancel, check results)
4. Support-intent detection → hand-off to human Support Agent
5. Multi-channel notification matrix fully live (push/SMS/WhatsApp/email, with fallback logic)

**Exit criteria:** a patient can complete an entire booking purely through WhatsApp.

## Phase 7 — Discovery, SEO & Growth
1. Homepage: search/filter, Most Booked, Top Rated, Offers, specialty tiles — **partially live now**: homepage, `/search`, `/doctors`, `/clinics`, `/specialties`, `/services`, `/cities`, `/labs` catalog + cart, `/offers`. Ratings/Most Booked wait for completed bookings.
2. Programmatic SEO pages (city, governorate, specialty, city×specialty, service pages) with schema.org structured data — **generator paths now match the UI spec** (`/specialties/{slug}`, `/services/{slug}`, `/cities/{slug}`); combo landings and schema are still Phase 7.
3. Ratings & Reviews (post-visit prompts, verified-visit badges, moderation)
4. Promotions engine live end-to-end (clinic-level + platform-level), homepage integration — **admin + clinic CRUD and public directory are live**; booking attribution follows Phase 3
5. Sitemap automation, hreflang, meta management from Admin — **admin meta overrides are live**; `/ar`/`/en` prefixes and hreflang wait until locale-prefixed URLs ship.

**Exit criteria:** the platform is fully self-serve discoverable and SEO-indexable, with live ratings and offers.

## Phase 8 — Mobile App (React Native + Expo, hybrid)
1. Native shell: auth, push notifications (FCM), camera/gallery upload, deep links
2. WebView-embedded business screens reusing Phase 1–7 Blade views
3. Biometric login, background reminder handling

**Exit criteria:** feature parity with web for booking, medical record, and notifications, published to app stores.

## Phase 9 — Hardening & Launch
1. Load testing on booking concurrency (slot race conditions)
2. Security review: consent model, prescription tamper-evidence, PII encryption
3. Arabic/English content QA across all SEO pages
4. Payment gateway go-live (real transactions)
5. Public launch
