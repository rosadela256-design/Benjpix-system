# benjpix photo and video services — Booking & Request Management System

A working PHP + MySQL web application. Covers four services:
- **Photo shoot** and **Event videography** — booked for a date, time, and location
- **Printing** and **Photo/video editing** — drop-off requests (file upload, no fixed time)

Built with PHP, MySQL, JavaScript, and an XML configuration file.

## Folder structure
```
binjpix-system/
├── index.php, request.php, track.php      customer-facing pages
├── login.php, logout.php                  staff sign-in
├── install.php                            one-time database setup
├── config/
│   ├── config.xml                         services, upload rules, status list
│   └── database.local.php                 created by install.php (your DB credentials)
├── includes/                              shared PHP: db connection, auth, helpers, layout
├── staff/                                 staff-only area (requires login)
│   ├── dashboard.php, requests.php, payments.php, customers.php, file.php
├── assets/css, assets/js                  styling and small client-side scripts
├── uploads/                               uploaded files (blocked from direct web access)
└── sql/schema.sql                         database tables, for manual import if preferred
```

## Setup with XAMPP
1. Install XAMPP and start Apache + MySQL.
2. Copy this whole `binjpix-system` folder into `htdocs/`, e.g. `C:\xampp\htdocs\binjpix-system`.
3. In your browser, open `http://localhost/binjpix-system/install.php`.
4. Fill in your MySQL details (defaults `localhost` / `root` / blank password work on a
   fresh XAMPP install) and set a staff email and password — staff sign in with email, not a username.
5. Click **Run setup** — this creates the `binjpix` database, all tables, and your staff account.
6. Visit `http://localhost/binjpix-system/` for the customer site, or `login.php` to sign in as staff.

If you'd rather create the database yourself, import `sql/schema.sql` in phpMyAdmin and
create `config/database.local.php` by hand (see the `define(...)` lines install.php writes).

## What's implemented
- **Customer:** book a photo shoot or videography session (date, time, location), or submit a
  printing/editing request (file upload, quantity, pickup/delivery/digital) — either way, get a
  reference number and track status later by it.
- **Staff:** sign in, see a dashboard of request counts, filter/update request status
  (Pending → Processing → Ready → Completed), open the submitted file, record payments against
  a reference number, search customers and see their order history.
- **Security:** PDO prepared statements, hashed passwords, CSRF tokens on every form, session-based
  access control (staff pages redirect to login if you're not signed in), upload type/size
  validation with files renamed and stored outside direct web access, output escaping, and the
  XML config parsed with external entities disabled.

## Not included (see proposal's listed exclusions)
Online payment processing, SMS/email notifications, multi-branch support, analytics dashboards.

## Showcasing your own photos
The homepage has an "A look at our work" gallery. It ships with 20 placeholder
tiles so the layout isn't empty — swap them for real samples of past customers' work:
1. Drop your own JPG/PNG files into `assets/img/gallery/` (any size works; portrait shots
   look great in the masonry layout).
2. Edit `config/config.xml` — under `<gallery>`, list each `<file>` name and a short
   `<caption>`. Add, remove, or go beyond 20 `<item>` entries freely; the grid adjusts
   automatically either way.
No code changes needed — this works the same way the services list does.

## Changing the logo
`assets/img/logo.png` is the circular badge used in both headers, and
`assets/img/favicon-*.png` are the browser-tab icons. Replace these files (keep the same
names) to swap the logo without touching any code.

## Using it from a phone
The site itself is fully responsive — on a narrow screen the navigation collapses into a
menu button, and everything (gallery, forms, staff dashboard) adapts to fit. That covers
*how it looks* on a phone. Whether a phone can actually *reach* it depends on where it's
running:
- **Same WiFi as the shop computer:** find the shop PC's local IP (Command Prompt →
  `ipconfig` → look for "IPv4 Address", e.g. `192.168.1.23`), then visit
  `http://192.168.1.23/binjpix-system/` from the phone's browser. Works for testing or an
  in-shop tablet, but only on that one network.
- **From anywhere, by searching or a public link:** this needs real web hosting (a hosting
  plan with PHP + MySQL and a domain name), since XAMPP on a personal computer isn't
  reachable from the open internet by default. Once you have hosting, upload these same
  files and run `install.php` there the same way — nothing in the code needs to change.

## Demo data
There is none by default — install.php only creates your one staff account. Submit a
request from the customer site to see data flow through the system.
