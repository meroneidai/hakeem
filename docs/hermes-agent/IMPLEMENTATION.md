# Hakeem × Hermes — Agent implementation pack

Upload this folder to Hermes. Laravel already exposes the customer-service API. Hermes only needs to call it, keep a short conversation state, and follow the auth rules below.

| Item | Value |
| --- | --- |
| Base URL | `{HAKEEM_BASE_URL}/api/agent/v1` |
| Local example | `http://localhost:8000/api/agent/v1` |
| Auth header | `X-Hermes-Key: {HERMES_AGENT_KEY}` |
| Locale | `locale=ar` query **or** `X-Locale: ar` / `en` |
| Live catalog | `GET /api/agent/v1` (same tools as `collection.openapi.json`) |
| Import file | `collection.openapi.json` |

Replace `{HAKEEM_BASE_URL}` and `{HERMES_AGENT_KEY}` in Hermes secrets. Never put the key in the system prompt or in chat logs.

---

## What is already built on Hakeem

Do **not** rebuild these on the Hermes side. They are live on the platform.

- Public search: doctors, clinics, services, offers, labs
- Doctor / clinic / offer details and appointment slots
- Geography, specialties, service types
- Visitor help, FAQ, contact details, search suggestions
- Signup campaigns and featured offers
- Patient register, login, password reset
- Profile read/update after the visitor proves identity
- Own bookings list (read only)
- Support tickets

Hermes is a separate chat/WhatsApp service. It talks to Hakeem only through `/api/agent/v1`.

The site widget talks to Hermes the other way: the browser posts to Hakeem (`POST /agent/messages`), and Hakeem forwards the turn to `HERMES_CHAT_URL`. The Hermes key never reaches the browser. Hermes then uses `/api/agent/v1` to search, share booking links, register a patient, list appointments, or open a ticket.

---

## What Hermes must implement

### 1. Secrets and HTTP client

Store:

- `HAKEEM_BASE_URL` — site origin, no trailing slash
- `HERMES_AGENT_KEY` — same value as Hakeem `HERMES_AGENT_KEY`

Send on **every** request:

```http
X-Hermes-Key: {HERMES_AGENT_KEY}
Accept: application/json
X-Locale: ar
```

Optional after the visitor signs in:

```http
Authorization: Bearer {customer_token}
```

Timeouts: 10s for search/help, 15s for register/login/tickets. Retry once on `502/503/504`. Do not retry `401/422`.

### 2. Conversation memory (short)

Keep per conversation:

| Field | Purpose |
| --- | --- |
| `locale` | `ar` or `en` |
| `customer_token` | Sanctum token after login/register/profile lookup |
| `customer_name` | Display name only |
| `last_search` | Last `q`, city, specialty |
| `pending_intent` | `register`, `reset_password`, `profile`, `ticket` |

If `customer_token` is set, **do not ask for password again**.

Clear `customer_token` on `401` from a customer endpoint.

### 3. Tool routing

Map visitor intent to one tool. Prefer `GET /help` and `GET /suggestions` before guessing.

| Visitor wants | Call |
| --- | --- |
| “What can you do?” / كيف أحجز | `GET /help` |
| Incomplete search (“دكتور أطفال…”) | `GET /suggestions?q=` |
| Find a doctor / clinic / lab / offer | `GET /search` |
| Details after a search hit | `GET /doctors/{slug}`, `/clinics/{slug}`, `/offers/{slug}` |
| Available times | `GET /doctors/{slug}/slots` |
| Current deals | `GET /offers` and `GET /campaigns` |
| City / specialty list | `GET /reference/*` |
| Create account | `POST /customers` |
| Sign in | `POST /customers/session` |
| Forgot password | `POST /customers/password/forgot` then `/reset` |
| My profile / update profile | `GET /customers/me` if token exists, else `POST /customers/me` |
| My appointments | `GET` or `POST /customers/bookings` |
| Complaint / support | `POST /support/tickets` |

### 4. Identity rules (required)

Never fetch or update a profile without one of:

1. `Authorization: Bearer {customer_token}` from this conversation, or
2. Phone **or** email **plus** password that the visitor just typed

Ask in Arabic by default:

> للوصول إلى حسابك أرسل رقم الموبايل أو الإيميل وكلمة المرور. إذا كنت مسجّلاً في المحادثة لن أطلبها مرة أخرى.

Do **not**:

- Guess phone numbers
- Reuse a password from another conversation
- Read another person’s bookings
- Call `/api/v1/admin/*` or any delete / clinic-management route

Ticket create is allowed without a password (name + phone + subject + body).

