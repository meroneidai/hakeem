# SEO, Homepage Discovery, Ratings & Promotions

## 1. Why This Matters Most on the Homepage

The homepage is the single highest-leverage page in the product: it must let a patient go from "landing on the site" to "booked" in the fewest clicks, while also being the strongest SEO asset the platform has.

## 2. Homepage Requirements

- **Prominent search + filter bar**: keyword on `/search`; governorate, specialty, city, gender, and years of experience on `/doctors`; clinic directory filters on `/clinics`.
- **Featured sections**:
  - "Most Booked This Week" doctors/clinics (ranked by recent booking volume — Phase 7 once bookings exist)
  - "Top Rated" doctors/clinics (ranked by rating + review count — Phase 7)
  - Live now: featured specialties, featured lab packages, running offers, experienced doctors, listable clinics, city chips
  - Public surfaces: `/doctors`, `/clinics`, `/specialties`, `/services`, `/cities`, `/labs` (tests + packages + cart), `/offers` (inclusions, conditions, before/after price)
- **Fast, mobile-first performance** — most Egyptian traffic will be mobile; homepage must be lightweight and fast on 3G/4G.
- **Arabic-first, RTL-native layout** with a calm, medically-appropriate color palette (soft/muted blues as the primary brand color, paired with a clean white/neutral background and an accent color for CTAs), and strong, readable Arabic typography (a proper Arabic web font, not a Latin font with Arabic fallback).

## 3. SEO Architecture

Hakeem needs deep, programmatic SEO coverage because "find a doctor near me" and "[specialty] in [city]" searches are the primary organic acquisition channel.

Public URLs follow `hakeem-all-ui-interfaces/hakeem-website-seo-pages.md`. Locale prefixes `/ar` and `/en` are specified there but are **not** live yet — the app keeps a session locale switcher so existing links and the language toggle keep working.

### 3.1 Page types to auto-generate

Generator paths must match the public UI, not unprefixed `/{slug}` routes:

- `/cities/{governorate-slug}` — governorate overview (generator record; city pages below are the live directory)
- `/cities/{city-slug}` — clinics/doctors in a city (live: `CityDirectoryController`)
- `/specialties/{specialty-slug}` — doctors for a specialty nationally (live)
- `/services/{service-slug}` — service type landing (live; `/service/{slug}` is retired)
- `/specialties/{specialty-slug}/{city-slug}` — highest-value long-tail page (generator; live combo pages are Phase 2)
- `/doctors/{doctor-slug}` and `/clinics/{clinic-slug}` — individual profiles (live; not `/doctor/` or `/clinic/`)

`/search`, `/login`, `/register`, and `/book/*` are `noindex`.

Ratings, medical library, and `/cities/{city}/{area}` stay later phases — do not invent thin pages for them.

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

- Public offers (`/offers`) show category, what is included, conditions, original vs offer price, clinic, and end date — the same information patients see on Vezeeta, with clearer Arabic copy.
- Clinics create those offers from `/clinic/offers`. Admin can also create platform-wide or clinic-scoped campaigns from `/admin/promotions`.
- Clinics can still set promotional pricing on **individual catalog items** via `promo_price` — see `02-booking-services.md` §5 — subject to the subscription plan.
- Promotional notifications (opt-in) go out to patients who have previously booked with or followed that clinic, via the channels defined in `05-notifications-agent-whatsapp.md` §4.
