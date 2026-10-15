<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

$todaySales = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE status='completed' AND sale_date >= CURDATE() AND sale_date < CURDATE() + INTERVAL 1 DAY")->fetchColumn();
$monthSales = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE status='completed' AND sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND sale_date < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')")->fetchColumn();
$activeProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
$lowStock = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn();
$stockValue = (float) $pdo->query('SELECT COALESCE(SUM(stock_quantity * buying_price),0) FROM products WHERE is_active=1')->fetchColumn();
$recentSales = $pdo->query("SELECT s.id, s.sale_number, s.sale_date, s.total_amount, s.payment_method, s.status, u.full_name FROM sales s LEFT JOIN users u ON u.id=s.sold_by ORDER BY s.sale_date DESC LIMIT 7")->fetchAll();
$lowProducts = $pdo->query("SELECT name, sku, stock_quantity, minimum_stock, unit FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock ORDER BY (stock_quantity <= 0) DESC, stock_quantity ASC, name ASC LIMIT 6")->fetchAll();
$topProducts = $pdo->query("SELECT si.product_name_snapshot AS name, SUM(si.quantity) AS units_sold, SUM(si.line_total) AS sales_value FROM sale_items si INNER JOIN sales s ON s.id=si.sale_id WHERE s.status='completed' AND s.sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') GROUP BY si.product_name_snapshot ORDER BY units_sold DESC LIMIT 5")->fetchAll();
$lowCount = $lowStock;
$flash = get_flash();
function money_tsh($amount) { return 'TSh ' . number_format((float)$amount, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#c62828">
  <title>Overview | SmartShop Inventory</title>
  <link rel="stylesheet" href="Assets/css/style.css">
  <link rel="stylesheet" href="Assets/css/inventory.css">
  <link rel="stylesheet" href="Assets/fontawesome-free-7.3.1-web/css/all.min.css">
  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page">
<header class="inv-topbar">
  <a class="inv-brand" href="dashboard.php">SmartShop <span>Inventory</span></a>
  <?php require __DIR__ . '/includes/nav.php'; ?>
</header>
<main class="inv-container">
  <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
  <section class="dashboard-welcome">
    <div>
      <p class="eyebrow">BUSINESS OVERVIEW</p>
      <h1>Good day, <?= e(explode(' ', trim(current_user()['full_name'] ?? current_user()['username'] ?? 'there'))[0]) ?>.</h1>
      <p class="muted">Here is the current snapshot of your inventory and sales performance.</p>
    </div>
    <div class="dashboard-actions"><a class="btn btn-primary" href="sales.php"><i class="fa-solid fa-cash-register" aria-hidden="true"></i> Open sales</a><a class="btn btn-plain" href="product_form.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add product</a></div>
  </section>
  <section class="dashboard-grid" aria-label="Key performance indicators">
    <article class="dashboard-stat"><div class="stat-top"><span>Sales today</span><span class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></span></div><strong><?= e(money_tsh($todaySales)) ?></strong><small>Completed transactions only</small></article>
    <article class="dashboard-stat"><div class="stat-top"><span>Sales this month</span><span class="stat-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span></div><strong><?= e(money_tsh($monthSales)) ?></strong><small>Month-to-date total</small></article>
    <article class="dashboard-stat"><div class="stat-top"><span>Active products</span><span class="stat-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span></div><strong><?= number_format($activeProducts) ?></strong><small>Products available in catalog</small></article>
    <article class="dashboard-stat"><div class="stat-top"><span>Low-stock alerts</span><span class="stat-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span></div><strong><?= number_format($lowStock) ?></strong><small>At or below minimum quantity</small></article>
  </section>
  <section class="dashboard-columns">
    <div>
      <section class="dashboard-panel">
        <div class="dashboard-panel-head"><div><h2>Recent sales</h2><p>Latest transactions recorded in SmartShop</p></div><a href="sales.php">View sales <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        <div class="table-wrap"><table><thead><tr><th>Sale reference</th><th>Date</th><th>Cashier</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>
        <?php if (!$recentSales): ?><tr><td colspan="6" class="empty-state">No sales have been recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($recentSales as $sale): ?><tr><td><strong><?= e($sale['sale_number']) ?></strong></td><td><?= e(date('d M Y, H:i', strtotime($sale['sale_date']))) ?></td><td><?= e($sale['full_name'] ?: 'Former user') ?></td><td><span class="status-pill <?= $sale['status'] === 'completed' ? 'status-active' : 'status-inactive' ?>"><?= e(ucfirst($sale['status'])) ?></span></td><td><strong><?= e(money_tsh($sale['total_amount'])) ?></strong></td><td><a href="receipt.php?id=<?= (int)$sale['id'] ?>">Receipt</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
      </section>
      <section class="dashboard-panel">
        <div class="dashboard-panel-head"><div><h2>Top-selling products</h2><p>Based on completed sales this month</p></div><a href="reporting.php">Full reports <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        <?php if (!$topProducts): ?><div class="dashboard-empty">Product performance will appear here after completed sales.</div><?php else: ?><ul class="dashboard-list"><?php foreach ($topProducts as $product): ?><li><div class="item-main"><strong><?= e($product['name']) ?></strong><small><?= e(number_format((float)$product['units_sold'], 3, '.', '')) ?> units sold</small></div><span class="item-value"><?= e(money_tsh($product['sales_value'])) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      </section>
    </div>
    <aside>
      <section class="dashboard-panel">
        <div class="dashboard-panel-head"><div><h2>Quick actions</h2><p>Common inventory tasks</p></div></div>
        <div class="quick-links">
          <a class="quick-link" href="products.php"><span class="stat-icon"><i class="fa-solid fa-box" aria-hidden="true"></i></span><span><strong>Products</strong><small>Catalog and stock</small></span></a><a class="quick-link" href="categories.php"><span class="stat-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span><span><strong>Categories</strong><small>Organize products</small></span></a><a class="quick-link" href="purchases.php"><span class="stat-icon"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i></span><span><strong>Purchases</strong><small>Receive stock</small></span></a><a class="quick-link" href="suppliers.php"><span class="stat-icon"><i class="fa-solid fa-truck-field" aria-hidden="true"></i></span><span><strong>Suppliers</strong><small>Manage contacts</small></span></a>
          <a class="quick-link" href="product_form.php"><span class="stat-icon"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i></span><span><strong>Add product</strong><small>Create a new item</small></span></a>
          <a class="quick-link" href="stock_alerts.php"><span class="stat-icon"><i class="fa-solid fa-bell" aria-hidden="true"></i></span><span><strong>Stock alerts</strong><small><?= $lowStock ?> need attention</small></span></a>
          <a class="quick-link" href="stock_movements.php"><span class="stat-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span><span><strong>Stock history</strong><small>Trace stock changes</small></span></a>
          <a class="quick-link" href="reporting.php"><span class="stat-icon"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></span><span><strong>Reports</strong><small>Review performance</small></span></a>
          <?php if ((current_user()['role'] ?? '') === 'admin'): ?><a class="quick-link" href="user_management.php"><span class="stat-icon"><i class="fa-solid fa-users-gear" aria-hidden="true"></i></span><span><strong>Manage users</strong><small>Staff and permissions</small></span></a><?php endif; ?>
        </div>
      </section>
      <section class="dashboard-panel">
        <div class="dashboard-panel-head"><div><h2>Restock attention</h2><p>Products at or below minimum stock</p></div><a href="stock_alerts.php">Review</a></div>
        <?php if (!$lowProducts): ?><div class="dashboard-empty">All active products are above their minimum stock levels.</div><?php else: ?><ul class="dashboard-list"><?php foreach ($lowProducts as $product): ?><li><div class="item-main"><strong><?= e($product['name']) ?></strong><small><?= e($product['sku']) ?></small></div><span class="stock-pill <?= (float)$product['stock_quantity'] <= 0 ? 'stock-zero' : 'stock-low' ?>"><?= e(rtrim(rtrim(number_format((float)$product['stock_quantity'], 3, '.', ''), '0'), '.')) ?> <?= e($product['unit']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      </section>
      <section class="dashboard-panel"><div class="dashboard-panel-head"><div><h2>Inventory valuation</h2><p>Estimated value at recorded buying cost</p></div></div><div class="valuation-content"><strong class="valuation-value"><?= e(money_tsh($stockValue)) ?></strong><p class="muted valuation-note">Calculated from active stock quantities × buying prices. This is not a cash balance.</p></div></section>
    </aside>
  </section>
  <p class="muted dashboard-footnote">SmartShop Inventory · Internal operational overview. Confirm business totals against the Reports module.</p>
</main>
</body>
</html>
