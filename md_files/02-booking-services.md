# Booking Services & Flows

Hakeem supports six bookable service types. They all use the **same Booking engine** (same table, same status machine, same notification system) but differ in required inputs and fulfillment steps.

## 1. Service Types

| # | Service Type | Where it happens | Needs an Address? | Needs a time slot? |
|---|---|---|---|---|
| 1 | Clinic Appointment | At the clinic | Yes | Yes |
| 2 | Home Visit | Patient's home | No (patient address instead) | Yes |
| 3 | Video Consultation | Online | No | Yes |
| 4 | Lab Test (in-clinic/lab) | At the clinic/lab | Yes | Yes (or walk-in window) |
| 5 | Home Lab Test | Patient's home | No (patient address instead) | Yes |
| 6 | Online Psychiatric Consultation | Online, video or chat | No | Yes |

All six also support: dental & cosmetic clinic services, beauty/hydration/massage services, and physical therapy sessions (see §5) — these are **Service Categories** layered on top of the six service types above, not separate types.

## 2. Common Booking Flow (all types)

1. **Discover** — patient searches/filters on the homepage by Governorate/City + Specialty/Service, or opens a specific clinic/doctor profile.
2. **Choose service** — patient picks one of the six service types offered by that clinic/doctor.
3. **Choose slot** — patient picks a Clinic Address (if applicable) and an available time slot, based on that address's working hours.
4. **Patient details** — if the patient is new/unauthenticated, a lightweight registration happens inline (phone + name + password) without leaving the booking flow.
5. **Confirm** — booking is created with status `pending_confirmation` (or `confirmed` automatically if the clinic has auto-accept enabled).
6. **Notify** — patient and clinic/doctor both get a notification (push + SMS/WhatsApp fallback).
7. **Status changes** — clinic/reception can move the booking through: `pending → confirmed → in_progress → completed` or `cancelled / no_show`. Every transition fires a notification to the patient.
8. **Payment** — depending on the clinic's admin-configured payment mode: pay online now, pay at the clinic, or pay after service (see `03-clinic-doctor-onboarding-subscriptions.md` §4).
9. **Post-visit** — patient is prompted to rate the doctor/clinic; any prescription or lab result issued during the visit is attached to the patient's Medical Record automatically.

## 3. Service-Specific Notes

### 3.1 Clinic Appointment (most common flow)
- Slot availability is derived from the chosen Clinic Address's working hours minus already-booked slots minus doctor's blocked time.
- Reception can also create/manage this booking manually (walk-ins, phone bookings) from the clinic dashboard queue.

### 3.2 Home Visit
- Patient provides their home address (street/area/City) instead of picking a clinic address.
- Clinic sets a service radius and/or a home-visit surcharge per City.
- Doctor/nurse assignment happens from the clinic side after confirmation.

### 3.3 Video Consultation
- On confirmation, a video room link/token is generated (WebRTC or a third-party video SDK) and sent to both patient and doctor ahead of the appointment time.
- Chat is available during the call for sharing files (e.g., prior lab results).

