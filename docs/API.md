# API Reference

Base URL: `http://localhost:8100/api`

All responses use the JSON envelope:
```json
{ "success": true, "message": "OK", "data": {...} }
{ "success": false, "message": "...", "errors": {"field": ["..."]} }
```

Paginated lists include:
```json
{ "data": [...], "pagination": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 98, "from": 1, "to": 20 } }
```

## Authentication

Session + CSRF (same-origin). Include `X-CSRF-TOKEN` header (from `<meta name="csrf-token">`) on all mutating requests.

| Method | Path | Role | Description |
|--------|------|------|-------------|
| POST | /login | public | `{email, password}` → sets session cookie |
| POST | /logout | auth | Invalidates session |
| GET | /me | auth | Returns current user + roles |

---

## Properties

| Method | Path | Role | Body / Params |
|--------|------|------|---------------|
| GET | /properties | auth | `?status, type, city, agent_id, q, page, per_page` |
| POST | /properties | admin/agent | `{title, type, price, address, city, description, status, bedrooms, bathrooms, area_sqft, agent_id, is_featured}` |
| GET | /properties/{id} | auth | — |
| PUT | /properties/{id} | admin/agent(own) | same as POST |
| DELETE | /properties/{id} | admin/agent(own) | — (soft delete) |
| PATCH | /properties/{id}/status | admin/agent(own) | `{status: available|reserved|sold}` |
| POST | /properties/{id}/images | admin/agent(own) | multipart `images[]` |
| DELETE | /properties/{id}/images/{img} | admin/agent(own) | — |
| PATCH | /properties/{id}/images/{img}/primary | admin/agent(own) | — |

---

## Agents

| Method | Path | Role | Body |
|--------|------|------|------|
| GET | /agents | admin | `?q, page, per_page` |
| POST | /agents | admin | `{name, email, password, license_no, bio, phone}` |
| GET | /agents/{id} | admin | — |
| PUT | /agents/{id} | admin | — |
| DELETE | /agents/{id} | admin | Blocked if agent has active bookings |

---

## Clients

| Method | Path | Role | Body |
|--------|------|------|------|
| GET | /clients | admin/agent | — |
| POST | /clients | admin | `{name, email, phone, address, notes, assigned_agent_id}` |
| GET | /clients/{id} | admin/agent | — |
| PUT | /clients/{id} | admin/agent(own) | — |
| DELETE | /clients/{id} | admin | — |
| GET | /clients/{id}/interactions | admin/agent | — |
| POST | /clients/{id}/interactions | admin/agent | `{type, notes, occurred_at}` |

---

## Inquiries

Status flow: `new → contacted → viewing_scheduled → negotiation → closed`

| Method | Path | Role | Body |
|--------|------|------|------|
| GET | /inquiries | auth | `?status, q, per_page` |
| POST | /inquiries | auth | `{property_id, client_id?, message}` |
| GET | /inquiries/{id} | auth | — |
| PUT | /inquiries/{id} | admin/agent | `{message}` |
| PATCH | /inquiries/{id}/status | admin/agent | `{status}` — illegal transition → 422 |
| DELETE | /inquiries/{id} | admin | — |

---

## Bookings

| Method | Path | Role | Body |
|--------|------|------|------|
| GET | /bookings | auth | `?status, date_from, date_to, property_id, page, per_page` |
| POST | /bookings | auth | `{property_id, client_id?, scheduled_at, duration_min?, notes?}` — overlap → 422 |
| GET | /bookings/{id} | auth | — |
| PUT | /bookings/{id} | admin/agent | `{notes}` |
| PATCH | /bookings/{id}/status | auth | `{status: scheduled|completed|cancelled}` |
| DELETE | /bookings/{id} | admin | — |

Double-booking protection: DB transaction + `SELECT … FOR UPDATE`. Overlapping bookings return `422` with field error on `scheduled_at`.

---

## Dashboard

| Method | Path | Role | Returns |
|--------|------|------|---------|
| GET | /dashboard/stats | auth | Properties/agents/clients/bookings counts (role-scoped) |
| GET | /dashboard/charts | auth | Property distribution, inquiry pipeline, bookings trend (30d) |
| GET | /dashboard/activity | auth | Last 20 audit log entries (role-scoped) |

---

## Logs

| Method | Path | Role | Params |
|--------|------|------|--------|
| GET | /logs | admin | `?q, date_from, date_to, actor_id, page, per_page` |

---

## Notifications

| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | /notifications | auth | `?unread=1` to filter unread |
| PATCH | /notifications/{id}/read | auth | Mark one as read |
| PATCH | /notifications/read-all | auth | Mark all as read |

Triggered automatically via `AuditLogger` on: `inquiry.submitted`, `booking.requested`, `inquiry.status_changed`, `property.status_changed`.

---

## Chat

| Method | Path | Role | Body |
|--------|------|------|------|
| GET | /chat/users | auth | Returns counterparties (admin → clients, client → admins) |
| GET | /chat/{userId}/messages | auth | Paginated message history |
| POST | /chat | auth | `{receiver_id, message}` — persists to MySQL + publishes to Redis |

---

## Socket Auth

| Method | Path | Role | Description |
|--------|------|------|-------------|
| GET | /socket/auth?token={hmac} | public | Called by Node.js to validate HMAC token. Returns `{userId, name, roles[]}` or 401. |

Token format: `{userId}|{timestamp}|{hmac}` where hmac = HMAC-SHA256(`userId|timestamp`, `APP_KEY`). TTL: 5 minutes.

---

## Enums

**PropertyStatus**: `available`, `reserved`, `sold`

**PropertyType**: `apartment`, `house`, `villa`, `land`, `commercial`, `office`

**InquiryStatus**: `new`, `contacted`, `viewing_scheduled`, `negotiation`, `closed`

**BookingStatus**: `scheduled`, `completed`, `cancelled`

**InteractionType**: `call`, `email`, `meeting`, `note`
