# SmartShop International UI/UX Upgrade — Stage 2

## Included
- Enterprise visual layer in `Assets/css/enterprise-overrides.css`, loaded after each module stylesheet (external CSS only).
- Responsive navigation, typography, buttons, form controls, tables, cards, status pills and focus states.
- New role-protected `dashboard.php` with live MySQL KPIs, recent sales, monthly top products, restock attention, quick links and estimated stock valuation.
- New read-only `stock_movements.php` with search/type/date filters and latest 250 movement records.
- New `categories.php` category creation and directory, plus `suppliers.php` supplier directory and `purchases.php` received-stock workflow using the existing supplier/purchase tables. Purchase posting is transactional: it updates stock, purchase totals/items and stock movement records together or rolls back on error.
- External `Assets/js/purchase-form.js` supports adding/removing purchase lines without inline JavaScript.
- Admin/manager sign-in now lands on the dashboard; cashier sign-in still lands on Sales.
- Inventory brand links lead back to the dashboard.
- Product search uses distinct named PDO placeholders for compatibility with native prepared statements.
- Dashboard and stock history remain protected by server-side role checks.

## Important
- Existing modules and database schema were preserved; no database import/migration is required for these additions. Categories, suppliers and purchase screens use tables already present in `database.sql`; the stock history page reads the existing `stock_movements` table.
- PHP syntax checks can only establish parse validity. This package has not been run against your local MySQL data, so verify the acceptance tests on a backup or test database.
- Backup/restore automation, returns workflow, automated login throttling, HTTPS production configuration and full end-to-end testing remain separate release tasks.
- The public storefront still contains static sample product cards and non-integrated cart controls; it should not be presented as a live e-commerce checkout.
