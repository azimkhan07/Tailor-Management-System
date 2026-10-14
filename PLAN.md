# Tailor Shop Management System — Development Plan

**Date:** 14 Oct 2026
**Stack:** Laravel 12 (PHP 8.2), MySQL, Bootstrap 4 "Modern Admin" theme (prebuilt assets, no build step).
**Repo:** https://github.com/azimkhan07/Tailor-Management-System

---

## 1. What was asked

1. Fix the **measurement part**: the tailor writes the measurement in a description box; if the same
   customer returns, the previous measurement should be able to be carried forward and managed.
2. **Auto-generated number** on the order/bill: first 3 letters of the brand name in CAPS, followed
   by date, month, time and year.
3. Order created → **order slip**; order completed → **invoice as PDF**.
4. **Employee management**: admin assigns which customer's cloth goes to which employee, employee
   marks complete/not, timestamps of every status change, admin assigns, employee-side **calendar**
   showing which days the shop is closed, employees can **apply for leave** from it.
5. **SMS / WhatsApp**: when an order completes, the customer gets a message (with the order number).
   Send on WhatsApp directly if available.
6. Write the plan down, add anything that would make it better, finish quickly, push to git.

---

## 2. Data model

### New tables

| Table | Purpose | Key columns |
|---|---|---|
| `measurements` | Saved measurement "kits" per customer (carry-over source) | `customer_id`, `cloth_type_id?`, `title`, `details` |
| `order_status_histories` | Immutable status trail | `order_id`, `from_status`, `status`, `user_id?`, `notes?`, timestamps |
| `holidays` | Shop closed days (calendar) | `date?` (one-off), `weekday?` (0-6 weekly repeat), `title` |
| `leaves` | Employee leave requests | `user_id`, `start_date`, `end_date`, `reason`, `status`, `decided_by?`, `decided_at?` |

### Changed tables

| Table | Added columns |
|---|---|
| `orders` | `order_no` (unique), `status`, `assigned_to` (→ staff), `completed_at`, `status_updated_at` |
| `users` | `role` (`admin` / `employee`), `staff_id` (→ staff, so an employee login maps to a staff record) |

---

## 3. Features

### 3.1 Measurement carry-over
- Every order's description is saved as a `Measurement` for that customer (auto, on order create,
  titled with the date).
- While creating an order, picking a customer loads that customer's **previous measurements** via
  `GET /customers/{id}/measurements` (JSON) into a dropdown next to the description box.
- Selecting one fills the description box; the tailor can then edit or just submit.
- Keeps the original free-text description behaviour — nothing is lost.

### 3.2 Auto-generated order number
- Format: **`{BRAND3}-{DDMM}-{HHMMSS}-{YYYY}`** e.g. `TAI-1410-153045-2026`.
  - `BRAND3` = first 3 letters of the brand (setting `app_name`) upper-cased, padded if shorter.
  - `DDMM` = day+month, `HHMMSS` = time, `YYYY` = year (as requested).
- Uniqueness: if the same second is used twice, a `-2`, `-3`… suffix is appended.
- Back-filled for orders that existed before this feature.

### 3.3 Order slip & invoice
- `GET /orders/{order}/slip` — printable slip (order details, measurements, amounts). Print → PDF
  from the browser.
- `GET /orders/{order}/invoice` — real PDF download via `barryvdh/laravel-dompdf`, only meaningful
  once the order is completed (but always available).
- Both are available from the order row / order detail page.

### 3.4 Employee management & status tracking
- Statuses: `pending → in_progress → completed → delivered` (+ `cancelled`).
- Admin assigns an order to a staff member (`assigned_to`) and can move status anywhere.
- An employee (`role=employee`, linked to a `staff` record) logs in and sees **My Work**:
  the orders assigned to them, with a status control (`start` / `complete`).
- Every status change writes an `order_status_histories` row with **who** changed it and **when**.
  The admin order page shows this as a timeline with timestamps.
