# Admin Dashboard, Roles, Medical Record Consent & Support

## 1. Admin Dashboard Scope (build this first)

The Platform Admin Dashboard is the foundation everything else depends on. It must manage:

1. **Geography** — Governorates and Cities (CRUD, Arabic/English names, slugs for SEO URLs)
2. **Specialties & Service Categories** — the master list of medical specialties (general, dental, cosmetic, physical therapy, psychiatry, etc.) and the six service types (§ in `02-booking-services.md`)
3. **Subscription Plans** — the 3-plan system, pricing, booking caps, feature flags per plan, discount/campaign codes
4. **Clinic/Doctor moderation** — approve/reject/suspend clinic and doctor accounts if moderation is enabled; verification badges
5. **Payment configuration** — global default payment modes, gateway credentials, per-clinic overrides allowed or not
6. **Promotions & Offers** — create platform-wide or clinic-specific promotional pricing/banners, schedule start/end dates, feature them on the homepage
7. **Ratings & Reviews moderation** — hide/flag abusive reviews, respond on behalf of platform if needed
8. **Content/SEO management** — manage the auto-generated landing pages (by Governorate × Specialty, City × Specialty), meta titles/descriptions, blog/health-content section
9. **Support tickets** — inbox for chat/WhatsApp support requests raised by patients or clinics (see §6)
10. **Analytics** — bookings by service type/Governorate/City/time, revenue, active clinics, churn, top doctors/clinics, plan distribution
11. **Users & Roles** — manage internal admin/support staff accounts and their permission scopes

## 2. Role & Permission Model

| Role | Scope |
|---|---|
| **Platform Admin** | Full access to everything in §1 |
| **Support Agent** | Support tickets, read-only view into a booking/clinic to help troubleshoot |
| **Clinic Owner** | Full control of their own clinic: addresses, doctors, services, subscription, promotions, analytics for their clinic only |
| **Doctor** | Their own schedule, their patients' bookings, consultation notes/prescriptions, their own profile |
| **Reception** | Booking queue management for their clinic (or a specific address), no billing/subscription access |
| **Patient** | Their own profile, bookings, medical record, ratings |

All roles are enforced server-side (not just hidden UI) — this is a healthcare product and access control is a compliance requirement, not just UX.

## 3. Medical Record Consent Model (cross-clinic access)

This is one of the most sensitive flows in the product:

1. Every patient has **one Medical Record**, keyed to their phone number / membership number, independent of any single clinic.
2. By default, **only the clinic(s) where a booking/visit actually took place** can see the records/results/prescriptions generated during that visit.
3. If **Clinic B** (which the patient has never visited) wants to view the patient's full record or a specific prior result (e.g., the patient mentions a previous lab test), Clinic B must **request access**.
4. The patient receives a **push notification / SMS** asking them to approve or deny that specific clinic's access request, with a time-bound grant (e.g., access for 24–72 hours, or "for this visit only") rather than a permanent blanket grant.
5. All access grants and requests are logged in an audit trail visible to the patient ("Who has viewed my record and when").
6. **Exception:** results/notes generated during a booking automatically belong to that clinic's view of the record (no separate consent needed for the clinic that actually generated them).
7. **Psychiatric consultations** get an extra layer of restriction by default (see `02-booking-services.md` §3.6) — even the originating clinic's non-doctor staff should not see consultation content, only the assigned doctor and the patient.

## 4. Patient Dashboard/App — Medical Record View

From the patient's dashboard or mobile app, the patient can see, in one place:
- All past bookings (by clinic, date, service type, status)
- All lab results, structured by test name/date/clinic, with the ability to view/download the report
- All prescriptions, each rendered with clinic branding, doctor name, and a verification code
- Pending consent requests from clinics wanting to view their record (approve/deny)
- A log of who has accessed their record and when

## 5. Support Flow

A patient or clinic experiencing an issue (chat glitch, WhatsApp not responding, payment problem, access-request confusion) can reach **Platform Support** via:
- In-app/web live chat widget
- WhatsApp (same number used for the Hermes booking agent, but routed to a human support queue when the message is tagged as a support/complaint intent rather than a booking intent)

Support tickets land in the Admin Dashboard's Support inbox (§1.9), assignable to Support Agents, with SLA/status tracking (open, in progress, resolved).

## 6. Notifications Admin Controls

Admin can configure, per notification type (new booking, status change, result ready, prescription issued, access request, promotion), which channels are enabled: push, SMS, WhatsApp, email — see `05-notifications-agent-whatsapp.md` for the full notification/channel matrix.
