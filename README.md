# KrishiConnect

A PHP + MySQL platform connecting farmers, buyers, and finance providers.

## Tech Stack

**Backend**

- **PHP 8.1+** — no framework; a small custom structure (`pages/` views,
  `app/actions/` request handlers, `app/includes/` shared library).
- **PDO** (MySQL driver) with **prepared statements** for all database access.
- No Composer / third-party PHP packages — standard library only.

**Database**

- **MySQL / MariaDB** (InnoDB, `utf8mb4`).
- Schema + seed data in `app/sql/`, created automatically on first run, with
  idempotent migrations in `app/includes/db.php`.
- Uses foreign keys, transactions, a JSON column, and `INFORMATION_SCHEMA`
  introspection.

**Frontend**

- Server-rendered HTML (PHP templates) — no SPA framework and no build step.
- Hand-written CSS (`css/styles.css`) using custom properties, Flexbox, Grid,
  and responsive media queries.
- **Vanilla JavaScript** (small inline scripts; no jQuery/React/Vue).
- **Font Awesome 6.5.1** (icons) and **Google Fonts** (Inter, DM Sans) via CDN.
- **Bootstrap 5.3.3** (CDN) — used only on the Admin PIN login page.

**Security**

- Hardened PHP sessions (custom save path, HttpOnly/SameSite cookies, ID
  regeneration).
- `password_hash`/`password_verify` (bcrypt) for passwords and the admin PIN.
- CSRF tokens (`random_bytes` + `hash_equals`) on all state-changing forms.
- Role-based access control and prepared statements throughout.

**Payments**

- **SSLCommerz** payment gateway (sandbox by default), integrated server-side
  via PHP **cURL** (`app/includes/sslcommerz.php`).

**Tooling / Dev environment**

- **XAMPP** (Apache + MySQL) or the PHP built-in dev server.
- `run.py` — optional **Python 3** launcher for the dev server.
- **Git** for version control.

## Requirements

- **XAMPP** (or any PHP 8.1+ and MySQL/MariaDB). PHP is auto-detected from
  `C:\xampp\php\php.exe` if it is not on your PATH.
- Python 3 (only to use the `run.py` launcher — optional).

## Running the project

1. Open the **XAMPP Control Panel** and start **MySQL**.
   The MySQL port does **not** matter — the app auto-detects it (it tries
   `3307`, then `3306`, then `3308`). You do **not** need to import any SQL
   manually: the `krishiconnect_db` database, tables, and seed data are created
   automatically the first time the app connects.
2. Start the dev server from the project folder:

   ```bash
   python run.py
   ```

   or directly with PHP:

   ```bash
   php -S localhost:8000 -t .
   ```

3. Open <http://localhost:8000/index.php>.

### Demo accounts (created by the seed data)

Sign in at <http://localhost:8000/pages/login.php>.

| Role   | Email                     | Password    |
| ------ | ------------------------- | ----------- |
| Farmer | farmer@krishiconnect.test | password123 |
| Buyer  | buyer@krishiconnect.test  | password123 |

A second set of ready-to-use accounts shares the password `12345678`:

| Role            | Email                      |
| --------------- | -------------------------- |
| Farmer          | farmer1@krishiconnect.com  |
| Buyer           | buyer1@krishiconnect.com   |
| Finance Officer | finance1@krishiconnect.com |

**Admins** do not use the email/password form. Open the
**Admin PIN login** (<http://localhost:8000/pages/admin_login.php>) and enter
the demo PIN **123456**.

> New **Finance** sign-ups are created in a *pending* state and must be
> activated by an admin (User Moderation → **Approve**) before they can log in.

## Configuration (optional)

Everything works out of the box. If your setup is unusual, override any of
these environment variables before starting the server:

| Variable          | Default       | Notes                              |
| ----------------- | ------------- | ---------------------------------- |
| `KRISHI_DB_HOST`  | `127.0.0.1`   | Use an IP, not `localhost`.        |
| `KRISHI_DB_PORT`  | `3307`        | First port tried; 3306/3308 are also auto-tried. |
| `KRISHI_DB_NAME`  | `krishiconnect_db` |                               |
| `KRISHI_DB_USER`  | `root`        |                                    |
| `KRISHI_DB_PASS`  | *(empty)*     | Set this if your MySQL root has a password. |

Example (Windows `cmd`):

```bat
set KRISHI_DB_PORT=3306
python run.py
```

## Troubleshooting

- **"Could not connect to the database"** — MySQL is not running. Start it in
  the XAMPP Control Panel. If your root user has a password, set
  `KRISHI_DB_PASS`.
