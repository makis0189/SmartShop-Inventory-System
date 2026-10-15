# SmartShop Inventory System

SmartShop is a PHP/MySQL inventory and sales application designed for local development with XAMPP. Start with `DEPLOYMENT_GUIDE.md` for local setup and use `TEST_PLAN.md` for acceptance testing.

## Current modules
- Role-protected sign-in and user management (admin, manager, cashier).
- Inventory/product management and low-stock alerts.
- Sales entry, server-side stock validation, receipts and reporting.
- Business overview dashboard with live database KPIs.
- Read-only stock movement ledger with filters.
- Product category management, supplier directory and received-purchase workflow that updates stock and records movement history in a database transaction.
- External CSS design system (`Assets/css/enterprise-overrides.css`) and external purchase form JavaScript (`Assets/js/purchase-form.js`).

## Setup
1. Back up your current project folder and database.
2. Copy the project into `C:\xampp\htdocs\smartshop` (or your chosen folder).
3. Start Apache and MySQL in XAMPP.
4. Confirm the database `smartshop_inventory` and tables from `database.sql` exist. Do not re-import over a live database without a backup.
5. Configure DB variables in the environment or update local defaults in `config/db.php` for your local setup.
6. If no administrator exists, create one from Command Prompt with `C:\xampp\php\php.exe create_admin.php`.
7. Open `http://localhost/smartshop/login.php`.

**Important:** this is a development release candidate until acceptance tests pass on your own XAMPP/MySQL instance. PHP syntax checks are not a substitute for live database and browser testing. Do not expose a local XAMPP installation to the public internet.
