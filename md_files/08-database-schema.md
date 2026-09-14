# Database Schema (Outline)

This is a starting entity-relationship outline — enough to begin migrations. Column lists are representative, not exhaustive.

## Geography
```
governorates
  id, name_ar, name_en, slug

cities
  id, governorate_id (FK), name_ar, name_en, slug
```

## Identity & Access
```
users
  id, phone (unique, primary login), name, password_hash, email (nullable for patients, required for clinic/doctor accounts),
  preferred_language (ar/en), created_at

roles
  id, name (platform_admin, support_agent, clinic_owner, doctor, reception, patient)

role_user
  user_id (FK), role_id (FK), clinic_id (FK, nullable — scopes reception/doctor to a clinic)
```

## Clinics, Doctors, Addresses
```
clinics
  id, owner_user_id (FK), name_ar, name_en, logo_path, email, phone,
  is_single_doctor (bool), subscription_plan_id (FK), verification_status,
  created_at

doctors
  id, user_id (FK), name_ar, name_en, bio_ar, bio_en, specialty_id (FK),
  credentials, profile_photo_path

clinic_doctor
  clinic_id (FK), doctor_id (FK)

clinic_addresses
  id, clinic_id (FK), city_id (FK), address_line, latitude, longitude

address_schedules
  id, clinic_address_id (FK), day_of_week, open_time, close_time, slot_duration_minutes, break_start, break_end

doctor_address_availability
  doctor_id (FK), clinic_address_id (FK), day_of_week, open_time, close_time
```

## Specialties & Services
```
specialties
  id, name_ar, name_en, slug, category (general/dental/cosmetic/physical_therapy/psychiatry/...)

service_types
  id, code (clinic_appointment, home_visit, video_consultation, lab_test, home_lab_test, psychiatric_consultation)

clinic_services
  id, clinic_id (FK), service_type_id (FK), specialty_id (FK, nullable),
  price, promo_price (nullable), is_active
```

## Dental / Cosmetic / Beauty Service Catalog
```
clinic_service_items
  id, clinic_id (FK), category (dental/cosmetic/beauty/massage/physical_therapy),
  name_ar, name_en, description_ar, description_en,
  duration_minutes, price, promo_price (nullable), promo_starts_at, promo_ends_at,
  is_active, requires_evaluation_first (bool), display_order
```
`bookings.clinic_service_item_id` (nullable FK) is added so a booking can reference a specific catalog item (e.g., a specific Botox area or massage type) rather than only a generic `service_type_id`. See `02-booking-services.md` §5 for the full catalog breakdown (dental, cosmetic, beauty/massage, physical therapy).

## Bookings
```
bookings
  id, patient_id (FK -> users), clinic_id (FK), doctor_id (FK, nullable),
  clinic_address_id (FK, nullable), service_type_id (FK),
  scheduled_at, status (pending, confirmed, in_progress, completed, cancelled, no_show),
  is_evaluation (bool, for physical therapy first session),
  payment_mode (online, at_clinic, after_service), payment_status,
  patient_home_address (nullable, for home_visit/home_lab_test),
  created_at, updated_at

booking_status_history
  id, booking_id (FK), status, changed_by_user_id (FK), changed_at
```

## Medical Record
```
medical_records
  id, patient_id (FK, unique) -- one record per patient

lab_results
  id, medical_record_id (FK), booking_id (FK, nullable), clinic_id (FK),
  test_name, result_file_path, structured_result_json, uploaded_at

prescriptions
  id, medical_record_id (FK), booking_id (FK), clinic_id (FK), doctor_id (FK),
  document_path, verification_code, issued_at

record_access_grants
  id, medical_record_id (FK), requesting_clinic_id (FK), status (pending, approved, denied, expired),
  scope (full, single_result), expires_at, requested_at, decided_at

record_access_audit_log
  id, medical_record_id (FK), accessed_by_clinic_id (FK), accessed_by_user_id (FK), accessed_at
```

## Subscriptions & Billing
```
subscription_plans
  id, name_ar, name_en, monthly_price, yearly_price, yearly_discount_pct,
  booking_cap (nullable = unlimited), is_default_free

plan_feature_flags
  plan_id (FK), feature_code, is_enabled

clinic_subscriptions
  id, clinic_id (FK), plan_id (FK), billing_cycle (monthly/yearly), status, current_period_end

discount_codes
  id, code, discount_pct_or_amount, applies_to_plan_id (nullable = all), valid_from, valid_to, max_uses
```

## Ratings & Promotions
```
reviews
  id, patient_id (FK), clinic_id (FK), doctor_id (FK, nullable), booking_id (FK),
  overall_rating, sub_ratings_json, comment, is_hidden, created_at

promotions
  id, clinic_id (FK, nullable = platform-wide), title_ar, title_en, discount_details,
  starts_at, ends_at, is_active
```

## Support
```
support_tickets
  id, opened_by_user_id (FK), channel (chat/whatsapp), subject, status, assigned_agent_id (FK, nullable),
  created_at

support_messages
  id, ticket_id (FK), sender_user_id (FK), body, sent_at
```

## Notifications (Laravel's native `notifications` table, extended)
```
notifications
  id, notifiable_type, notifiable_id, type, data (json), channels_sent (json), read_at, created_at
```

Note: this schema will evolve during implementation; treat it as the v1 migration starting point, refined per module as each is built (see `09-roadmap-phases.md`).
