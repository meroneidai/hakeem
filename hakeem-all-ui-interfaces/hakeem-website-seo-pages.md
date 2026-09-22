# HAKEEM — Website, SEO & Public Pages Specification

## 1. Scope

This document covers **only the public Hakeem website, marketplace pages, SEO structure, URLs, content pages, and public-facing user flows**.

It does not define:
- Doctor dashboard
- Clinic dashboard
- Reception dashboard
- Admin dashboard
- Internal staff management
- Backend implementation
- Database schema
- Mobile application screens

---

# 2. Website Architecture

The public website should be a modern healthcare marketplace for Egypt.

Primary goals:

1. Search for doctors, clinics, specialties and services.
2. Discover healthcare providers by location.
3. Compare doctors and clinics.
4. View availability.
5. Start a booking.
6. Discover offers and services.
7. Access trusted medical content.
8. Support strong SEO for doctors, clinics, services, specialties and locations.
9. Support Arabic RTL and English LTR.
10. Work extremely well on mobile.

Supported languages:

- Arabic: `/ar/...`
- English: `/en/...`

Recommended default:

- Arabic for Egypt
- English as an alternative language

---

# 3. Main Website Routes

## Homepage

```text
/
```

Arabic:

```text
/ar
```

English:

```text
/en
```

Purpose:

- Main marketplace entry point
- Search
- Featured doctors
- Featured clinics
- Specialties
- Services
- Offers
- Locations
- Medical content
- App promotion
- SEO content

---

# 4. Main Navigation

## Desktop Header

Recommended navigation:

```text
Home
Doctors
Clinics
Specialties
Services
Offers
Home Care
Teleconsultation
Medical Library
```

Actions:

```text
Search
Login
Book Appointment
Language
```

For Arabic:

```text
الرئيسية
الأطباء
العيادات
التخصصات
الخدمات
العروض
الرعاية المنزلية
الاستشارة عن بُعد
المكتبة الطبية
```

---

# 5. Homepage Sections

Route:

```text
/
```

Recommended sections:

### Header

- Hakeem logo
- Main navigation
- Search
- Language switcher
- Login
- Mobile menu

### Hero

Main headline focused on healthcare search.

Example:

```text
Find the right doctor, clinic or healthcare service
```

Arabic:

```text
ابحث عن الطبيب أو العيادة أو الخدمة الطبية المناسبة لك
```

Main search:

```text
What are you looking for?
```

Search examples:

```text
طبيب جلدية
دكتور أطفال
عيادة أسنان
تحاليل
كشف باطنة
```

Location:

```text
City
Area
Near me
```

Optional date:

```text
Today
Tomorrow
This week
```

### Popular Searches

Examples:

- Dermatologist
- Dentist
- Pediatrician
- Internal Medicine
- Gynecologist
- Ophthalmologist
- Orthopedic
- Cardiologist

### Specialties

Display:

- Icon
- Specialty name
- Number of doctors
- Link

### Featured Doctors

Doctor card:

- Photo
- Name
- Specialty
- Rating
- Reviews
- Location
- Price
- Availability
- Verification badge
- Book button

### Featured Clinics

Clinic card:

- Logo
- Clinic name
- Specialty
- Rating
- Reviews
- Location
- Opening hours
- Services
- Book button

### Popular Services

Examples:

- Dental cleaning
- Dental filling
- Ultrasound
- X-ray
- Blood tests
- Home nursing
- Physiotherapy
- Consultation

### Offers

Offer card:

- Image
- Offer title
- Clinic/provider
- Original price
- Offer price
- Discount
- Validity
- Location
- View offer

### Home Care

Landing section linking to:

```text
/home-care
```

### Teleconsultation

Landing section linking to:

```text
/teleconsultation
```

### How Hakeem Works

Three/four steps:

1. Search
2. Compare
3. Book
4. Visit / consult

### Reviews

Patient reviews of Hakeem.

### App Promotion

Links to:

- iOS
- Android

### Medical Library

Latest medical articles.

