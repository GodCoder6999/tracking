# Tracking — Order Tracking & Analytics

Internal-only Laravel 11 app with three roles: **Owner**, **Dealer**, **Client**.

- **Owner** — single admin account. Hidden login at `/owner-gate-7k9m2x`. Creates dealers, manages products, views everything.
- **Dealer** — creates clients, creates and updates orders, uploads payment screenshots + dispatch bills.
- **Client** — sees their own orders and live status only (no ordering).

Built for Render hosting + GitHub Codespaces dev.

---

## Quick start — GitHub Codespaces

1. Push this repo to GitHub.
2. Open the repo on GitHub → **Code → Codespaces → Create codespace on main**.
3. Wait for `postCreateCommand` to finish (installs Composer + npm deps, migrates, seeds).
4. In the terminal run:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
5. Click the forwarded port **8000** to open the app.

### Demo logins

| Role   | URL                     | Email                  | Password       |
|--------|-------------------------|------------------------|----------------|
| Owner  | `/owner-gate-7k9m2x`    | `owner@tracking.local` | `ChangeMe!2026` |
| Dealer | `/dealer/login`         | `dealer@demo.local`    | `DealerDemo!1`  |
| Client | `/client/login`         | `client@demo.local`    | `ClientDemo!1`  |

> The Owner login is intentionally unlinked from the home page. Bookmark it.

---

## Deploy to Render

1. Push to GitHub.
2. Render → **New → Blueprint** → connect this repo → it reads `render.yaml`.
3. Render will provision:
   - Free PostgreSQL (`tracking-db`)
   - Web service running the app via Docker (`Dockerfile`)
4. In the Render dashboard set env vars marked `sync: false`:
   - `APP_URL` → your Render URL (e.g. `https://tracking.onrender.com`)
   - `OWNER_PASSWORD` → a strong password
5. First deploy runs migrations + seeds automatically.

> Render free web services sleep after 15 min idle (~30s cold start).
> File uploads (payment screenshots, bills) go to `storage/app/public`. Render free disk is **not persistent** — uploads will disappear on redeploy. For production, swap the `public` disk for S3 / Cloudinary in `config/filesystems.php`.

---

## Stack

- Laravel 11 (PHP 8.2)
- Tailwind CSS + Alpine.js + Chart.js
- SQLite for local/Codespace dev, PostgreSQL on Render
- Authentication: session-based, role-gated via `role` middleware
- Notifications: database + mail (logged in dev, configure SMTP for prod)

## Project layout

```
app/
  Http/Controllers/{Auth, Owner, Dealer, Client}
  Http/Middleware/EnsureRole.php
  Models/{User, Product, Order, OrderItem, Payment, Dispatch}
  Support/{OrderNumber, OrderMath}
  Notifications/OrderStatusUpdated.php
database/migrations/
database/seeders/DatabaseSeeder.php
resources/views/{auth, owner, dealer, client, partials, components/layouts}
routes/web.php
render.yaml       # Render blueprint
Dockerfile        # Render build
.devcontainer/    # Codespaces config
```

## Changing names / slug

- Role names in code are `owner`, `dealer`, `client`. Change display labels in Blade views only.
- Owner URL slug lives in `.env` as `OWNER_GATE_SLUG`. Change it and redeploy.

## Adding SMTP (real emails)

Set on Render:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
```
