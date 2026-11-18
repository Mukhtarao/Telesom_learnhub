# Telesom LearnHub — Learning Management System

Stack: PHP 8+ (PDO), MySQL/MariaDB, HTML, CSS, JavaScript. Theme: green & white.

## Install (XAMPP / WAMP / Laragon or any PHP + MySQL host)
1. Copy the `telesom-learnhub` folder into `htdocs` (or your web root).
2. Start Apache and MySQL.
3. Open phpMyAdmin → create a database named `telesom_learnhub` → Import → choose `database/database.sql` (tables only; no accounts and no sample courses).
4. If your MySQL user/password is not `root` / empty, edit `includes/config.php`.
5. Visit http://localhost/telesom-learnhub/ — a brand-new install sends you to **setup.php**, where you create the first administrator (your own email and password). That page switches itself off once an administrator exists.

## Production hosting environment variables (Railway)
The app automatically reads Railway MySQL variables: `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and `MYSQLPASSWORD` (or `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`). Set `APP_URL` to the public `https://` address. HTTPS enforcement turns on automatically when `APP_URL` starts with `https://`; you can override it with `FORCE_HTTPS=true` or `false`.

Optional variables include `CRON_KEY`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`, `MAIL_FROM`, `SMS_API_URL`, `SMS_API_KEY`, `MERCHANT_ZAAD`, `MERCHANT_EDAHAB`, `MERCHANT_BANK`, and `WEBHOOK_SECRET`. Add secrets in Railway Variables, not source control. SMS and payment webhook are optional; manual payment approval remains available without the webhook.

## Accounts
There are **no built-in or demo accounts**. The first administrator is created at `setup.php`; after that, administrators add users from **People → Users**, and learners, instructors and organizations can register themselves.

## Features
Landing page (Telesom info + platform info) · registration/login/logout/change password · profiles ·
course catalog with search, filter, sort · free / paid (simulated) / instructor-approved / private enrollment ·
lessons (text, video/YouTube, audio, PDF, documents, images, links, uploads) · automatic progress tracking ·
assignments with deadlines, uploads, grading & feedback · quizzes & exams (MCQ, true/false, short answer, timer, attempts, auto-grading) ·
auto-issued certificates with verification codes (verify.php) · discussions, private messages, notifications ·
ratings, reviews and reporting · role dashboards · reports with CSV export ·
admin: users, roles, suspend, course approval, categories, announcements, moderation ·
organizations: add members, assign private courses, training reports.

## Security notes
Password hashing (bcrypt), prepared statements (PDO), CSRF tokens on every POST form, output escaping,
role-based access checks, upload extension whitelist and PHP execution disabled in `uploads/`.
Before going live: serve over HTTPS and work through **GO_LIVE_CHECKLIST.md** (Admin → profile menu → System health checks most of it automatically).

## Payments
Paid courses use `checkout.php` (ZAAD, eDahab, bank transfer), `payments.php` (history, admin confirmation, sales) and `receipt.php` (printable receipt).
Learner pays to your merchant number and enters the transaction ID; an administrator confirms it in **Payments**, which unlocks the course. Put your real numbers in `MERCHANT_ZAAD`, `MERCHANT_EDAHAB`, `MERCHANT_BANK`.
Put your real numbers in `MERCHANT_ZAAD`, `MERCHANT_EDAHAB`, `MERCHANT_BANK` in `includes/config.php`. Fully automatic ZAAD/eDahab charging needs a merchant API agreement and credentials from the provider; the code is structured so it can be added in `checkout.php`.
Upgrading an older install? Import `database/update_payments.sql`.

## Updates in this version
- **Watch-to-complete:** video and audio lessons can only be completed after the learner has watched the whole lesson (skipping ahead is blocked; verified on the server in `watch.php`). Works with YouTube, Vimeo, MP4 links and uploaded files.
- **Materials tab:** grouped cards for videos, documents (Open / Download with file size), links and assignment files.
- **Instructors:** edit and re-order lessons (course > Manage > Lessons).
- **Forgot password:** `forgot.php` / `reset.php`. The reset link is always e-mailed (configure the `SMTP_*` settings in `includes/config.php`); it is never shown on screen.
- Existing install? Import `database/update_v3.sql` (password reset table).

## Certificate, sidebar and night mode
- Certificates now carry a scannable **QR code** (opens `verify.php?code=…`), a gold seal, and handwriting-style name/signature (Great Vibes font, loaded from Google Fonts; falls back to a system script font offline). The QR is generated offline by `assets/js/qr.js`. For phones to open the link, the site must be reachable from the phone (use your PC's LAN IP instead of `localhost`, or a real domain).
- **Profile menu (top bar, right):** My profile, Payments (learners), System health (administrators), language, night / day mode, install app and Log out. Messages and notifications are the two icons beside it.
- **Sidebar:** fixed, with a short list of the main pages for each role. Administrator pages are grouped (People · Courses & Content · Finance) with tabs inside each group.

## Organization certificates & Live classes
- **Organization certificates:** courses owned by an organization issue a navy-and-gold "Certificate of Training" with "Issued in partnership with <organization>", the organization's signatory as the signature (set it under Profile → Certificate signatory), and "Issued by <organization> × Telesom LearnHub" on the verification page. Public courses keep the green "Certificate of Completion".
- **Live classes:** instructors/organizations schedule classes in *Course → Manage → Live classes* (Jitsi Meet rooms are created automatically; Zoom/Google Meet/Teams links are supported). Learners see them under *Live Classes*, get notifications, can enter the room 15 minutes before the start, add the class to their calendar (.ics), and watch the recording afterwards. Attendance is recorded when a learner presses Join. Time zone: `APP_TZ` in `includes/config.php` (default Africa/Mogadishu).
- The database upgrades itself on first visit (new tables/columns). If your MySQL user cannot ALTER/CREATE, import `database/update_v4.sql`.

## Organizations: approval & subscriptions
1. An organization registers (**Register → Organization**) and waits for approval (**Admin → People → Organizations → Approve / Reject**).
2. Once approved it opens **Subscription**, picks a **monthly or yearly** plan and pays by ZAAD / eDahab / bank (an administrator confirms each payment).
3. The administrator confirms the payment (**Admin → Finance → Subscriptions → Confirm**) — the plan then starts. Renewals start the day after the current period ends.
4. Plans enforce **learner and private-course limits**; when a subscription ends, the organization is locked to the Subscription page and its members cannot open the organization's private courses until it is renewed. A warning banner appears 14 days before expiry.
5. Admin can **grant a free / sponsored / custom-price plan** (e.g. internal Telesom departments) and edit plans and prices under **Admin → Finance → Plans & Pricing** (starter prices are included — replace them).
6. Existing organizations are given a free 12-month "Founding member" plan automatically on upgrade so nobody is locked out.
- **Course sales are visible to administrators only** (learners see their own payments). Instructors and organizations have no Sales page, receipts or revenue figures, and are no longer told that a learner *paid*.

## Design polish (v4)
Toast notifications, spinner + double-submit protection on forms, dashboard charts (admin: enrollments & users by role; instructors/organizations: learners per course), price chips on course cards, animated landing hero, skip link / focus outlines / favicon, back-to-top button. See **GO_LIVE_CHECKLIST.md** before opening the system to real users.

## Security, languages, reminders, installable app (v6)
- **Login protection:** 5 wrong passwords per e-mail (or 20 per device) in 15 minutes → temporary lock; password-reset and sign-up requests are rate-limited; secure session cookies and security headers.
- **Terms of use / Privacy policy** pages (templates) and a required checkbox at registration.
- **System Health** (admin, profile menu): go-live checks, database/uploads backups, test e-mail/SMS, run reminders, cron address.
- **Reminders** (live classes, deadlines, subscription expiry) by in-app message, e-mail and SMS; user preferences in Profile.
- **Automatic payment confirmation** endpoint (`payment_webhook.php`) — off until `WEBHOOK_SECRET` is set.
- **Languages:** English / Somali / Arabic (RTL) switcher (EN · SO · AR).
- **Installable app (PWA):** "Install" button appears in supported browsers on HTTPS (or localhost); friendly offline page.
- Read **GO_LIVE_CHECKLIST.md**.
