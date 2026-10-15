SMARTSHOP INVENTORY SYSTEM — INTEGRATED XAMPP BUILD

This ZIP contains one integrated project: the original SmartShop storefront plus Product Management, Sales Management, Stock Alerts, and Reporting Dashboard.

INSTALL
1. Stop editing/running an older copy for this test, and make a backup of C:\xampp\htdocs\smartshop.
2. Extract this ZIP. Open the folder named "SmartShop Inventory System".
3. Copy the CONTENTS of that folder into C:\xampp\htdocs\smartshop, allowing matching project files to be replaced. Do not copy the outer folder inside itself.
4. Start Apache and MySQL in XAMPP.
5. Open phpMyAdmin and check whether database smartshop_inventory already exists. If it exists and the current Products/Sales pages work, keep it; do not import the schema again. If it does not exist, import database.sql.
6. Open http://localhost/smartshop/index.php

PAGES
- Homepage/storefront: http://localhost/smartshop/
- Products: http://localhost/smartshop/products.php
- Sales: http://localhost/smartshop/sales.php
- Stock alerts: http://localhost/smartshop/stock_alerts.php
- Reporting dashboard: http://localhost/smartshop/reporting.php

END-TO-END TEST
A. Add a test product with a known selling price, buying price, stock quantity (e.g. 20), and minimum stock level (e.g. 5).
B. Make a sale for quantity 3. Verify stock falls from 20 to 17, a sale appears in Sales History, and its receipt opens.
C. Make another sale until stock is at or below the minimum. Verify Stock Alerts updates.
D. Open Reporting Dashboard and select a date range including the test sale. Verify sales revenue and gross profit reflect the saved transaction.
E. Record expenses only if you want them included in the net-profit estimate.

NOTES
- Reports use actual saved database records; empty periods should show zero.
- Gross profit uses sale_items.unit_cost recorded at the time of sale. Net profit is an estimate based on recorded expenses.
- Back up the database before making changes. Do not expose this local development build publicly before adding authentication/authorization and completing security testing.
- If MySQL credentials differ from default XAMPP, update config/db.php.
