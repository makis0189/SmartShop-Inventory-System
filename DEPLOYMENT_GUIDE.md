# SmartShop Inventory System — XAMPP and Deployment Guide

## A. Safe local installation on XAMPP

1. Stop Apache/MySQL if replacing a running project.
2. Back up `C:\xampp\htdocs\smartshop` and export the existing `smartshop_inventory` database from phpMyAdmin.
3. Extract this project into `C:\xampp\htdocs\smartshop` so that `index.php`, `login.php`, `products.php` and `config/` are directly inside that folder.
4. Start Apache and MySQL in XAMPP.
5. If the database already exists and has the expected tables, **do not re-import `database.sql`**. If creating a new test database, import `database.sql` into phpMyAdmin.
6. Check `config/db.php` only for local settings. Default local settings are host `127.0.0.1`, database `smartshop_inventory`, user `root`, blank password; these defaults are only for a private local development setup.
7. Create the first administrator using the PHP CLI. Open Command Prompt and run:

   ```bat
   cd C:\xampp\htdocs\smartshop
   C:\xampp\php\php.exe create_admin.php
   ```

   Use a unique username and a password of at least 12 characters. The current script reads the password from the terminal, so run it privately and do not share a recording/screenshot of the session. After successfully creating the administrator, remove `create_admin.php` from any deployment package or keep it inaccessible and archived outside the web root.

8. Open `http://localhost/smartshop/login.php` and sign in. The storefront homepage remains at `http://localhost/smartshop/`.
9. Verify `config/`, `includes/`, `database.sql` and `create_admin.php` cannot be downloaded directly. The included `.htaccess` rules require Apache overrides to be enabled.
10. Run every applicable test in `TEST_PLAN.md` using test data before switching to real transactions.

## B. Roles

- `admin`: products, sales, alerts and reports.
- `manager`: products, sales, alerts and reports.
- `cashier`: sales and receipts only.

Create and deactivate staff accounts from the administrator-only `Manage Users` page (`user_management.php`). The page is restricted to admins, uses CSRF tokens, hashes passwords, prevents deactivating the current account, and prevents deactivating the last active administrator. Do not share one staff login across multiple people.

## C. Production deployment checklist

Do not upload this application unchanged to a public server. Before going live:

- Choose PHP/MySQL hosting that meets the project's PHP and MySQL requirements.
- Create a separate production database and a dedicated database user with only required privileges; do not use `root`.
- Configure `SMARTSHOP_DB_HOST`, `SMARTSHOP_DB_NAME`, `SMARTSHOP_DB_USER`, and `SMARTSHOP_DB_PASS` as server environment variables. Do not commit real credentials to Git or store them in a public folder.
- Import the schema into the new production database only once, after reviewing and backing up any existing data.
- Enforce HTTPS and redirect HTTP to HTTPS. Confirm secure session cookies.
- Configure equivalent web-server rules to block directory listing and deny access to config, schema, helper, backup, log and setup files. Do not assume `.htaccess` works on every host.
- Remove `create_admin.php` and development/test artifacts from the production web root.
- Change all default credentials; create unique user accounts with least privilege.
- Set PHP production settings (`display_errors=Off`, `log_errors=On`) and configure protected log rotation.
- Set up encrypted/off-site database backups and test restoring them to a separate database.
- Test login, roles, CSRF, product creation, sale transaction rollback, stock alerts, reports, print receipt, mobile layout and error handling.
- Define how to handle returns, cancelled sales, stock corrections, expense entry and end-of-day reconciliation before staff training.
- Keep a rollback copy of the previous release and database backup before each update.

## D. Portfolio deployment

For a portfolio demo, use a separate demo database populated with synthetic products and sales. Never expose real stock, revenue, customer, supplier, or staff data. Add a README describing features, architecture, setup, screenshots, known limitations, and a clear statement that the storefront sample cart is not a live checkout/payment integration.

## E. Rollback

1. Put the app into maintenance/offline mode.
2. Restore the previous code backup.
3. If a schema/data migration was applied, restore the matching database backup to a separate database first and verify it before replacing the current database.
4. Confirm products and sales reconcile before reopening access.
