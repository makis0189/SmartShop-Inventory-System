<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);
$stmt = $pdo->query('SELECT p.id,p.sku,p.name,p.unit,p.stock_quantity,p.minimum_stock,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND p.stock_quantity <= p.minimum_stock ORDER BY (p.stock_quantity=0) DESC, p.stock_quantity ASC, p.name ASC');
$products = $stmt->fetchAll();
$zero = 0; $lowOnly = 0; foreach ($products as $p) { if ((float)$p['stock_quantity'] <= 0) $zero++; else $lowOnly++; }
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Stock Alerts | SmartShop
  </title>
  <link href="Assets/css/style.css" rel="stylesheet"/>
  <link href="Assets/css/inventory.css" rel="stylesheet"/>
  <link rel="stylesheet" href="Assets/css/stock-alerts.css">
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
      INVENTORY HEALTH
     </p>
     <h1>
      Stock alerts
     </h1>
     <p class="muted">
      Products at or below their configured minimum stock level.
     </p>
    </div>
    <a class="btn btn-primary" href="products.php">
     Manage products
    </a>
   </div>
   <div class="stats-grid">
    <div class="stat-card">
     <span>
      Products needing restock
     </span>
     <strong>
      <?= count($products) ?>
     </strong>
    </div>
    <div class="stat-card">
     <span>
      Low stock
     </span>
     <strong>
      <?= $lowOnly ?>
     </strong>
    </div>
    <div class="stat-card stat-danger">
     <span>
      Out of stock
     </span>
     <strong>
      <?= $zero ?>
     </strong>
    </div>
   </div>
   <section class="inv-card">
    <div class="card-title">
     <h2>
      Reorder attention
     </h2>
     <span class="muted">
      Based on current database quantities
     </span>
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
         Category
        </th>
        <th>
         Current stock
        </th>
        <th>
         Minimum stock
        </th>
        <th>
         Suggested to reach minimum
        </th>
        <th>
         Action
        </th>
       </tr>
      </thead>
      <tbody>
       <?php if(!$products): ?>
       <tr>
        <td class="empty-state" colspan="7">
         Great! No products are currently below their minimum stock levels.
        </td>
       </tr>
       <?php endif; ?><?php foreach($products as $p): $current=(float)$p['stock_quantity'];$min=(float)$p['minimum_stock']; ?>
       <tr>
        <td>
         <strong>
          <?= e($p['name']) ?>
         </strong>
        </td>
        <td>
         <?= e($p['sku']) ?>
        </td>
        <td>
         <?= e($p['category_name'] ?: 'Uncategorised') ?>
        </td>
        <td>
         <span class="stock-pill <?= $current<=0?'stock-zero':'stock-low' ?>">
          <?= e(rtrim(rtrim(number_format($current,3,'.',''),'0'),'.')) ?> <?= e($p['unit']) ?>
         </span>
        </td>
        <td>
         <?= e(rtrim(rtrim(number_format($min,3,'.',''),'0'),'.')) ?> <?= e($p['unit']) ?>
        </td>
        <td>
         <?= e(rtrim(rtrim(number_format(max(0,$min-$current),3,'.',''),'0'),'.')) ?> <?= e($p['unit']) ?>
        </td>
        <td>
         <a href="product_form.php?id=<?= (int)$p['id'] ?>">
          View product
         </a>
        </td>
       </tr>
       <?php endforeach; ?>
      </tbody>
     </table>
    </div>
   </section>
  </main>
 </body>
</html>
