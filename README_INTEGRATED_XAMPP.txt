SMARTSHOP - INTEGRATED XAMPP PACKAGE

This package merges the original SmartShop storefront with the PHP/MySQL inventory module.

SETUP
1. Extract the entire folder to C:\xampp\htdocs\smartshop
2. Start Apache and MySQL from XAMPP.
3. In phpMyAdmin, import SmartShop Inventory System/database.sql (inside this project folder).
4. Check config/db.php. Default XAMPP settings are host 127.0.0.1, database smartshop_inventory, user root, blank password.
5. Open http://localhost/smartshop/ . This opens the original storefront, now linked to Inventory Management and Stock Alerts.
6. Use the shortcuts or navigation links to open products.php and stock_alerts.php.

The original design, CSS, images and Font Awesome assets are retained. index.html redirects to index.php when served through Apache. Do not open PHP files by double-clicking them; use localhost through XAMPP.

This is still a local development build. Login/roles, sales entry, stock adjustment and reporting are not yet implemented.
