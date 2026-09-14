# SEO, Homepage Discovery, Ratings & Promotions

## 1. Why This Matters Most on the Homepage

The homepage is the single highest-leverage page in the product: it must let a patient go from "landing on the site" to "booked" in the fewest clicks, while also being the strongest SEO asset the platform has.

## 2. Homepage Requirements

- **Prominent search + filter bar**: filter by Governorate → City (cascading dropdown), Specialty/Service type, and free-text symptom/keyword search.
- **Featured sections**:
  - "Most Booked This Week" doctors/clinics (ranked by recent booking volume)
  - "Top Rated" doctors/clinics (ranked by rating + review count, with a minimum review threshold to avoid gaming)
  - "Current Offers & Promotions" (pulled from the Admin-managed promotions engine, `04-admin-dashboard-support.md` §1.6)
  - Specialty shortcuts (Dental, Physical Therapy, Psychiatry, Home Visits, Video Consultation, Lab Tests) as visual category tiles
- **Fast, mobile-first performance** — most Egyptian traffic will be mobile; homepage must be lightweight and fast on 3G/4G.
- **Arabic-first, RTL-native layout** with a calm, medically-appropriate color palette (soft/muted blues as the primary brand color, paired with a clean white/neutral background and an accent color for CTAs), and strong, readable Arabic typography (a proper Arabic web font, not a Latin font with Arabic fallback).

## 3. SEO Architecture

Hakeem needs deep, programmatic SEO coverage because "find a doctor near me" and "[specialty] in [city]" searches are the primary organic acquisition channel.

### 3.1 Page types to auto-generate
- `/{city-slug}` — all clinics/doctors in a city
- `/{governorate-slug}` — overview + list of cities within it
- `/{specialty-slug}` — all doctors/clinics for a specialty nationally
- `/{city-slug}/{specialty-slug}` — the highest-value long-tail page (e.g., "Dentists in Nasr City")
- `/doctor/{doctor-slug}` and `/clinic/{clinic-slug}` — individual profile pages
- `/service/{service-slug}` — pages per service type (Home Visit, Video Consultation, Home Lab Test, Psychiatric Consultation, etc.)

### 3.2 On-page SEO requirements
- Bilingual meta titles/descriptions per page (Arabic primary, English via hreflang alternate)
- Structured data (schema.org `MedicalOrganization`, `Physician`, `MedicalClinic`, `AggregateRating`) on profile and listing pages
- Server-rendered content for these pages (not client-only rendering) so crawlers see full content
- Fast Core Web Vitals — this is a Laravel Blade + Alpine.js server-rendered stack for exactly this reason (SEO-critical pages are not an SPA)
- XML sitemaps auto-regenerated as clinics/doctors/cities/specialties are added
- Admin-editable meta title/description overrides per generated page (§1.8 in `04-admin-dashboard-support.md`)

## 4. Ratings & Reviews

- Patients can rate a doctor/clinic **after a completed booking** (rating prompts fire automatically post-visit).
- Rating dimensions: overall stars (1–5) + optional short written review + optional sub-ratings (e.g., wait time, staff friendliness, cleanliness) to make aggregate scores meaningful.
- Aggregate rating and review count are shown on every doctor/clinic profile and in search results, and feed the "Top Rated" homepage section.
- Reviews are moderatable from the Admin dashboard (hide/flag abusive or fake reviews); clinics can publicly respond to a review but cannot delete it.
- Verified-visit badge on reviews (only patients with a completed booking at that clinic can review it) to prevent fake reviews.

## 5. Offers & Promotions

- Clinics can create promotional pricing on **individual catalog items** (e.g., "20% off dental cleaning," "30% off Botox this month," a massage bundle deal) via each item's `promo_price`/`promo_starts_at`/`promo_ends_at` fields — see the full dental/cosmetic/beauty/massage catalog in `02-booking-services.md` §5 — subject to what their subscription plan allows (`03-clinic-doctor-onboarding-subscriptions.md` §3).
- Admin can create platform-wide promotions/campaigns (e.g., seasonal health-check campaigns) independent of any single clinic.
- Promotions have a start/end date, are automatically surfaced on the homepage and on relevant SEO landing pages while active, and automatically expire.
- Promotional notifications (opt-in) go out to patients who have previously booked with or followed that clinic, via the channels defined in `05-notifications-agent-whatsapp.md` §4.
