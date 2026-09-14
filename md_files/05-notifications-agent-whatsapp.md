# Notifications & the Hermes Booking Agent (Chat/WhatsApp)

## 1. Why a Conversational Channel

Many patients in Egypt will find it faster/more natural to book "by typing" on WhatsApp or an in-site chat widget than to navigate a full booking UI. Hakeem treats this as a **first-class booking channel**, not a support add-on — a patient should be able to complete an entire booking (find a clinic, pick a slot, confirm, get a reminder) without ever opening the web app.

## 2. Hermes Agent — Architecture

- **Hermes** is a conversational AI agent, deployed as a **separate service outside the main Laravel server** (its own process/container), so it can be scaled, restarted, or upgraded independently of the core platform.
- Hermes talks to the Hakeem platform exclusively through the **same public Booking API** used by the web app and mobile app (`example.com/api/v1` and a dedicated `example.com/api/agent/v1` surface for agent-specific needs like conversation state) — this guarantees the agent can never create a booking that violates the same slot/availability/consent rules as any other channel.
- Channels Hermes listens on: WhatsApp Business API, and an embedded web chat widget on the Hakeem site/app.
- Hermes' core responsibilities:
  1. Understand intent: booking a new service, checking/rescheduling/cancelling an existing booking, asking about results, or routing to human support.
  2. Disambiguate: ask for Governorate/City, specialty or symptom, preferred date/time, service type — using the same Egypt geography and specialty taxonomy as the web filters.
  3. Confirm and execute the booking via the API, then hand back a normal confirmation message + a deep link into the app/web for full details.
  4. Escalate to a human Support Agent (see `04-admin-dashboard-support.md` §5) when it detects a complaint/problem intent rather than a booking intent.

## 3. Notification Types & Recipients

| Event | Patient notified? | Doctor/Clinic notified? |
|---|---|---|
| Booking created (pending) | ✔ (confirmation of request) | ✔ (new booking alert) |
| Booking confirmed | ✔ | — |
| Booking rescheduled | ✔ | ✔ |
| Booking cancelled | ✔ | ✔ |
| Reminder (e.g., 24h / 1h before) | ✔ | ✔ (optional) |
| Lab result ready | ✔ | — |
| Prescription issued | ✔ | — |
| Cross-clinic record access request | ✔ (must approve/deny) | ✔ (the requesting clinic, once approved) |
| Promotion/offer from a followed/visited clinic | ✔ (opt-in) | — |
| Subscription plan status (e.g., limit reached) | — | ✔ |

## 4. Delivery Channels per Notification

Each notification type can be configured (per `04-admin-dashboard-support.md` §6) to fire on any combination of:
- **Push notification** (mobile app, via Firebase Cloud Messaging)
- **SMS** (fallback for users without the app installed, or for critical alerts like booking confirmation)
- **WhatsApp message** (via the same Business API integration used by Hermes)
- **Email** (mainly for clinics/doctors — receipts, weekly summaries, account/billing notices)
- **In-app notification center** (always on, regardless of the above)

## 5. Reliability Requirements

- Notification dispatch is queued (Laravel Queues) so a spike in bookings never blocks the request/response cycle.
- Failed WhatsApp/SMS sends fall back automatically to the next available channel (e.g., WhatsApp fails → SMS retried) so a patient never misses a booking confirmation.
- All notification sends are logged for support troubleshooting ("did the patient actually receive the reminder?").
