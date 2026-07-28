# ExcelBids — Bid Management System

An end-to-end system for a tender, bid and grant writing consultancy: a
CMS-driven public website, an admin panel that runs the business, and a client
portal.

Built as plain PHP 8 with PDO/MySQL. **No Composer, no build step, no
dependencies** — upload it to cPanel and run the installer.

---

## What it does

### Public website
The approved design, rendered from the database. Every heading, paragraph,
button label, service, sector, FAQ, testimonial, statistic and process step is
editable in the admin panel — nothing on the front end requires a code change.
Includes the consultation request form, sitemap, robots.txt and FAQ structured
data.

### Admin panel
- **Dashboard** — deadlines, overdue bids, your workload, pipeline by status, a
  six-month trend, and what needs attention.
- **Bids** — the full lifecycle: seven pipeline stages, seven statuses, the
  four-stage QA sign-off from the website, tasks, documents, and a timeline you
  can selectively share with the client. List, board and calendar views.
- **Clients** — CRM records, portal logins, per-client bid history and win rate.
- **Consultation requests** — the website inbox, with one-click conversion into
  a client record.
- **Reports** — win rate, pipeline value, performance by client, sector, portal
  and team member, QA pass rates, and CSV export throughout.
- **Website content** — the CMS described above, plus page and menu management.
- **Settings** — email (with a test-send and a delivery log), portal options,
  SEO, and a system health check.
- **Staff accounts** — four roles from administrator to read-only viewer.

### Client portal
Clients sign in to see their bids, deadlines, QA progress and status timeline,
download documents, upload evidence, and message the bid team.

---

## Quick start

### On cPanel

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the full walkthrough. In short:
upload, create a MySQL database, visit `/install/`, then delete the installer.

### Locally

```bash
# 1. Create a database
mysql -e "CREATE DATABASE excelbids CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Serve the public folder
php -S 127.0.0.1:8000 -t public_html public_html/index.php

# 3. Open http://127.0.0.1:8000/install/ and follow the steps
```

---

## Layout

```
excelbids/
├── app/                        Application code — never web-accessible
│   ├── Core/                   Router, DB, auth, CSRF, mailer, uploads, views
│   ├── Models/                 Bid, Client, Document, Enquiry, Message, Report, User
│   ├── Controllers/
│   │   ├── Site/               Public website
│   │   ├── Admin/              Admin panel
│   │   └── Portal/             Client portal
│   ├── Views/                  Templates, including the email layouts
│   ├── config.sample.php       Copy to config.php, or let the installer write it
│   └── routes.php              Every route in the system
├── database/
│   ├── schema.sql              Tables
│   └── seed.sql                The website's starting content
├── public_html/                Document root
│   ├── index.php               Single front controller
│   ├── .htaccess               Pretty URLs, security headers, caching
│   ├── assets/                 CSS and JS
│   └── install/                Web installer — DELETE after setup
├── storage/
│   ├── uploads/                Client documents, outside the web root
│   └── logs/                   PHP error log
├── DEPLOYMENT.md
└── README.md
```

---

## How it is put together

**One front controller.** Apache rewrites every request to
`public_html/index.php`, which dispatches through `app/routes.php`. Routes are
grouped behind middleware (`csrf`, `auth.staff`, `auth.client`, `can:*`).

**Two authentication guards.** Staff (`users`) and clients (`client_users`) are
entirely separate. A client session simply has no staff key, so it can never
satisfy an admin route even if one were misconfigured.

**Role-based access.** `Auth::can('bids.manage')` checks a capability matrix.
Administrator, Manager, Bid Writer and Viewer.

**The CMS.** Singleton copy lives in `content_blocks`, repeating items in their
own tables (`services`, `faqs`, …), and section visibility and order in
`page_sections`. Editors can wrap a word in `[c]…[/c]` for the hand-drawn circle
in the hero, or `[m]…[/m]` for the highlighter mark — values are escaped before
those markers are expanded, so no other HTML can get through.

**Adding a new CMS list** takes one array entry in
`app/Controllers/Admin/CmsController.php::COLLECTIONS` — the list editor,
validation and reordering are generated from it.

---

## Security

- Every query uses prepared statements with bound parameters.
- All output is escaped with `e()`; page HTML from the editor is passed through
  an allow-list sanitiser.
- CSRF tokens on every state-changing request; tokens rotate on login.
- Passwords hashed with `password_hash()`, rehashed transparently when PHP's
  default cost changes.
- Login throttling — five failed attempts locks the address and IP for 15 minutes.
  Failures are deliberately vague and timing-equalised so accounts cannot be enumerated.
- Sessions: `httponly`, `samesite=Lax`, `secure` over HTTPS, regenerated on login,
  with an idle timeout independent of the cookie lifetime.
- Uploads are stored outside the web root under generated names, validated by
  extension **and** by sniffed MIME type, and served only through a controller
  that re-checks authorisation.
- Portal document access requires all three of: the document belongs to that
  client, it is flagged visible, and the session is authenticated.
- `app/`, `database/` and `storage/` each carry a deny-all `.htaccess` as a
  second line of defence.

---

## Things to change before launch

The seeded content includes deliberate placeholders, each flagged in the admin panel:

1. **Statistics bar** — `92%`, `7`, `£—M`, `4` are illustrative.
2. **Case study** — the supported-living example is illustrative.
3. **Testimonials** — only publish quotes you have permission to use.
4. **Privacy policy and terms** — a starting point, not legal advice.

---

## Conventions

- PHP 8.0+ syntax, `declare(strict_types=1)` everywhere.
- Views are plain PHP with one level of layout inheritance.
- Controllers validate, models hold the domain logic, `Core/` holds the plumbing.
- British English throughout the interface, dates as `j M Y`, currency
  configurable (defaults to `£`).
