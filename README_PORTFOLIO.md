# SmartShop Inventory System

A PHP and MySQL inventory/sales management project developed for local XAMPP use, with a storefront-style landing page and a separate management workspace.

## Features

- Product records with SKU, category, buying/selling price, stock quantity and minimum stock.
- Sales transactions with multiple items, discounts, stock deduction in a database transaction, and printable receipts.
- Stock movement history for opening stock and completed sales.
- Low-stock and out-of-stock alerts.
- Date-filtered reporting for revenue, COGS, gross profit, expenses, estimated net profit, and best-selling products.
- Session login and roles for admin, manager and cashier.
- CSRF protection on login and data-changing forms.

## Stack

- PHP (PDO)
- MySQL/MariaDB
- HTML, CSS and JavaScript
- Apache/XAMPP for local development

## Local setup

Follow `DEPLOYMENT_GUIDE.md`. Run the acceptance tests in `TEST_PLAN.md` before using business data.

## Security and testing

See `SECURITY_REVIEW.md` for included controls and known limitations. This project has not received a formal penetration test or live integration test against a production database.

## Important scope notes

- The sample storefront uses static demo product cards and does not implement real cart checkout or online payments.
- Supplier purchasing, expense entry UI, returns, staff-account management UI, password reset, MFA and login rate limiting remain future work.
- Do not include real business data or credentials in a public portfolio repository or demo.
