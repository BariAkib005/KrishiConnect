# KrishiConnect

A PHP + MySQL platform connecting farmers, buyers, and finance providers.

## Requirements

- **XAMPP** (or any PHP 8.1+ and MySQL/MariaDB). PHP is auto-detected from
  `C:\xampp\php\php.exe` if it is not on your PATH.
- Python 3 (only to use the `run.py` launcher — optional).

## Running the project

1. Open the **XAMPP Control Panel** and start **MySQL**.
   The MySQL port does **not** matter — the app auto-detects it (it tries
   `3307`, then `3306`, then `3308`). You do **not** need to import any SQL
   manually: the `krishiconnect` database, tables, and seed data are created
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

| Role   | Email                        | Password    |
| ------ | ---------------------------- | ----------- |
| Admin  | admin@krishiconnect.test     | password123 |
| Farmer | farmer@krishiconnect.test    | password123 |
| Buyer  | buyer@krishiconnect.test     | password123 |

## Configuration (optional)

Everything works out of the box. If your setup is unusual, override any of
these environment variables before starting the server:

| Variable          | Default       | Notes                              |
| ----------------- | ------------- | ---------------------------------- |
| `KRISHI_DB_HOST`  | `127.0.0.1`   | Use an IP, not `localhost`.        |
| `KRISHI_DB_PORT`  | `3307`        | First port tried; 3306/3308 are also auto-tried. |
| `KRISHI_DB_NAME`  | `krishiconnect` |                                  |
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
