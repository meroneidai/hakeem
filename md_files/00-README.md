# Hakeem (حكيم) — Project Documentation

Full specification for Hakeem, a healthcare booking and clinic-management platform for Egypt, connecting patients, doctors, and clinics for clinic appointments, home visits, video consultations, lab tests (in-clinic and home), and online psychiatric consultations — with a unified, consent-gated patient medical record.

## Reading Order

1. **01-overview-vision-personas.md** — What Hakeem is, business goals, Egypt geography model, user personas
2. **02-booking-services.md** — All six booking service types and their flows, including prescriptions
3. **03-clinic-doctor-onboarding-subscriptions.md** — Registration flow, addresses/schedules, 3-plan subscription system, payments, reception
4. **04-admin-dashboard-support.md** — Admin dashboard scope, roles/permissions, medical record consent model, support flow
5. **05-notifications-agent-whatsapp.md** — Notification matrix and the Hermes conversational booking agent (WhatsApp/chat)
6. **06-seo-homepage-discovery-ratings.md** — Homepage, SEO architecture, ratings/reviews, offers/promotions
7. **07-technical-architecture.md** — Stack, system diagram, hybrid mobile app approach, API surface, security
8. **08-database-schema.md** — Starting entity-relationship / migration outline
9. **09-roadmap-phases.md** — Phased build plan (Admin Dashboard first, then Clinics, then Booking Engine, etc.)

## Build Order (short version)

Admin Dashboard → Clinic/Doctor Onboarding → Core Booking Engine (Clinic Appointment first) → Remaining Service Types → Medical Record/Consent → Hermes Agent/WhatsApp → SEO/Discovery/Ratings → Mobile App → Hardening & Launch.
