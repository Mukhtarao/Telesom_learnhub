# Telesom LearnHub — go-live checklist

**Start here:** sign in as administrator → profile menu (top right) → **System health**. It checks everything below automatically and tells you what to fix. This file explains each item.

## 0. First start
Open the site once: a new install goes to **setup.php** where you create the first administrator. There are no demo accounts or demo passwords anywhere in this package.

## 1. Must do before real users (security & money)
| What | Where | Why |
|---|---|---|
| Your **real merchant numbers** (ZAAD / eDahab / bank) | `includes/config.php` | Learners are told to pay these numbers; payments are always confirmed by an administrator (or the webhook) |
| Your **real plan prices** | Admin → Finance → Plans & Pricing | The prices shipped are starter values |
| Fill the **SMTP_*** settings | `includes/config.php` | Password-reset links are **only** sent by e-mail, so resets do not work until this is set (System health has a *test e-mail* button) |
| **HTTPS** + `FORCE_HTTPS = true` + `APP_URL` | hosting + `includes/config.php` | Encrypts passwords; required for the installable app and secure cookies |
| A **limited database user** | `database/create_db_user.sql`, then `DB_USER`/`DB_PASS` | Don't run the site as MySQL `root` |
| `display_errors = Off` | `php.ini` | Visitors must not see error messages |

## 2. Automatic reminders (e-mail / SMS / in-app)
Reminders are sent for: live classes (24 h and 1 h before), assignments due within 24 h, and organization subscriptions ending within 7 days. Users can switch e-mail/SMS reminders on or off in **Profile → Reminders**.
- They run by themselves every ~10 minutes while people use the site. For exact timing also schedule `cron.php` (the exact address is on System health once you set `CRON_KEY` to a long random secret in `includes/config.php`; empty = cron.php is disabled): Linux cron `*/10 * * * * php /path/cron.php YOUR_CRON_KEY`, or Windows Task Scheduler `php.exe C:\xampp\htdocs\telesom-learnhub\cron.php YOUR_CRON_KEY`.
- **SMS** needs a gateway. Fill `SMS_API_URL`, `SMS_API_KEY` and, if your gateway uses different field names, `SMS_FIELD_TO` / `SMS_FIELD_TEXT` / `SMS_AUTH_HEADER`. Telesom's own IT team can tell you the right values.

## 3. Automatic payment confirmation (optional)
Payments are confirmed by an administrator by default. To confirm automatically, set `WEBHOOK_SECRET` and let your payment gateway (or an SMS-forwarding tool) call `payment_webhook.php`:
```
POST /payment_webhook.php      Header:  X-Signature = hex(HMAC-SHA256(body, WEBHOOK_SECRET))
{"reference":"TXN12345","amount":25.00,"status":"paid","phone":"0634000000","timestamp":1700000000}
```
It confirms the pending course payment or subscription whose *transaction ID* matches `reference` (amount must be at least the price; timestamp must be within 10 minutes). Python example: `sig = hmac.new(SECRET.encode(), body.encode(), hashlib.sha256).hexdigest()`.
**Note:** connecting directly to ZAAD / eDahab needs their merchant API documentation and credentials — this endpoint is the receiving side that their integration (or Telesom's IT team) calls.

## 4. Backups
System health → **Download database (.sql)** and **Uploads (.zip)** — do it weekly at least, and keep copies off the server.

## 5. Languages & translations
English, Somali and Arabic (right-to-left). Menus, sign-in/registration, landing page, dashboards, courses, live classes, profile and most buttons are translated; admin tools, long help texts and the Terms/Privacy pages are English. **Have a native speaker review `lang/so.php` and `lang/ar.php`** — add or fix a line there and it appears immediately.

## 6. Legal
`terms.php` and `privacy.php` are **templates**. Have them reviewed by a legal adviser and adjust refunds, governing law and contact details.

## 7. Known trade-offs
- The login lock (5 wrong passwords per e-mail in 15 minutes) also lets someone lock out a known e-mail on purpose; the user can still reset the password.
- The shipped plan prices are starter values: set yours under Admin → Finance → Plans & Pricing.