### SEO Content

Homepage should include useful indexable content.

Examples:

```text
أفضل الأطباء في مصر
حجز كشف طبي أونلاين
أفضل العيادات في القاهرة
حجز موعد مع طبيب
```

### Footer

Include:

- Doctors
- Clinics
- Specialties
- Services
- Offers
- Cities
- Medical Library
- Home Care
- Teleconsultation
- About Hakeem
- Contact
- Help
- Terms
- Privacy
- Cookies
- Sitemap
- App links
- Social links

---

# 6. Search

Route:

```text
/search
```

Localized:

```text
/ar/search
/en/search
```

Search should be one of the most important website experiences.

## Search Types

Users can search for:

- Doctor
- Clinic
- Specialty
- Service
- Offer
- City
- Area

Examples:

```text
دكتور جلدية في القاهرة
دكتور أطفال في التجمع
عيادة أسنان في مدينة نصر
تحاليل في القاهرة
```

Potential future AI search:

```text
عايز دكتور أطفال قريب مني بكرة
```

The system can interpret:

```text
specialty = Pediatrics
location = current location
date = tomorrow
```

---

# 7. Search Results

Route:

```text
/search?query=
```

Possible URL:

```text
/search?q=dermatologist
```

Filters:

- Specialty
- Category
- Subcategory
- Doctor
- Clinic
- City
- Area
- Distance
- Price
- Rating
- Gender
- Availability
- Today
- Tomorrow
- Teleconsultation
- Home visit
- Offers
- Verified
- Language

Result tabs:

```text
All
Doctors
Clinics
Services
Offers
```

Mobile:

- Filter button
- Bottom-sheet filters
- Sort
- Map/list toggle

Sorting:

```text
Recommended
Highest rated
Nearest
Lowest price
Most reviews
Available today
```

---

# 8. Doctors

## Doctor Directory

Route:

```text
/doctors
```

Localized:

```text
/ar/doctors
/en/doctors
```

Page should include:

- H1
- Introductory SEO text
- Search
- Filters
- Doctor cards
- Pagination/infinite loading
- Related specialties
- Related cities

---

# 9. Doctor Profile

Route:

```text
/doctors/[slug]
```

Example:

```text
/doctors/ahmed-mohamed-dermatologist
```

Localized:

```text
/ar/doctors/[slug]
/en/doctors/[slug]
```

Page sections:

### Doctor Header

- Photo
- Name
- Specialty
- Verification
- Rating
- Reviews
- Experience
- Languages
- Short description

### About

### Specialties

### Services

### Locations

Each location:

- Clinic
- Address
- Map
- Working hours
- Price
- Availability

### Appointment Availability

- Date
- Time
- Available slots

### Booking CTA

Sticky on mobile.

### Reviews

### FAQ

### Similar Doctors

### Related Services

### Related Locations

### SEO Content

---

# 10. Clinics

## Clinic Directory

Route:

```text
/clinics
```

Localized:

```text
/ar/clinics
/en/clinics
```

Features:

- Search
- Filters
- Location
- Specialty
- Rating
- Services
- Availability
- Offers
- Verified clinics

---

# 11. Clinic Profile

Route:

```text
/clinics/[slug]
```

Example:

```text
/clinics/abc-medical-center
```

Page sections:

- Cover image
- Logo
- Clinic name
- Verification
- Rating
- Reviews
- About
- Locations
- Doctors
- Services
- Offers
- Opening hours
- Photos
- Map
- Reviews
- FAQ
- Booking CTA

For organizations with multiple branches:

```text
/clinics/[slug]
```

should show all locations.

Individual location URLs can optionally be added later:

```text
/clinics/[clinic-slug]/[location-slug]
```

---

# 12. Specialties

## Specialty Directory

Route:

```text
/specialties
```

Examples:

```text
Dermatology
Dentistry
Pediatrics
Cardiology
Internal Medicine
Orthopedics
Ophthalmology
Gynecology
Neurology
Psychiatry
ENT
Urology
```

Arabic examples:

