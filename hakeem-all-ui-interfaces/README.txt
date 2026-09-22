HAKEEM Healthcare Platform – UI Design Reference

Arabic-first RTL healthcare marketplace and management platform.
The package contains 30 individual interface references plus the complete design board.
Visual direction: modern medical UI, calm blue palette, rounded cards, RTL Arabic layouts,
strong dashboards, responsive web screens, mobile screens, and Hermes AI.

Implementation notes (2026-09-15)
---------------------------------
Public website Phase 1 routes in hakeem-website-seo-pages.md are the source of truth
for URLs, nav, homepage sections, search, directories, static pages, and robots.

Live now in the Laravel app (session locale, not /ar and /en prefixes):
  / /search /doctors /clinics /specialties /services /offers /labs /cities
  /home-care /teleconsultation /how-it-works /about /contact /help
  /terms /privacy /cookies /cancellation-policy /medical-disclaimer
  /book/doctors/{slug}  (auth, noindex, pending request + payment mode)
  /appointments          (auth, noindex, patient list)
  /clinic/queue          (clinic staff; confirm / walk-in)
  /clinic/payments       (clinic owner; subset of platform payment modes)

Deferred on purpose (do not fake):
  /ar /en URL prefixes, medical library, ratings, area pages,
  /patient dashboard, Hermes/mobile screens in this pack.

See md_files/00-README.md, 06-seo-homepage-discovery-ratings.md, and 09-roadmap-phases.md.