### 5. Reply style

- Arabic first. Switch to English only if the visitor writes in English.
- Answer with short facts from the API, then one next question.
- Include public page URLs from the payload (`url`, `book_url`) so the visitor can finish booking on the site.
- Do not invent prices, slots, or doctor names. If search is empty, say so and offer a suggestion.
- Do not cancel bookings or add clinic services. This collection cannot do that.

### 6. Error handling

| Status | Hermes action |
| --- | --- |
| `401` + `hermes_key_required` | Stop. The Hermes key is missing or wrong. Fix secrets. |
| `401` + `customer_credentials_required` | Ask for phone/email + password, or use the existing token. |
| `422` | Show the validation message. Ask only for the missing field. |
| `404` | That doctor/clinic/offer is not public. Search again. |
| `429` | Tell the visitor to wait a minute. |

Password-forgot always looks successful. Do not say “this number is not registered”.

---

## Setup checklist

On **Hakeem**

1. Set `HERMES_AGENT_KEY` in `.env` (long random string).
2. Set `APP_URL` to the public origin Hermes will call.
3. Set `HERMES_CHAT_URL` to the Hermes inbound chat endpoint (OpenAI-compatible `/v1/chat/completions`, or a webhook). Optional: `HERMES_CHAT_KEY`, `HERMES_CHAT_MODEL`.
4. Run `php artisan config:clear` after changing env.
5. Confirm tools: `curl -H "X-Hermes-Key: $HERMES_AGENT_KEY" $APP_URL/api/agent/v1`
6. Confirm the widget: open the site chat and send a message. The panel shows `source: hermes` in the JSON response when the inbound URL is set.

On **Hermes**

1. Import `collection.openapi.json`.
2. Set secrets `HAKEEM_BASE_URL` and `HERMES_AGENT_KEY`.
3. Paste the system prompt in the next section.
4. Enable only the tools in this collection. Disable admin or delete tools if the host adds them later.
5. Test: help → search → register → ticket.

---

## System prompt (paste into Hermes)

```
You are Hermes, Hakeem’s patient assistant for Egypt. Speak Arabic unless the visitor uses English.

You help visitors find doctors, clinics, labs, offers and campaigns; explain how booking works; create a patient account; reset a password; update a profile after the visitor proves identity; list that visitor’s appointments; and open a support ticket.

Call Hakeem only at {HAKEEM_BASE_URL}/api/agent/v1 with header X-Hermes-Key. Add Authorization: Bearer {customer_token} when this conversation already has a token. Never ask for a password if a token is already stored.

Never read a profile or bookings without a token or phone/email+password. Never call admin APIs, never delete records, never add or edit clinic services.

Use GET /help and GET /suggestions before guessing. Use search results and URLs from the API. Do not invent doctors, prices, or slots.

If the visitor wants to book, give the book_url from the API. Do not claim payment was charged.
```

---

## Request examples

All examples need `X-Hermes-Key`.

Search:

```http
GET /api/agent/v1/search?q=جلدية&type=doctors&locale=ar
```

Register:

```http
POST /api/agent/v1/customers
Content-Type: application/json

{"name":"منى إبراهيم","phone":"01055512345","password":"password12"}
```

Save `token` from the response as `customer_token`.

Profile without a token:

```http
POST /api/agent/v1/customers/me
Content-Type: application/json

{"phone":"01055512345","password":"password12"}
```

Profile with a token (do not ask again):

```http
GET /api/agent/v1/customers/me
Authorization: Bearer {customer_token}
```

Support ticket:

```http
POST /api/agent/v1/support/tickets
Content-Type: application/json

{
  "name": "منى إبراهيم",
  "phone": "01055512345",
  "subject": "مشكلة في الحجز",
  "body": "لم يصل تأكيد الموعد",
  "category": "booking"
}
```

Ticket categories: `booking`, `payment`, `record_access`, `technical`, `complaint`, `other`.

---

## Out of scope (do not implement on Hermes)

- Admin dashboard, staff roles, billing, clinic onboarding
- Creating, editing, or deleting clinic services
- Cancelling or creating bookings through this collection (send the visitor to `book_url`)
- Reading medical records or lab files
- Charging a payment gateway

Those stay on the Hakeem website or a later API slice.

---

## Files in this folder

| File | Upload to Hermes as |
| --- | --- |
| `IMPLEMENTATION.md` | Knowledge / instructions |
| `collection.openapi.json` | HTTP tool collection (OpenAPI 3.1) |
| `tools.json` | Lightweight tool list if the host does not accept OpenAPI |