```text
الجلدية
الأسنان
الأطفال
القلب
الباطنة
العظام
العيون
النساء والتوليد
المخ والأعصاب
الطب النفسي
الأنف والأذن والحنجرة
المسالك البولية
```

---

# 13. Specialty Detail

Route:

```text
/specialties/[slug]
```

Example:

```text
/specialties/dermatology
```

Page sections:

- H1
- Specialty description
- Doctors
- Clinics
- Services
- Offers
- Related locations
- FAQ
- Medical articles
- SEO content

Example SEO title:

```text
Best Dermatologists in Egypt | Hakeem
```

Arabic:

```text
أفضل أطباء الجلدية في مصر | حكيم
```

---

# 14. Services

## Service Directory

Route:

```text
/services
```

Service hierarchy:

```text
Category
  └── Subcategory
       └── Service
```

Example:

```text
Dental
  └── Dental Treatments
       ├── Dental Cleaning
       ├── Dental Filling
       ├── Root Canal
       └── Teeth Whitening
```

---

# 15. Service Detail

Route:

```text
/services/[slug]
```

Example:

```text
/services/dental-cleaning
```

Page sections:

- Service title
- Description
- Price range
- Doctors offering service
- Clinics offering service
- Locations
- Offers
- Booking CTA
- FAQ
- Related services
- Related specialties
- Medical articles
- SEO content

---

# 16. Offers

## Offers Directory

Route:

```text
/offers
```

Filters:

- Category
- Specialty
- City
- Area
- Price
- Discount
- Clinic
- Doctor
- Validity

---

# 17. Offer Detail

Route:

```text
/offers/[slug]
```

Page sections:

- Offer title
- Image
- Description
- Original price
- Offer price
- Discount
- Valid until
- Provider
- Location
- Included services
- Terms
- Booking CTA
- Related offers

---

# 18. Home Care

Route:

```text
/home-care
```

Purpose:

Promote healthcare services provided at the patient's home.

Examples:

- Doctor home visit
- Nursing
- Physiotherapy
- Blood collection
- Vaccination
- Elderly care
- Home medical services

Sections:

- Hero
- Services
- How it works
- Locations
- Available providers
- FAQs
- Booking CTA
- SEO content

---

# 19. Teleconsultation

Route:

```text
/teleconsultation
```

Sections:

- Hero
- How teleconsultation works
- Available specialties
- Available doctors
- Pricing
- Benefits
- FAQ
- Booking CTA
- SEO content

---

# 20. Cities

## Cities Directory

Route:

```text
/cities
```

Purpose:

SEO index for Egyptian locations.

Examples:

```text
Cairo
Giza
Alexandria
Qalyubia
Dakahlia
Gharbia
Sharqia
Port Said
Suez
Ismailia
Fayoum
Minya
Assiut
Sohag
Qena
Luxor
Aswan
```

---

# 21. City Pages

Route:

```text
/cities/[city]
```

Example:

```text
/cities/cairo
```

Localized:

```text
/ar/cities/cairo
/en/cities/cairo
```

Page sections:

- H1
- City introduction
- Doctors
- Clinics
- Specialties
- Services
- Offers
- Popular areas
- FAQs
- Medical content
- SEO content

---

# 22. Area Pages

Route:

```text
/cities/[city]/[area]
```

Example:

```text
/cities/cairo/new-cairo
```

Arabic example:

```text
/cities/cairo/new-cairo
```

Page sections:

- H1
- Doctors in area
- Clinics in area
- Services in area
- Specialties
- Offers
- Nearby areas
- FAQs
- SEO content

This hierarchy is important for local SEO.

---

# 23. Medical Library

Main route:

```text
/medical-library
```

Article route:

```text
/medical-library/[slug]
```

Category route:

```text
/medical-library/category/[slug]
```

Author route:

```text
/medical-library/author/[slug]
```

Content types:

- Medical guides
- Symptoms
- Diseases
- Treatments
- Prevention
- Tests
- Pregnancy
- Children
- Nutrition
- Mental health
- Dental health
- Skin health

