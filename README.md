# Real Estate Management System

A multi-agency real estate platform (Laravel 13 + jQuery + Tailwind) that runs entirely in Docker: one platform hosts many agencies, each managing its own staff, listings, and customers, while the public can browse every agency's available properties.

---

## Prerequisites

- **Docker** (Engine 20.10+)
- **Docker Compose** v2 (the `docker compose` plugin)

No local PHP, Node, Composer, or MySQL install is required — everything runs in containers.

---

## Run

```bash
docker compose up -d --build
```

This builds and starts all six services. On first boot the app container's entrypoint automatically:

1. Runs `composer install` if `vendor/` is missing.
2. Copies `.env.example` → `.env` if no `.env` exists.
3. Generates `APP_KEY` if not already set.
4. Waits for MySQL to become reachable.
5. Runs `php artisan migrate --force --seed` (migrations + demo data) and `php artisan storage:link`.

Allow ~30 s on the first run for MySQL to initialise and seeding to complete, then open **http://localhost:8100**.

---

## Services

| Service | Image / Build | Purpose | Port (host:container) | URL |
|---------|---------------|---------|-----------------------|-----|
| `app` | build `docker/php/Dockerfile` (`php:8.4-fpm` + Composer) | Laravel 13 PHP-FPM application | — (internal, served via nginx) | — |
| `nginx` | `nginx:1.27-alpine` | Web server / reverse proxy to PHP-FPM | `8100:80` | http://localhost:8100 |
| `mysql` | `mysql:8.4` | Primary database | `3308:3306` | — |
| `phpmyadmin` | `phpmyadmin:5.2` | Database admin UI | `8101:80` | http://localhost:8101 |
| `redis` | `redis:7-alpine` | Pub/sub bus for real-time notifications & chat | — (internal only) | — |
| `node` (socket-server) | build `./socket-server` (`node:20-alpine`, runs `server.js`) | Socket.IO websocket server | `6001:6001` | http://localhost:6001 |

The `node` service is the **socket-server**: it subscribes to Redis and pushes live notifications and chat over Socket.IO to the browser.

---

## Environment

The app runs with sensible defaults; the entrypoint auto-creates `.env` from `.env.example`, so **no manual configuration is required** to start. To customise, `cp .env.example .env` before the first `docker compose up` and edit these keys:

| Key | Default | Purpose |
|-----|---------|---------|
| `APP_URL` | `http://localhost:8100` | Public app URL |
| `DB_DATABASE` | `realestate` | MySQL database name |
| `DB_USERNAME` | `realestate` | MySQL user |
| `DB_PASSWORD` | `secret` | MySQL user password |
| `DB_ROOT_PASSWORD` | `rootsecret` | MySQL root password (used by phpMyAdmin) |
| `DB_PORT` | `3306` | MySQL port inside the container (host maps to `3308`) |
| `REDIS_HOST` / `REDIS_PORT` | `redis` / `6379` | Redis connection (real-time bus) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | `admin@realestate.com` / `password` | Seeded agency admin credentials |
| `SOCKET_URL` | `http://localhost:6001` | Socket.IO URL injected into the browser as `window.__SOCKET_URL__` |
| `SOCKET_CORS_ORIGIN` | `http://localhost:8100` | Allowed Socket.IO CORS origin |

The compose file passes `DB_*` into the `app`, `mysql`, and `phpmyadmin` services, and `REDIS_*` / `SOCKET_CORS_ORIGIN` / `LARAVEL_URL` (`http://nginx`) / `PORT` (`6001`) into the `node` socket-server.

---

## Volumes & persistence

**Named volumes:**

- `db_data` → `/var/lib/mysql` — MySQL data (survives `docker compose down`; removed by `down -v`).
- `redis_data` → `/data` — Redis data.

**Bind mounts:**

- `.` → `/var/www/html` on both `app` and `nginx` — the project source is mounted live, so code edits reflect immediately.
- `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf` — nginx site config.

---

## Common commands

```bash
# Follow logs
docker compose logs -f app
docker compose logs -f node          # socket-server

# Stop the stack (keeps data volumes)
docker compose down

# Stop and wipe all data (db_data, redis_data)
docker compose down -v

# Rebuild after Dockerfile / dependency changes
docker compose up -d --build

# Open a shell in the app container
docker compose exec app bash

# Migrations & demo data are run automatically by the entrypoint.
# To reset to a clean seeded state manually:
docker compose exec app php artisan migrate:fresh --seed

# Run the test suite (in-memory SQLite, no MySQL needed)
docker compose exec app php artisan test

# Clear caches after editing routes/views
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Restart just the socket-server
docker compose restart node
```

---

## Verify

Once the stack is up:

- **App** → http://localhost:8100
- **phpMyAdmin** → http://localhost:8101 (user `root`, password = `DB_ROOT_PASSWORD`, default `rootsecret`)
- **Socket.IO** → http://localhost:6001

The entrypoint seeds demo data automatically. **Every seeded account uses the password `password`.** There are two login pages:

- **Staff portal** → `http://localhost:8100/admin/login` (super admin, agency admin, agents)
- **Client portal** → `http://localhost:8100/login` (customers)

| Role | Email | Log in at | Lands on |
|------|-------|-----------|----------|
| Super Admin (platform owner) | `super@platform.com` | `/admin/login` | `/super` |
| Agency Admin (Sunrise Realty) | `admin@realestate.com` | `/admin/login` | `/` (dashboard) |
| Agent | `agent1@realestate.com` … `agent3@realestate.com` | `/admin/login` | `/` |
| Customer | `client1@realestate.com` … `client6@realestate.com` | `/login` | `/` |

Guests can browse all agencies' available listings at `http://localhost:8100/browse` with no account.

Seeded admin defaults can be overridden via `ADMIN_EMAIL` / `ADMIN_PASSWORD` (and `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD`) in `.env`.

---

## How it works

### The four roles

```
SUPER ADMIN (platform owner)  — no agency
  └─ manages agencies: create, edit, activate/deactivate, delete,
     reset an agency admin's password, and view each agency's full
     detail (info + agents + listings + customers)

AGENCY ADMIN  — belongs to one agency
  └─ full control of THEIR agency: properties, agents, customers,
     inquiries, bookings, logs. Sees only their own agency's data.

AGENT  — belongs to one agency
  └─ their own listings, assigned customers, their inquiries & bookings

CUSTOMER (client)  — platform-wide
  └─ browses every agency's properties, submits inquiries, requests
     viewings, and chats with the agency they engage
```

### Multi-tenant data isolation

Agency-owned data (`properties`, `agents`, `inquiries`, `bookings`, `clients`, `audit logs`) carries an `agency_id`. A global scope (`App\Traits\BelongsToAgency`) automatically filters every query to the logged-in staff member's agency, so an agency admin **only ever sees their own agency's data**. The super admin (no agency) and the public browse endpoint bypass the scope deliberately.

A customer becomes an agency's customer the first time they inquire or book on one of that agency's listings — that's when their `client` record is stamped with the agency.

### The four entry points

| Who | Where | What they see |
|-----|-------|---------------|
| **Guest** (no login) | `/browse` | All agencies' *available* listings, with search & filters. "Inquire" / "Book" prompt them to register. |
| **Customer** | `/login` → `/` | Their inquiries, bookings, and chat. Can browse and act across all agencies. |
| **Agency staff** | `/admin/login` → `/` | Dashboard, Properties, Agents, Clients, Inquiries (Kanban), Bookings, Chat, Logs — scoped to their agency. |
| **Super admin** | `/admin/login` → `/super` | Platform dashboard + Agencies management. |

### Super admin → agency management (`/super/agencies`)

- **Create agency** — provisions the agency + its first admin user, and shows a one-time auto-generated password to share.
- **View** — a detail panel with the agency's info, agents, **all property listings**, and customers (with inquiry/booking counts).
- **Edit** — update name, contact details, plan, etc.
- **Reset admin password** — regenerate the agency admin's password if it's lost.
- **Activate / Deactivate** — a deactivated agency's staff are blocked at login.
- **Delete** — removes the agency, its staff, agents, and listings; customers are detached (they keep their accounts).
- **Search / filter** by name, email, city, or status.

### Real-time (notifications + chat)

- **Notifications** fire on: inquiry submitted, inquiry status changed, booking requested, booking status changed, property status changed. Delivered MySQL → Redis → Node/Socket.IO → the browser bell badge.
- **Chat** is agency-staff ↔ customer, 1-on-1. Messages persist to MySQL and deliver live over Socket.IO.
- **Socket auth** uses an HMAC-SHA256 token signed with `APP_KEY` (5-minute TTL), validated by `GET /api/socket/auth`.

---

## Architecture

```
┌─────────────┐   ┌──────────┐   ┌────────┐
│  Browser    │──▶│  Nginx   │──▶│  PHP   │  Laravel 13
│  jQuery     │   │  :8100   │   │  :9000 │  + Tailwind CDN
└─────────────┘   └──────────┘   └──┬─────┘
       │                             │
       │ Socket.IO                   │ Redis::publish
       ▼                             ▼
┌─────────────┐             ┌───────────────┐
│  Node.js    │◀── Redis ───│  MySQL 8.4    │
│  Socket.IO  │  subscribe  │  + phpMyAdmin │
│  :6001      │             └───────────────┘
└─────────────┘
```

The frontend is server-rendered Blade *shells* that load data over AJAX (jQuery + a shared `api.js` wrapper). Pagination, search, and filters never reload the page.

---

## Notes

- Tailwind is loaded via the **Play CDN** — no build step. For production, swap to a compiled Tailwind build.
- `CACHE_STORE=database` is the default; Redis is used only as the real-time message bus, not a read cache.
- Properties use **soft deletes** — deleted listings remain in the DB and can be recovered via phpMyAdmin.
- Prices are displayed in **AED** throughout.