- On transition to `completed`, the customer notification fires.

### 3.5 Calendar & leave
- Employee **Calendar** page: a lightweight month grid (built in-house — see §4) showing:
  - Shop-closed days (one-off holidays + weekly recurring closed weekdays),
  - The employee's own leave days coloured by status,
  - Today highlighted.
- Employee clicks a date → **Apply for leave** (date range + reason) → `pending`.
- Admin sees requests on the same calendar and can **Approve / Reject**, which stamps
  `decided_by` + `decided_at`.
- Admin manages shop-closed days (add/remove) from the Calendar page.

### 3.6 WhatsApp / SMS notifications
- Pluggable driver selected in **Settings → Notifications**:
  - `off` — disabled (default until configured),
  - `log` — writes to `storage/logs/notify.log` (safe default, useful for testing),
  - `whatsapp` — official **WhatsApp Cloud API** (`graph.facebook.com`), free tier; needs
    `notify_whatsapp_token` + `notify_whatsapp_phone_id` from https://developers.facebook.com,
  - `sms` — any HTTP SMS gateway you post to (url + api key + sender), e.g. a free Indian TRAI
    DLT provider you already have an account with.
- Zero-config fallback: every completed order also shows a **"Send on WhatsApp"** button that opens
  `wa.me/<phone>?text=<prefilled message>` with the order number. No API key, no cost — works today.
- Message includes the auto-generated order number and shop name.

### 3.7 Settings added
`notify_enabled`, `notify_driver`, `notify_whatsapp_token`, `notify_whatsapp_phone_id`,
`notify_gateway_url`, `notify_gateway_key`, `notify_from`, `shop_closed_weekdays`.

---

## 4. Decisions / trade-offs

- **Custom calendar instead of the bundled FullCalendar.** The theme ships FullCalendar **v4.3.1
  core** but its initializer script calls the **v3 jQuery** `.fullCalendar()` API, so the bundled
  demo is internally inconsistent. A small hand-written month grid (~150 lines, no jQuery version
  roulette) is lighter, fully responsive, and works offline.
- **`barryvdh/laravel-dompdf ^3.1`** added for the PDF invoice (auto-discovered, no config publish
  needed).
- **Notification defaults to `off`/`log`** so the app never silently "sends" anything before an
  admin configures a provider — no surprise API bills, no failed sends in production logs.
- Employees get a **restricted sidebar** (My Work, Calendar, Profile, Logout) via a role gate.

---

## 5. Improvements worth adding later (backlog)

1. **Delivery OTP / SMS link** — customer receives a link confirming delivery, closing the loop.
2. **Payment reminder** — auto WhatsApp nudge when `collecting_on` passes and balance > 0.
3. **Measurement templates per cloth type** — pre-fill which body parts apply (Shirt/Kurta/Trouser),
   so the tailor only sees relevant fields.
4. **Image capture** — photograph the customer's existing garment, stored with the measurement.
5. **Barcode / QR on the slip** — scan the physical slip to pull the order on the shop floor.
6. **Payroll roll-up** — connect `staff.salary` to approved leaves for automatic deductions.
7. **Offline-first (PWA)** — tailors often have poor connectivity; queue status changes and sync.
8. **Multi-till receipts** — thermal 80mm receipt layout as an alternative to the A4 invoice.
9. **Audit log** — who edited an amount after the fact (important for cash disputes).
10. **Bulk import** — existing customers/measurements from CSV on first setup.

---

## 6. Verification checklist

- [ ] `php artisan migrate` runs clean on a fresh DB.
- [ ] Order create auto-generates a unique `order_no`, saves a measurement, shows the slip.
- [ ] Returning customer's previous measurements appear and can fill the description.
- [ ] Assign → employee sees it in My Work → complete → history row + timestamp + notification.
- [ ] Calendar shows closed days; leave apply + approve/reject work.
- [ ] Invoice downloads as PDF.
- [ ] `php artisan test` green; every page renders 200 for admin and employee roles.