---

# 24. Medical Article Page

Example:

```text
/medical-library/what-is-high-blood-pressure
```

Sections:

- Title
- Featured image
- Author
- Medical reviewer
- Last reviewed date
- Article content
- Table of contents
- References
- Related doctors
- Related services
- Related articles
- FAQ

Important trust elements:

```text
Written by
Medically reviewed by
Last reviewed
References
```

---

# 25. How Hakeem Works

Route:

```text
/how-it-works
```

Sections:

1. Search
2. Compare
3. Select
4. Book
5. Receive confirmation
6. Visit or consult
7. Review

---

# 26. About

Route:

```text
/about
```

Sections:

- About Hakeem
- Mission
- Vision
- Healthcare problem
- Hakeem solution
- Trust and safety
- Team
- Contact CTA

---

# 27. Contact

Route:

```text
/contact
```

Fields:

- Name
- Phone
- Email
- Subject
- Message

Optional:

```text
Patient
Doctor
Clinic
Partner
Other
```

---

# 28. Help Center

Route:

```text
/help
```

Categories:

- Booking
- Cancellation
- Rescheduling
- Payments
- Doctor questions
- Clinic questions
- Account
- Notifications
- Reviews
- Technical support

Possible article structure:

```text
/help/[slug]
```

---

# 29. Legal Pages

## Terms

```text
/terms
```

## Privacy

```text
/privacy
```

## Cookie Policy

```text
/cookies
```

## Cancellation Policy

```text
/cancellation-policy
```

## Medical Disclaimer

```text
/medical-disclaimer
```

## Accessibility

```text
/accessibility
```

---

# 30. Authentication Pages

Public authentication flow:

```text
/login
```

```text
/register
```

```text
/verify
```

Recommended phone-first flow:

```text
/login
    ↓
Enter phone
    ↓
OTP
    ↓
Existing user?
    ├── Yes → Continue
    └── No → Complete profile
```

Possible routes:

```text
/auth/login
/auth/otp
/auth/register
/auth/complete-profile
```

Authentication should support:

- Egyptian phone numbers
- SMS OTP
- WhatsApp OTP where available
- English
- Arabic
- RTL
- Account recovery

---

# 31. Public Booking Flow

Recommended route structure:

```text
/book/[provider]
```

or booking as a stateful flow without exposing every step as an SEO page.

Flow:

```text
Provider
    ↓
Service
    ↓
Location
    ↓
Date
    ↓
Time
    ↓
Patient
    ↓
Confirmation
```

Example:

```text
Doctor
→ Dermatology consultation
→ New Cairo clinic
→ Tuesday
→ 18:30
→ Patient
→ Confirm booking
```

Booking pages should generally be:

```text
noindex
```

because they are transactional rather than useful SEO landing pages.

---

# 32. Public Patient Dashboard

The public website may include:

```text
/patient
```

However, authenticated dashboard pages should generally be:

```text
noindex
```

Possible pages:

```text
/patient
/patient/appointments
/patient/appointments/[id]
/patient/doctors
/patient/favorites
/patient/offers
/patient/reviews
/patient/family
/patient/notifications
/patient/profile
/patient/settings
```

These are application pages rather than SEO pages.

---

# 33. SEO URL Strategy

URLs should be:

- Short
- Readable
- Stable
- Lowercase
- Hyphen-separated
- Keyword descriptive
- Free from unnecessary IDs

Good:

```text
/doctors/ahmed-mohamed-dermatologist
/specialties/dermatology
/services/dental-cleaning
/cities/cairo/new-cairo
/clinics/abc-medical-center
```

Avoid:

```text
/doctor?id=123
/page?id=456
/category/17
```

IDs can still exist internally in the database.

---

# 34. SEO Entity Structure

Main SEO entities:

```text
Doctor
Clinic
Organization
Location
City
Area
Specialty
Category
Subcategory
Service
Offer
Medical Article
Author
Medical Reviewer
```

Each entity should have a canonical public URL.

