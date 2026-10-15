SMARTSHOP - STOCK ALERTS AND REPORTING DASHBOARD

This update preserves the original storefront and adds reporting.php to the current project. It also includes the Phase 2 sales pages and CSRF helper functions so the existing sales flow remains available.

XAMPP setup:
1. Back up your current C:\xampp\htdocs\smartshop folder.
2. Extract this ZIP into a temporary folder.
3. Copy the contents of the extracted "SmartShop Inventory System" folder into C:\xampp\htdocs\smartshop, allowing files to be replaced.
4. Keep your existing smartshop_inventory database; do not re-import database.sql over a working database unless you know it is needed.
5. Start Apache and MySQL.
6. Open http://localhost/smartshop/

Useful pages:
- Homepage: http://localhost/smartshop/
- Products: http://localhost/smartshop/products.php
- Sales: http://localhost/smartshop/sales.php
- Stock alerts: http://localhost/smartshop/stock_alerts.php
- Reporting dashboard: http://localhost/smartshop/reporting.php

Reports read completed sales from sales, item costs from sale_items.unit_cost, expenses from expenses, and current inventory from products. If no transactions have been saved, sales and profit reports will correctly show zero rather than invented sample values.

Important: net profit is an estimate based on recorded costs and expenses. Ensure buying prices are correct and record operating expenses for the selected period.
