# Changelog

## 2026-10-15 — Audit, security hardening, Vite frontend

### Security fixes
- **Tenant/role isolation**: Agent, Client, Inquiry and Booking controllers now call `authorize()` against their policies. Policies check the user's agency and record ownership. Client-role users can no longer list or view other agencies' clients and agents, or add interactions to them.
- **Tenant scope** (`app/Traits/BelongsToAgency.php`): the `runningInConsole()` bypass is removed. The scope now applies whenever the logged-in web user has an `agency_id`, so tests exercise it.
- **Client IDOR**: client-role users always act as their own `Client` record on inquiry and booking create. Any `client_id` they send is ignored.
- **Cross-tenant references**: `exists:` rules are replaced by `App\Support\TenantRules` (agent, client and property scoped to the agency). This applies to property, client, inquiry, booking and interaction requests.
- **Open redirect / `javascript:` XSS** on register (`AuthController::safeRedirect`): only same-origin relative paths are accepted.
- **Rate limiting**: a new `auth` limiter (10/min per email+IP) is applied to `/api/login`, `/api/staff/login` and `/api/register`. `POST /api/chat` is limited to 60/min.
- **Stored XSS in the front end**: a global `esc()` in `public/js/api.js` escapes `& < > " '`. It replaces `$('<s>').text().html()`, which did not escape quotes, at 44 call sites. Inline `onclick` handlers built from data are replaced by delegated handlers.
- **Layout script injection**: user name, roles and the socket token are emitted with `@json`. Previously they used `addslashes` and `{!! json_encode !!}`.
- **Chat**: `ChatService::contactIds` now decides who a user can see and message. Users outside that list get 403.
- **Error leakage**: `AgentController` no longer returns raw exception messages.
- **Mass changes via general update**: `status` is removed from `UpdatePropertyRequest`, so status changes go through the audited endpoint. `is_featured` is now admin-only on both create and update.
- **Dependencies**: `composer update` of guzzle, psr7, laravel/framework (13.16 → 13.36), commonmark and flysystem. This clears 22 advisories, and `composer audit` is now clean.

### Bug fixes
- `POST /api/clients/{id}/interactions` always returned 500 because of an undefined `agent_id` key.
- Bookings:
  - A cancelled slot can now be re-booked. The DB unique key on `(property_id, scheduled_at)` is replaced by an index, and overlap is enforced under a row lock.
  - Unique-key races return 422 instead of 500.
  - Status changes follow a transition map: scheduled → completed or cancelled, and cancelled → scheduled (re-checked for overlap).
- Deleting an agency failed on soft-deleted properties (restrictOnDelete FK). Image files on disk are now removed as well.
- Deleting an agent that has properties now returns 422 instead of a 500 FK error.
- `per_page=0` or a negative value caused division by zero. All lists now clamp it through the `ClampsPerPage` trait.
- Public browse now hides listings of deactivated agencies.
- Audit logs written by clients now carry the agency of the record they act on.
- Super admin:
  - can read logs and the dashboard;
  - gets 422 instead of 500 when creating a property.
- The `/` route now requires the `active` middleware.
- `env('SOCKET_URL')` in Blade is replaced by `config('services.socket.url')`. `AgencySeeder` reads the new `config/platform.php`.
- Notification and chat toasts never fired (`typeof Toast` typo).
- `phpunit.xml`: tests silently used a sqlite file `./realestate` instead of `:memory:`. Docker exports `DB_DATABASE`, which phpunit's `<env>` did not override; `force` and `<server>` overrides were added.
- PHP 8.4 deprecation (implicit nullable) in `PropertyService`, and dead code in `AuditLog` and `AgencyController`.
- Found during browser QA:
  - Web pages are now gated by role: `/agents` and `/clients` use `can:viewAny`, and `/logs` uses the role admin|super_admin. Before, clients could open these shells and got 403s from the API.
  - Bookings and interactions sent the browser's local time without a timezone, so times displayed shifted by the UTC offset. The JS now sends ISO UTC.
  - An interaction's `occurred_at` is optional and defaults to now.
  - `/api/browse?q[]=x` returned a 500 "Array to string conversion". The query params are now validated.
  - `AuditLog` and `ChatMessage` never set `created_at` (`$timestamps = false`), so new entries showed "Jan 1". They now use `UPDATED_AT = null`.
  - Escape now closes the mobile sidebar.
  - Staff now log out to `/admin/login`.
  - The inquiry "Move to stage" select disables transitions the server would reject.

### UI / UX
- Tailwind now builds with Vite (Tailwind v4) instead of the Play CDN. `public/css/app.css` is merged into `resources/css/app.css` and removed. Chart.js no longer loads twice on the dashboard.
- Shared component classes `.btn*`, `.input`, `.label`, `.badge-*` and `.card` are applied across all pages.
- Accessibility:
  - labels are tied to their inputs;
  - `aria-label` added on icon buttons;
  - visible `:focus-visible` rings;
  - a skip link;
  - modals have `role="dialog"`, a focus trap, focus restore on close and Escape to close.
- The inquiry kanban has a "Move to stage" select, so it can be used without drag-and-drop by keyboard or touch.
- A shared windowed pager with first/last pages and ellipsis is used by tables, the property grid, bookings, logs and agencies.
- Duplicate-submit guards and loading states are on every async form. A toast now appears on network failure.
- Sidebar links are filtered by role: Agents and Clients for staff only, Logs for admins only.
- Mobile fixes:
  - modals limited to 90vh and scrollable;
  - two-column forms collapse to one column;
  - filter bars wrap;
  - toasts are full width.

### Database
- New migration `2026_10_15_100001_relax_bookings_property_slot_unique` (reversible).
- New factories: Agency, Agent, Client, Property, PropertyImage, Inquiry, Booking, ClientInteraction.

### Docker / configuration
- `entrypoint.sh` always runs `migrate --force`. Seeding now runs only if `SEED_ON_BOOT=true`, which is passed through `docker-compose.yml` and defaults to `false`. Set `SEED_ON_BOOT=true` in `.env` to seed a fresh database.
- `storage:link` is skipped when the link already exists.
- nginx:
  - sends `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy`;
  - `server_tokens off`;
  - denies all dotfiles except `.well-known`;
  - limits request bodies to 64M (was 256M).
- `php.ini`: `expose_php=Off`, `upload_max_filesize=10M` and `post_max_size=64M` (were 256M).
- The front-end build is now required: `npm install && npm run build`. In Docker:
  `docker run --rm --mount type=bind,source="$PWD",target=/app -w /app node:22-alpine sh -c "npm install && npm run build"`.
  Use `--mount`, because the project path contains spaces.
- The app image was rebuilt (`docker compose build app`).