---

# 35. SEO Metadata

Every indexable page should support:

```text
title
meta description
canonical
robots
Open Graph
Twitter/X metadata
language
alternate language URLs
structured data
```

Example doctor title:

```text
Dr. Ahmed Mohamed – Dermatologist in Cairo | Hakeem
```

Example specialty title:

```text
Dermatologists in Cairo | Book an Appointment | Hakeem
```

Example clinic title:

```text
ABC Medical Center in New Cairo | Hakeem
```

Example service title:

```text
Dental Cleaning in Cairo | Prices & Doctors | Hakeem
```

---

# 36. Canonical URLs

Every indexable page should have exactly one canonical URL.

Example:

```text
https://hakeem.com/ar/doctors/ahmed-mohamed
```

Do not allow multiple parameter combinations to create duplicate indexable pages.

Example:

```text
/search?q=doctor
/search?q=doctor&sort=rating
/search?q=doctor&page=2
```

These should generally not be treated as separate SEO landing pages.

---

# 37. Robots

Recommended:

## Index

```text
/
 /doctors/*
 /clinics/*
 /specialties/*
 /services/*
 /offers/*
 /cities/*
 /medical-library/*
```

## Noindex

```text
/search
/login
/register
/verify
/book/*
/patient/*
/account/*
/settings/*
```

Exact strategy should be implemented carefully based on generated content and search-engine behavior.

---

# 38. Sitemap Structure

Use multiple sitemaps when the site becomes large.

Main:

```text
/sitemap.xml
```

Possible sitemap index:

```text
/sitemap-index.xml
```

Sub-sitemaps:

```text
/sitemaps/doctors.xml
/sitemaps/clinics.xml
/sitemaps/specialties.xml
/sitemaps/services.xml
/sitemaps/offers.xml
/sitemaps/cities.xml
/sitemaps/medical-library.xml
```

Only indexable canonical URLs should be included.

---

# 39. Hreflang

Arabic and English pages should reference each other.

Example:

```text
ar
en
x-default
```

Example:

```text
/ar/doctors/ahmed-mohamed
/en/doctors/ahmed-mohamed
```

Use absolute canonical URLs.

---

# 40. Structured Data / Schema

Recommended Schema.org types:

## Homepage

```text
Organization
WebSite
WebPage
SearchAction
```

## Doctor

Depending on available data:

```text
Person
Physician
MedicalBusiness
Review
AggregateRating
BreadcrumbList
```

## Clinic

```text
MedicalClinic
MedicalBusiness
LocalBusiness
Review
AggregateRating
BreadcrumbList
```

## Service

```text
MedicalProcedure
Service
WebPage
BreadcrumbList
```

Use only schema that accurately represents visible page content.

## Medical Articles

```text
Article
MedicalWebPage
BreadcrumbList
Person
```

## Offers

```text
Offer
```

Only when the offer information is actually displayed and valid.

## Location Pages

```text
WebPage
BreadcrumbList
ItemList
```

---

# 41. Breadcrumbs

Use breadcrumbs on indexable pages.

Example:

```text
Home
→ Doctors
→ Dermatology
→ Cairo
→ Dr. Ahmed Mohamed
```

Specialty:

```text
Home
→ Specialties
→ Dermatology
```

Service:

```text
Home
→ Services
→ Dental
→ Dental Cleaning
```

Location:

```text
Home
→ Cities
→ Cairo
→ New Cairo
```

Breadcrumbs should be visible to users and represented with BreadcrumbList schema where appropriate.

---

# 42. Internal Linking

Every important SEO page should link to related pages.

Doctor:

```text
Doctor
→ Specialty
→ Services
→ Clinics
→ Locations
→ Related doctors
→ Articles
```

Clinic:

```text
Clinic
→ Doctors
→ Services
→ Specialty
→ Location
→ Offers
```

Specialty:

```text
Specialty
→ Doctors
→ Clinics
→ Services
→ Cities
→ Articles
```

City:

