# SmartShop Inventory System — Security Review

## Improvements included in this release candidate

- Session-based authentication for inventory, sales, receipt, alerts and reporting routes.
- Role checks: `admin` and `manager` can manage products/reports; `admin`, `manager` and `cashier` can use sales/receipts.
- Password verification uses PHP `password_verify()`; administrator setup stores a `password_hash()` hash.
- Session ID regeneration after successful login.
- HTTP-only session cookies, SameSite=Lax, strict session mode, and `Secure` cookies when HTTPS is detected.
- CSRF token validation for login, sale processing, product saves, product status changes, and logout.
- Prepared statements for user lookup and existing data-changing queries.
- Authenticated user ID is saved with completed sales and sale stock movements.
- Apache rules disable directory listings, add basic security headers, and deny direct access to database/config/helper files when `.htaccess` is supported.
- Database connection errors are logged server-side and do not reveal the SQL exception to the browser.
- Database credentials can be supplied through environment variables for deployment; local XAMPP defaults remain for development.

## Risks and controls still required before real production use

1. **Run the full acceptance test plan** on a separate test database. Static syntax checks are not proof of correct business behavior.
2. **Use a least-privilege database account** in production. Do not use MySQL `root` or a blank password outside a private local XAMPP environment.
3. **Require HTTPS** on any network-accessible deployment. Confirm session cookies have the Secure flag and redirect HTTP to HTTPS at the host/proxy.
4. **Add login rate limiting and monitoring** before exposing the app to the internet. Current generic login errors reduce account-enumeration detail but do not implement throttling.
5. **Create a backup and restore schedule** and test recovery, not just backup creation.
6. **Add an auditable stock-adjustment workflow** with permissions and reasons before staff can manually correct inventory. Do not directly edit stock in phpMyAdmin for normal business operations.
7. **Returns/cancellations need a controlled workflow.** Do not delete completed sales manually; stock and revenue must be reversed with traceable entries.
8. **Expense entry UI and supplier purchasing workflow are not fully implemented** merely because related database tables exist. Net profit is only as complete as the expense records entered.
9. **The public storefront includes static sample products and non-integrated Add to Cart controls.** Do not advertise it as a live e-commerce checkout. The inventory modules are a separate management workflow.
10. **Check `.htaccess` is honored** by the chosen Apache/hosting setup. If the host uses Nginx or disables overrides, apply equivalent server rules there.
11. **Protect backups and logs**; they may contain sales/customer/business data. Keep them outside the public web root and restrict access.
12. **Privacy and operations:** establish who may view sales, how staff accounts are disabled, and how business data is retained/exported.

## Known scope limitations

- No password reset, multi-factor authentication, or automatic account lockout.
- No automated test suite or live database integration test was run in this environment. The new dashboard, category, supplier and purchase workflows still require acceptance testing against a test database.
- This review is a practical development checklist, not a formal penetration test or legal compliance certification.
