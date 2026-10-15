<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

$today = new DateTimeImmutable('today');
$defaultFrom = $today->modify('first day of this month')->format('Y-m-d');
$defaultTo = $today->format('Y-m-d');
$from = trim($_GET['from'] ?? $defaultFrom);
$to = trim($_GET['to'] ?? $defaultTo);
$isDate = static function ($value) { if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false; $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); return $date !== false && $date->format('Y-m-d') === $value; };
if (!$isDate($from)) $from = $defaultFrom;
if (!$isDate($to)) $to = $defaultTo;
if ($from > $to) { $swap = $from; $from = $to; $to = $swap; }
$start = $from . ' 00:00:00';
$endExclusive = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
$params = [':start' => $start, ':end' => $endExclusive];

$summarySql = "SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amount),0) AS revenue, COALESCE(SUM(subtotal),0) AS subtotal, COALESCE(SUM(discount),0) AS discounts FROM sales WHERE status='completed' AND sale_date >= :start AND sale_date < :end";
$summaryStmt = $pdo->prepare($summarySql); $summaryStmt->execute($params); $summary = $summaryStmt->fetch();
$cogsStmt = $pdo->prepare("SELECT COALESCE(SUM(si.quantity * si.unit_cost),0) FROM sale_items si INNER JOIN sales s ON s.id=si.sale_id WHERE s.status='completed' AND s.sale_date >= :start AND s.sale_date < :end");
$cogsStmt->execute($params); $cogs = (float)$cogsStmt->fetchColumn();
$expenseStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= :start AND expense_date < :end');
$expenseStmt->execute($params); $expenses = (float)$expenseStmt->fetchColumn();
$revenue = (float)$summary['revenue'];
$grossProfit = $revenue - $cogs;
$netProfit = $grossProfit - $expenses;

$stockCounts = $pdo->query("SELECT COUNT(*) AS active_products, SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_count, SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= minimum_stock THEN 1 ELSE 0 END) AS low_count FROM products WHERE is_active=1")->fetch();
$topStmt = $pdo->prepare("SELECT si.product_name_snapshot AS product_name, si.sku_snapshot AS sku, SUM(si.quantity) AS units_sold, SUM(si.line_total) AS gross_line_sales FROM sale_items si INNER JOIN sales s ON s.id=si.sale_id WHERE s.status='completed' AND s.sale_date >= :start AND s.sale_date < :end GROUP BY si.product_name_snapshot, si.sku_snapshot ORDER BY units_sold DESC, gross_line_sales DESC LIMIT 8");
$topStmt->execute($params); $topProducts = $topStmt->fetchAll();