```text
City
→ Areas
→ Doctors
→ Clinics
→ Specialties
→ Services
```

Article:

```text
Article
→ Specialty
→ Doctors
→ Services
→ Related articles
```

---

# 43. SEO Landing Page Matrix

The website should be capable of creating indexable combinations such as:

```text
Specialty
+
City
```

Example:

```text
/specialties/dermatology/cairo
```

Potentially:

```text
/specialties/dermatology/cairo/new-cairo
```

Service:

```text
/services/dental-cleaning/cairo
```

Clinic/service:

```text
/clinics/abc-medical-center/services/dental-cleaning
```

These pages should only be generated when they contain enough unique, useful content and real providers/services.

Avoid generating thousands of thin pages automatically.

---

# 44. SEO Content Rules

Do not create pages only to target keywords.

Every indexable landing page should provide useful information such as:

- Real providers
- Real clinics
- Real services
- Real locations
- Prices where available
- Availability where available
- Useful FAQs
- Unique introduction
- Related content
- Clear booking/search actions

Avoid:

```text
Keyword stuffing
Duplicate paragraphs
AI-generated location spam
Empty city pages
Empty specialty pages
Doorway pages
```

---

# 45. SEO Pagination

For large directories:

```text
/doctors?page=2
```

Pagination pages should be useful to users.

Do not create thousands of crawlable filter combinations.

Preferred:

- Canonical base category pages
- Controlled filters
- Indexable landing pages only for valuable combinations

---

# 46. Search Engine Friendly Navigation

Important pages should be reachable through normal links.

Do not rely only on:

```text
JavaScript search
```

Use crawlable links for:

- Doctors
- Clinics
- Specialties
- Services
- Cities
- Areas
- Articles
- Offers

---

# 47. Open Graph

Every important page should have:

```text
og:title
og:description
og:url
og:image
og:type
og:locale
```

Doctor pages should preferably have doctor profile imagery.

Clinic pages should have clinic imagery.

Articles should have article featured images.

---

# 48. Social Sharing

Public pages should support sharing to:

- WhatsApp
- Facebook
- X
- LinkedIn
- Copy link

Especially:

- Doctor profiles
- Clinics
- Offers
- Medical articles
- Services

---

# 49. Performance SEO

The public website should prioritize:

- Server-side rendering
- Static generation where possible
- Incremental regeneration where appropriate
- Optimized images
- WebP/AVIF
- Lazy loading
- Minimal JavaScript
- Fast mobile rendering
- Good Core Web Vitals
- Semantic HTML
- Accessible forms
- Proper heading hierarchy

Avoid loading heavy UI libraries unnecessarily on SEO pages.

---

# 50. Accessibility

Target:

```text
WCAG 2.2 AA
```

Important:

- Keyboard navigation
- Screen reader support
- Proper labels
- Focus states
- Color contrast
- Reduced motion
- RTL support
- Accessible modals
- Accessible menus
- Accessible form errors

---

# 51. Responsive Website

Breakpoints should support:

```text
Mobile
Tablet
Desktop
Large desktop
```

Mobile is the primary experience.

Important mobile components:

- Sticky search
- Bottom sheets
- Filter drawer
- Sticky booking CTA
- Compact cards
- Swipeable sections
- Mobile navigation

---

# 52. Public Website Design System

## Visual Direction

Premium digital healthcare.

Avoid:

- Generic Bootstrap look
- Excessive gradients
- Overly clinical hospital design
- Heavy shadows
- Crowded layouts

Use:

- Clean cards
- Soft surfaces
- Modern typography
- Large whitespace
- Subtle gradients
- Rounded components
- Clear CTAs
- High-quality medical imagery
- Modern iconography

---

# 53. Theme Support

Support:

```text
Light
Dark
System
```

Arabic:

```text
RTL
```

English:

```text
LTR
```

---

# 54. Typography

Arabic:

```text
Tajawal
Noto Sans Arabic
```

Editorial medical content can optionally use:

```text
Amiri
```

English:

```text
Inter
```

---

