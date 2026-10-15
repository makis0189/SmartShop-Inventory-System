# SmartShop Inventory System — QA Test Plan

## Release status

This is a **local release candidate**, not a production-certified release. PHP syntax and static checks can be run locally, but the final acceptance test must be performed on the user's XAMPP/MySQL data because this environment cannot access that running database.

## Before testing

1. Back up the current project folder and export the current `smartshop_inventory` database in phpMyAdmin.
2. Install the release into a separate test folder first if possible.
3. Confirm Apache and MySQL are running and `database.sql` has already been imported (do not re-import it into a database containing live data).
4. Create an administrator with `create_admin.php` using the CLI instructions in `DEPLOYMENT_GUIDE.md`.
5. Use test products and test sales. Do not use real business transactions during acceptance testing.

## Test cases

| ID | Area | Test | Expected result | Result |
|---|---|---|---|---|
| AUTH-01 | Login | Open `products.php` while signed out | Redirects to `login.php` | ☐ |
| AUTH-02 | Login | Sign in with valid active admin credentials | Redirects to Products | ☐ |
| AUTH-03 | Login | Use an incorrect password | Generic login error; no access | ☐ |
| AUTH-04 | Roles | Sign in as cashier and open `products.php` directly | Access denied | ☐ |
| AUTH-05 | Logout | Use Sign out, then revisit a protected page | Session is ended; login required | ☐ |
| SEC-01 | CSRF | Submit product or sale POST without a valid CSRF token | Request is rejected; no database changes | ☐ |
| PROD-01 | Products | Create a product with a unique SKU | Product is saved and appears in Products | ☐ |
| PROD-02 | Products | Reuse an existing SKU | Validation error; no duplicate SKU | ☐ |
| PROD-03 | Products | Submit negative price/stock or invalid unit | Request is rejected | ☐ |
| PROD-04 | Products | Deactivate a product | Product cannot be sold; history remains | ☐ |
| SALE-01 | Sales | Sell 3 units when stock is 20 | Sale saved; stock becomes 17 | ☐ |
| SALE-02 | Sales | Attempt to sell more than available stock | Sale fails; stock is unchanged | ☐ |
| SALE-03 | Sales | Submit the same product on two sale lines | Quantities are aggregated and stock is deducted correctly | ☐ |
| SALE-04 | Sales | Use a discount greater than subtotal | Sale rejected | ☐ |
| SALE-05 | Sales | Complete a sale and open its receipt | Receipt shows saved sale and item snapshots | ☐ |
| SALE-06 | Stock ledger | Complete a sale | A `stock_movements` sale row records before/after quantities and user | ☐ |
| ALERT-01 | Stock alerts | Set stock to the minimum level | Product appears in low-stock alerts | ☐ |
| ALERT-02 | Stock alerts | Set stock to zero | Product appears as out of stock | ☐ |
| ALERT-03 | Stock alerts | Increase stock above minimum | Product leaves the alert list | ☐ |
| REP-01 | Reports | Choose a date range with completed sales | Revenue and sale count match `sales` | ☐ |
| REP-02 | Reports | Compare COGS | COGS matches sum of sold quantity × saved unit cost | ☐ |
| REP-03 | Reports | Add expenses in `expenses` for the range | Net profit changes by the expense total | ☐ |
| REP-04 | Reports | Choose a range with no sales | Revenue and counts show zero without errors | ☐ |
| WEB-01 | Routing | Open `/config/db.php`, `/includes/helpers.php`, and `/database.sql` in browser | Apache denies direct access to protected files (verify `.htaccess` is enabled) | ☐ |
| REG-01 | Regression | Check homepage, images, CSS, products, sales, alerts and reports | Existing UI/assets remain usable | ☐ |

## Acceptance criteria

- All AUTH, SEC, PROD, SALE, ALERT, REP, WEB and REG cases pass.
- No PHP warnings, notices, uncaught exceptions, or database errors appear during normal use.
- Database backup can be restored to a separate test database.
- All totals are reconciled against the actual rows for the same date range.
- No production deployment until unresolved critical/high findings are closed.

## Record a failure

For each failure, record: test ID, exact URL, steps, expected result, actual result, screenshot/error text, and whether the database changed. Never include real passwords or private customer information in screenshots.

## Stage 2 UI/UX and procurement acceptance checks

Run on a backed-up database or test database:

1. **Role landing pages** — Admin and Manager sign in to `dashboard.php`; Cashier lands on `sales.php`.
2. **Role enforcement** — Cashier cannot open `dashboard.php`, `products.php`, `product_form.php`, `stock_alerts.php`, `stock_movements.php`, `purchases.php`, `suppliers.php`, `reporting.php`, or `user_management.php` directly. Verify server-side denial, not only hidden navigation.
3. **Dashboard data** — Compare today's/month-to-date sales and low-stock count against database records and Reports. Check the empty state with no sales.
4. **Product search** — Search by product name, SKU and category. Verify Active, Inactive and All filters preserve the chosen option.
5. **Category creation** — Add a category and confirm it appears in the product form; test duplicate category names.
6. **Supplier creation** — Add a supplier with valid fields, then try invalid email and overlong fields. Verify validation and no duplicate/blank records.
7. **Purchase posting** — On a test product, record a received purchase. Verify purchase header, purchase items, stock quantity, buying cost and stock movement are all updated together.
8. **Purchase rollback** — Submit a purchase containing an invalid/inactive product ID using a test request. Confirm no purchase or stock changes are committed.
9. **Stock history** — Filter by product/SKU, movement type and dates. Confirm purchase and sale movements show correct before/change/after quantities.
10. **Responsive UI** — Test dashboard, products, sales, suppliers and purchases at desktop, tablet and phone widths. Check horizontal table scrolling and keyboard focus states.
11. **External styling** — Confirm pages load `Assets/css/enterprise-overrides.css`; there should be no `<style>` blocks or HTML `style` attributes in PHP/HTML views.
12. **Sales regression** — Repeat existing sale tests and confirm stock decreases exactly once, receipt values match saved sales, and overselling is rejected.

Live MySQL/browser checks remain to be completed by the project owner because the local XAMPP database is not available in this build environment.
