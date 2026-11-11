# Telesom LearnHub — Learning Management System

Stack: PHP 8+ (PDO), MySQL/MariaDB, HTML, CSS, JavaScript. Theme: green & white.

## Install (XAMPP / WAMP / Laragon or any PHP + MySQL host)
1. Copy the `telesom-learnhub` folder into `htdocs` (or your web root).
2. Start Apache and MySQL.
3. Open phpMyAdmin → Import → choose `database/database.sql` (creates the `telesom_learnhub` database with demo data).
4. If your MySQL user/password is not `root` / empty, edit `includes/config.php`.
5. Visit http://localhost/telesom-learnhub/

## Demo accounts (password for all: `password`)
| Role | Email |
|---|---|
| Administrator | admin@telesom.com |
| Instructor | instructor@telesom.com |
| Learner | learner@telesom.com |
| Organization | org@telesom.com (member: mustafe@telesom.com) |

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
Before going live: change demo passwords, set `display_errors=Off`, and serve over HTTPS.

## Payments
Paid courses use `checkout.php` (ZAAD, eDahab, bank transfer), `payments.php` (history, admin confirmation, sales) and `receipt.php` (printable receipt).
Set `PAYMENT_MODE` in `includes/config.php`:
- `demo`   — payment is confirmed instantly (no real money; good for testing/presentations).
- `manual` — learner pays to your merchant number and enters the transaction ID; an administrator confirms it in **Payments**, which unlocks the course. Put your real numbers in `MERCHANT_ZAAD`, `MERCHANT_EDAHAB`, `MERCHANT_BANK`.
Fully automatic ZAAD/eDahab charging needs a merchant API agreement and credentials from the provider; the code is structured so it can be added in `checkout.php`.
Upgrading an older install? Import `database/update_payments.sql`.

## Updates in this version
- **Watch-to-complete:** video and audio lessons can only be completed after the learner has watched the whole lesson (skipping ahead is blocked; verified on the server in `watch.php`). Works with YouTube, Vimeo, MP4 links and uploaded files.
- **Materials tab:** grouped cards for videos, documents (Open / Download with file size), links and assignment files.
- **Instructors:** edit and re-order lessons (course > Manage > Lessons).
- **Forgot password:** `forgot.php` / `reset.php`. `RESET_MODE` in `includes/config.php` = `demo` (link shown on screen) or `mail` (emailed; needs a mail server).
- Existing install? Import `database/update_v3.sql` (password reset table).

## Certificate, sidebar and night mode
- Certificates now carry a scannable **QR code** (opens `verify.php?code=…`), a gold seal, and handwriting-style name/signature (Great Vibes font, loaded from Google Fonts; falls back to a system script font offline). The QR is generated offline by `assets/js/qr.js`. For phones to open the link, the site must be reachable from the phone (use your PC's LAN IP instead of `localhost`, or a real domain).
- **Collapsible sidebar:** use the panel button in the top bar (desktop). Remembered per browser.
- **Night / day mode:** moon/sun button (top bar, public menu and login pages). Remembered per browser.

## Organization certificates & Live classes
- **Organization certificates:** courses owned by an organization issue a navy-and-gold "Certificate of Training" with "Issued in partnership with <organization>", the organization's signatory as the signature (set it under Profile → Certificate signatory), and "Issued by <organization> × Telesom LearnHub" on the verification page. Public courses keep the green "Certificate of Completion".
- **Live classes:** instructors/organizations schedule classes in *Course → Manage → Live classes* (Jitsi Meet rooms are created automatically; Zoom/Google Meet/Teams links are supported). Learners see them under *Live Classes*, get notifications, can enter the room 15 minutes before the start, add the class to their calendar (.ics), and watch the recording afterwards. Attendance is recorded when a learner presses Join. Time zone: `APP_TZ` in `includes/config.php` (default Africa/Mogadishu).
- The database upgrades itself on first visit (new tables/columns). If your MySQL user cannot ALTER/CREATE, import `database/update_v4.sql`.

## Organizations: approval & subscriptions
1. An organization registers (**Register → Organization**) and waits for approval (**Admin → Organizations → Approve / Reject**).
2. Once approved it opens **Subscription**, picks a **monthly or yearly** plan and pays by ZAAD / eDahab / bank (set `PAYMENT_MODE` to `manual` in `includes/config.php`; demo mode activates instantly).
3. The administrator confirms the payment (**Admin → Subscriptions → Confirm**) — the plan then starts. Renewals start the day after the current period ends.
4. Plans enforce **learner and private-course limits**; when a subscription ends, the organization is locked to the Subscription page and its members cannot open the organization's private courses until it is renewed. A warning banner appears 14 days before expiry.
5. Admin can **grant a free / sponsored / custom-price plan** (e.g. internal Telesom departments) and edit plans and prices under **Admin → Plans & Pricing** (sample prices are included).
6. Existing organizations are given a free 12-month "Founding member" plan automatically on upgrade so nobody is locked out.
- **Course sales are visible to administrators only** (learners see their own payments). Instructors and organizations have no Sales page, receipts or revenue figures, and are no longer told that a learner *paid*.