# 55. Main Public Components

Recommended reusable components:

```text
Header
Footer
MobileHeader
MobileNavigation
MegaMenu
SearchBox
SearchOverlay
SearchResults
DoctorCard
ClinicCard
ServiceCard
OfferCard
SpecialtyCard
CityCard
AreaCard
ReviewCard
ArticleCard
Rating
Badge
Availability
TimeSlot
Calendar
FilterDrawer
FilterSidebar
SortMenu
Breadcrumbs
Pagination
Map
LocationCard
BookingCTA
FAQ
FAQItem
Hero
SectionHeader
EmptyState
LoadingState
ErrorState
CookieBanner
LanguageSwitcher
ThemeSwitcher
```

---

# 56. Public Page Priority

## Phase 1 — Essential

```text
/
 /search
 /doctors
 /doctors/[slug]
 /clinics
 /clinics/[slug]
 /specialties
 /specialties/[slug]
 /services
 /services/[slug]
 /offers
 /offers/[slug]
 /cities
 /cities/[city]
 /cities/[city]/[area]
 /medical-library
 /medical-library/[slug]
 /how-it-works
 /about
 /contact
 /help
 /terms
 /privacy
 /cookies
```

## Phase 2

```text
/home-care
/teleconsultation
/specialties/[specialty]/[city]
/services/[service]/[city]
```

## Phase 3

Potential advanced SEO pages:

```text
/specialties/[specialty]/[city]/[area]
/services/[service]/[city]/[area]
/clinics/[clinic]/services/[service]
```

Only create these when there is enough unique content and real inventory.

---

# 57. Recommended Public Sitemap

```text
HAKEEM
│
├── Home
│   └── /
│
├── Search
│   └── /search
│
├── Doctors
│   ├── /doctors
│   └── /doctors/[slug]
│
├── Clinics
│   ├── /clinics
│   └── /clinics/[slug]
│
├── Specialties
│   ├── /specialties
│   └── /specialties/[slug]
│
├── Services
│   ├── /services
│   └── /services/[slug]
│
├── Offers
│   ├── /offers
│   └── /offers/[slug]
│
├── Locations
│   ├── /cities
│   ├── /cities/[city]
│   └── /cities/[city]/[area]
│
├── Home Care
│   └── /home-care
│
├── Teleconsultation
│   └── /teleconsultation
│
├── Medical Library
│   ├── /medical-library
│   ├── /medical-library/category/[slug]
│   ├── /medical-library/author/[slug]
│   └── /medical-library/[slug]
│
├── Information
│   ├── /how-it-works
│   ├── /about
│   ├── /contact
│   └── /help
│
└── Legal
    ├── /terms
    ├── /privacy
    ├── /cookies
    ├── /cancellation-policy
    ├── /medical-disclaimer
    └── /accessibility
```

---

# 58. SEO Implementation Checklist

For every indexable page:

```text
[ ] Unique URL
[ ] Unique title
[ ] Unique meta description
[ ] Canonical
[ ] H1
[ ] Proper H2/H3 hierarchy
[ ] Breadcrumbs
[ ] Internal links
[ ] Relevant structured data
[ ] Open Graph
[ ] Optimized image
[ ] Alt text
[ ] Arabic/English hreflang
[ ] Mobile responsive
[ ] Fast loading
[ ] No duplicate content
[ ] Useful unique content
```

---

# 59. Final Website Principle

Hakeem should not be designed as only a booking website.

The public website should combine:

```text
Healthcare Marketplace
+
Search Engine
+
Doctor Directory
+
Clinic Directory
+
Service Directory
+
Location Directory
+
Offers Marketplace
+
Medical Knowledge Platform
+
Booking Platform
```

The SEO architecture should therefore be built around the relationships:

```text
Doctor
    ↕
Specialty
    ↕
Service
    ↕
Clinic
    ↕
Location
    ↕
City / Area
    ↕
Medical Content
```

This relationship-based architecture should allow Hakeem to scale its public website without creating duplicate or thin SEO pages.