$chartStart = $today->modify('-6 days')->format('Y-m-d');
$chartEnd = $today->modify('+1 day')->format('Y-m-d');
$chartStmt = $pdo->prepare("SELECT DATE(sale_date) AS day, COALESCE(SUM(total_amount),0) AS revenue FROM sales WHERE status='completed' AND sale_date >= :start AND sale_date < :end GROUP BY DATE(sale_date) ORDER BY day");
$chartStmt->execute([':start'=>$chartStart.' 00:00:00', ':end'=>$chartEnd.' 00:00:00']);
$chartRows = $chartStmt->fetchAll(); $chartMap = [];
foreach ($chartRows as $row) $chartMap[$row['day']] = (float)$row['revenue'];
$chart = [];
for ($i=6; $i>=0; $i--) { $day=$today->modify('-'.$i.' days'); $key=$day->format('Y-m-d'); $chart[]=['label'=>$day->format('D'), 'date'=>$key, 'revenue'=>$chartMap[$key] ?? 0.0]; }
$maxChart = max(array_merge([1.0], array_column($chart, 'revenue')));
$money = static function ($value) { return 'TSh ' . number_format((float)$value, 2); };
$qty = static function ($value) { return rtrim(rtrim(number_format((float)$value, 3, '.', ','), '0'), '.'); };
$lowCount=(int)($stockCounts['low_count'] ?? 0); $outCount=(int)($stockCounts['out_count'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Reporting Dashboard | SmartShop
  </title>
  <link href="Assets/css/style.css" rel="stylesheet"/>
  <link href="Assets/css/inventory.css" rel="stylesheet"/>
  <link rel="stylesheet" href="Assets/css/reporting.css">
  <link rel="stylesheet" href="Assets/css/chart-heights.css">
   <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
 <body class="inventory-page">
  <header class="inv-topbar">
   <a class="inv-brand" href="dashboard.php">
    SmartShop
    <span>
     Inventory
    </span>
   </a>
   <?php require __DIR__ . '/includes/nav.php'; ?>
  </header>
  <main class="inv-container">
   <div class="inv-heading">
    <div>
     <p class="eyebrow">
      BUSINESS PERFORMANCE
     </p>
     <h1>
      Reporting dashboard
     </h1>
     <p class="muted">
      Live summaries calculated from your saved sales, sale items, expenses and stock records.
     </p>
    </div>
    <div class="report-tools">
     <button class="btn btn-secondary" onclick="window.print()" type="button">
      Print report
     </button>
     <a class="btn btn-plain" href="sales.php">
      View sales
     </a>
    </div>
   </div>
   <form method="get" class="inv-card filter-row date-filter date-filter--spaced">
    <label>
     From date
     <input name="from" required="" type="date" value="<?= e($from) ?>"/>
    </label>
    <label>
     To date
     <input name="to" required="" type="date" value="<?= e($to) ?>"/>
    </label>
    <button class="btn btn-primary" type="submit">
     Apply filters
    </button>
    <a class="btn btn-plain" href="reporting.php">
     This month
    </a>
    <span class="muted report-range-meta">
     Period: <?= e(date('d M Y',strtotime($from))) ?> – <?= e(date('d M Y',strtotime($to))) ?>
    </span>
   </form>
   <section class="report-grid">
    <div class="report-stat">
     <span class="label">
      Completed sales
     </span>
     <strong>
      <?= number_format((int)$summary['sale_count']) ?>
     </strong>
     <small>
      Transactions in selected period
     </small>
    </div>
    <div class="report-stat">
     <span class="label">
      Sales revenue
     </span>
     <strong>
      <?= e($money($revenue)) ?>
     </strong>
     <small>
      After recorded discounts
     </small>
    </div>
    <div class="report-stat">
     <span class="label">
      Gross profit
     </span>
     <strong>
      <?= e($money($grossProfit)) ?>
     </strong>
     <small>
      Revenue minus recorded item cost
     </small>
    </div>
    <div class="report-stat">
     <span class="label">
      Net profit estimate
     </span>
     <strong>
      <?= e($money($netProfit)) ?>
     </strong>
     <small>
      Gross profit minus recorded expenses
     </small>
    </div>
   </section>
   <div class="report-panels">
    <section class="report-panel">
     <div class="report-panel-head">
      <h2>
       Sales trend · last 7 days
      </h2>
      <p>
       Completed sales by date, from the database
      </p>
     </div>
     <div aria-label="Bar chart of completed sales for the last seven days" class="chart" role="img">
      <?php foreach($chart as $day): $height=$day['revenue']>0?max(4,($day['revenue']/$maxChart)*105):2; ?>
      <div class="chart-day">
       <span class="chart-value">
        <?= e(number_format($day['revenue'],0)) ?>
       </span>
       <div class="chart-track">
        <div class="chart-bar chart-bar--height-<?= (int) round($height) ?>">
        </div>
       </div>
       <span class="chart-label">
        <?= e($day['label']) ?>
       </span>
      </div>
      <?php endforeach; ?>
     </div>
     <div class="chart-caption">
      Values shown in TSh. Days without completed sales show zero.
     </div>
    </section>
    <section class="report-panel">
     <div class="report-panel-head">
      <h2>
       Profit breakdown
      </h2>
      <p>
       For <?= e(date('d M',strtotime($from))) ?> – <?= e(date('d M Y',strtotime($to))) ?>
      </p>
     </div>
     <div class="profit-list">
      <div class="profit-row">
       <span>
        Revenue after discounts
       </span>
       <strong>
        <?= e($money($revenue)) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Cost of goods sold
       </span>
       <strong>
        − <?= e($money($cogs)) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Gross profit
       </span>
       <strong>
        <?= e($money($grossProfit)) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Operating expenses recorded
       </span>
       <strong>
        − <?= e($money($expenses)) ?>
       </strong>
      </div>
      <div class="profit-row total">
       <span>
        Estimated net profit
       </span>
       <strong>
        <?= e($money($netProfit)) ?>
       </strong>
      </div>
     </div>
     <p class="report-note">
      Profit is an estimate based on unit costs saved with sale items and expenses entered for this period. Missing or inaccurate costs/expenses affect accuracy.
     </p>
    </section>
   </div>
   <div class="report-bottom">
    <section class="report-panel">
     <div class="report-panel-head">
      <h2>
       Best-selling products
      </h2>
      <p>
       Ranked by quantity sold in the selected period
      </p>
     </div>
     <div class="table-wrap">
      <table>
       <thead>
        <tr>
         <th>
          Product
         </th>
         <th>
          SKU
         </th>
         <th>
          Units sold
         </th>
         <th>
          Line sales
         </th>
        </tr>
       </thead>
       <tbody>
        <?php if(!$topProducts): ?>
        <tr>
         <td class="empty-state" colspan="4">
          No completed sales in this period yet.
         </td>
        </tr>
        <?php else: foreach($topProducts as $product): ?>
        <tr>
         <td>
          <strong>
           <?= e($product['product_name']) ?>
          </strong>
         </td>
         <td>
          <?= e($product['sku']) ?>
         </td>
         <td>
          <?= e($qty($product['units_sold'])) ?>
         </td>
         <td>
          <?= e($money($product['gross_line_sales'])) ?>
         </td>
        </tr>
        <?php endforeach; endif; ?>
       </tbody>
      </table>
     </div>
    </section>
    <section class="report-panel">
     <div class="report-panel-head">
      <h2>
       Inventory health
      </h2>
      <p>
       Current stock status, independent of selected sales dates
      </p>
     </div>
     <div class="profit-list">
      <div class="profit-row">
       <span>
        Active products
       </span>
       <strong>
        <?= number_format((int)($stockCounts['active_products'] ?? 0)) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Low-stock products
       </span>
       <strong>
        <?= number_format($lowCount) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Out-of-stock products
       </span>
       <strong>
        <?= number_format($outCount) ?>
       </strong>
      </div>
      <div class="profit-row">
       <span>
        Alerts needing attention
       </span>
       <strong>
        <?= number_format($lowCount+$outCount) ?>
       </strong>
      </div>
     </div>
     <p class="report-note">
      <a href="stock_alerts.php">
       Open stock alerts →
      </a>
      to see each product and the suggested restock quantity.
     </p>
    </section>
   </div>
  </main>
 </body>
</html>