### 3.4 Lab Test (in-clinic) & 3.5 Home Lab Test
- Booking specifies which test(s) are requested (multi-select from the clinic's test catalog).
- Home Lab Test additionally requires a home address and a collection time window.
- Results, once ready, are uploaded by clinic/lab staff and attached to the patient's Medical Record; patient is notified the moment results are available, viewable from dashboard or app as structured "Results," not just a raw file.

### 3.6 Online Psychiatric Consultation
- Same as Video Consultation, but flagged as a sensitive category: stricter privacy on record visibility (see `04-admin-dashboard-support.md` §5 on consent), and the option for the patient to book anonymously by display name only (while the account itself is still tied to their verified phone number for safety/compliance).

## 4. The "Most Common" Path

Per the product brief, the single most common patient journey is: **search → book a clinic appointment → go to the clinic → doctor treats them.** This flow must have the fewest steps and fastest time-to-confirm of all six service types, and should be the default tab/CTA on every clinic/doctor profile page.

## 5. Dental, Cosmetic & Beauty Service Catalog

Dental, cosmetic, and beauty clinics don't just get a category tag — they get a **real, itemized service catalog** per clinic, because each individual service (Botox, a filler type, a whitening session, a specific massage) has its own price, duration, and its own promotional offer, independent of the others. This catalog is what patients browse, compare, and book from, and what the Offers engine (`06-seo-homepage-discovery-ratings.md` §5) attaches promotions to.

### 5.1 Data shape: `clinic_service_items`
Each clinic maintains a list of individually bookable, individually priced items, e.g.:

```
clinic_service_items
  id, clinic_id, category (dental / cosmetic / beauty / massage / physical_therapy),
  name_ar, name_en, description_ar, description_en,
  duration_minutes, price, promo_price (nullable), promo_starts_at, promo_ends_at,
  is_active, requires_evaluation_first (bool), display_order
```

A `clinic_service_item` is what actually gets attached to a Booking (instead of just a generic "Clinic Appointment"), so the patient sees exactly what they're booking and paying for, and the clinic/admin can run an offer on that one item without discounting the whole clinic.

### 5.2 Dental Services (category = dental)
Typical catalog items a dental clinic can list, each independently priced/offered:
- Check-up & consultation, cleaning/scaling & polishing
- Fillings, root canal treatment
- Extraction (simple / surgical / wisdom tooth)
- Implants, crowns & bridges
- Whitening (in-clinic session)
- Orthodontics/braces (consultation + monthly adjustment sessions)
- Pediatric dentistry

### 5.3 Cosmetic / Aesthetic Services (category = cosmetic)
- **Botox** (by treatment area — e.g., forehead, crow's feet — each can be its own catalog item with its own price)
- **Fillers** (lip filler, cheek filler, under-eye filler — again, separate items/prices per area/product)
- Mesotherapy, thread lifts, chemical peels
- Laser hair removal (by body area)
- Skin hydration / rejuvenation facials
- Body contouring / sculpting sessions

### 5.4 Beauty / Massage / Wellness Services (category = beauty)
- Relaxation, deep-tissue, and therapeutic massage (each a separate priced item)
- Skin hydration and moisturizing facials
- Manicure/pedicure-adjacent medi-spa services, if the clinic offers them
- Wellness/spa packages (bundles of the above, priced as a package item)

### 5.5 Physical Therapy & Sessions (category = physical_therapy)
Catalog items here are typically session-based (e.g., "Post-Injury Rehab Session," "Back Pain Program Session"). Special rule that applies regardless of catalog item: **the first session in any physical-therapy plan is always an "Evaluation Session"** — auto-flagged on the booking (`is_evaluation = true`), and its outcome (evaluation notes) is always written to the patient's Medical Record, regardless of the clinic's normal record-sharing settings, because it defines the treatment plan for all follow-up sessions. A catalog item can be marked `requires_evaluation_first = true` so the booking flow automatically inserts/requires the evaluation session before any follow-up session can be booked.

### 5.6 Offers on Individual Services
Because each item has its own `promo_price` / `promo_starts_at` / `promo_ends_at`, a clinic (or the platform admin, for platform-wide campaigns) can run offers like "30% off Botox this month" or "Buy 5 massage sessions, get 1 free" (modeled as a package catalog item) without touching pricing on anything else the clinic offers. Active item-level offers surface automatically on:
- The clinic/doctor profile page (badge on the discounted item)
- The relevant SEO service/specialty pages (`06-seo-homepage-discovery-ratings.md` §3)
- The homepage "Current Offers & Promotions" section, if the offer is flagged as featured

### 5.7 Booking Flow Impact
Step 2 of the common booking flow ("Choose service," `02-booking-services.md` §2) becomes, for these clinics: patient picks a **specific catalog item** (e.g., "Under-eye Filler — 45 min — 1,200 EGP, was 1,500 EGP") rather than a generic appointment — the rest of the flow (slot selection, confirmation, payment, notifications) is unchanged.

## 6. Prescriptions Issued During a Booking

After (or during) a Clinic Appointment / Home Visit / Video Consultation, the doctor can write a **Consultation Note** and, if needed, a **Prescription**:

- Prescription is rendered as a branded PDF/document: clinic logo, clinic name, doctor name and credentials, patient name, date, medications with dosage instructions, and a verification code/QR that links back to Hakeem's record of that prescription (to prevent tampering and enable dispensing pharmacies to verify authenticity).
- Prescription is pushed to the patient's Medical Record and to their dashboard/app immediately, with a "ready to dispense" state.

## 7. Booking via Chat / WhatsApp (Hermes Agent)

Everything in §2–§6 must also be achievable through the Hermes conversational agent (see `05-notifications-agent-whatsapp.md`) — the agent calls the exact same booking API the web/app use, so there is only one source of truth for slots, statuses, and records.
