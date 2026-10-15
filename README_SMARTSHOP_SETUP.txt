SMARTSHOP INVENTORY - PHASE 1 SETUP

This package preserves the original HTML/CSS storefront and adds the first PHP/MySQL inventory module:
- Product listing with search and active/inactive filters
- Add and edit products
- Opening stock history record
- Soft deactivate/activate products (products are not permanently deleted)
- Stock alerts based on current stock and minimum stock

IMPORTANT SECURITY NOTE
This is a local development phase. Login and role-based authorization are not included yet. Do not deploy these PHP pages to a public server until authentication and authorization are implemented.

XAMPP SETUP
1. Copy the project folder to C:\xampp\htdocs\smartshop (or your preferred folder under htdocs).
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin.
4. Import database.sql (this creates/selects smartshop_inventory and its tables).
5. Check config/db.php. Default XAMPP values are host 127.0.0.1, user root, blank password.
6. Open http://localhost/smartshop/ to see the original SmartShop storefront with Inventory Management and Stock Alerts links.
7. Open http://localhost/smartshop/products.php to manage inventory.
8. Add a category first in phpMyAdmin if desired, for example:
   INSERT INTO categories (name, description) VALUES ('Hardware', 'Hardware products');
9. Add a product using a unique SKU, prices, current stock and minimum stock.
10. Open http://localhost/smartshop/stock_alerts.php to see products at or below their minimum stock.

IF YOU USE LARAGON INSTEAD
You previously used C:\laragon\www\smartshop and http://smartshop.test. Put the project in that folder, start Apache/MySQL in Laragon, import database.sql through phpMyAdmin, check config/db.php, then open http://smartshop.test/products.php.

NOT YET IMPLEMENTED
- Login and roles
- Stock adjustment form
- Sales recording and receipt
- Purchases and expenses forms
- Reporting dashboard and exports

Do not manually change a product's stock quantity in phpMyAdmin after operations begin, because stock movement history should match the quantity. Stock changes should be implemented through stock movement workflows in the next phase.
