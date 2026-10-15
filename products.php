<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'active';
$where = [];
$params = [];
if ($status === 'all') {
    // No active-status filter.
} else {
    $where[] = 'p.is_active = :active';
    $params[':active'] = ($status === 'inactive') ? 0 : 1;
}
if ($q !== '') {
    $where[] = '(p.name LIKE :q_name OR p.sku LIKE :q_sku OR c.name LIKE :q_category)';
    $searchTerm = '%' . $q . '%';
    $params[':q_name'] = $searchTerm;
    $params[':q_sku'] = $searchTerm;
    $params[':q_category'] = $searchTerm;
}
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$flash = get_flash();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Product Management | SmartShop
  </title>
  <link href="Assets/css/style.css" rel="stylesheet"/>
  <link href="Assets/css/inventory.css" rel="stylesheet"/>
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
      INVENTORY MANAGEMENT
     </p>
     <h1>
      Products
     </h1>
     <p class="muted">
      Manage your products, prices and stock quantities.
     </p>
    </div>
    <a class="btn btn-primary" href="product_form.php">
     <span>
      ＋
     </span>
     Add product
    </a>
   </div>
   <?php if ($flash): ?>
   <div class="alert <?= e($flash['type']) ?>">
    <?= e($flash['message']) ?>
   </div>
   <?php endif; ?>
   <section class="inv-card">
    <form class="filter-row" method="get">
     <label class="search-field">
      <span>
       ⌕
      </span>
      <input name="q" placeholder="Search product name, SKU or category" value="<?= e($q) ?>"/>
     </label>
     <select aria-label="Product status" name="status">
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
       Active products
      </option>
      <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>
       Inactive products
      </option>
      <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
       All products
      </option>
     </select>
     <button class="btn btn-secondary" type="submit">
      Search
     </button>
     <a class="btn btn-plain" href="products.php">
      Reset
     </a>
    </form>
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
         Buying price
        </th>
        <th>
         Selling price
        </th>
        <th>
         Stock
        </th>
        <th>
         Status
        </th>
        <th>
         Actions
        </th>
       </tr>
      </thead>
      <tbody>
       <?php if (!$products): ?>
       <tr>
        <td class="empty-state" colspan="8">
         No products found. Add your first product to get started.
        </td>
       </tr>
       <?php endif; ?>
    <?php foreach ($products as $p): $low = (float)$p['stock_quantity'] <= (float)$p['minimum_stock']; ?>
       <tr>
        <td>
         <strong>
          <?= e($p['name']) ?>
         </strong>
         <small>
          <?= e($p['unit']) ?>
         </small>
        </td>
        <td>
         <?= e($p['sku']) ?>
        </td>
        <td>
         <?= e($p['category_name'] ?: 'Uncategorised') ?>
        </td>
        <td>
         TSh <?= number_format((float)$p['buying_price'], 2) ?>
        </td>
        <td>
         TSh <?= number_format((float)$p['selling_price'], 2) ?>
        </td>
        <td>
         <span class="stock-pill <?= $low ? 'stock-low' : 'stock-ok' ?>">
          <?= e(rtrim(rtrim(number_format((float)$p['stock_quantity'], 3, '.', ''), '0'), '.')) ?> <?= e($p['unit']) ?>
         </span>
         <?php if($low): ?>
         <small class="low-note">
          At/below minimum
         </small>
         <?php endif; ?>
        </td>
        <td>
         <span class="status-pill <?= $p['is_active'] ? 'status-active' : 'status-inactive' ?>">
          <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
         </span>
        </td>
        <td>
         <div class="actions">
          <a href="product_form.php?id=<?= (int)$p['id'] ?>">
           Edit
          </a>
          <form action="toggle_product.php" method="post" onsubmit="return confirm('<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?> this product?');">
           <?= csrf_input() ?>
           <input name="id" type="hidden" value="<?= (int)$p['id'] ?>"/>
           <input name="action" type="hidden" value="<?= $p['is_active'] ? 'deactivate' : 'activate' ?>"/>
           <button class="text-action" type="submit">
            <?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>
           </button>
          </form>
         </div>
        </td>
       </tr>
       <?php endforeach; ?>
      </tbody>
     </table>
    </div>
    <div class="table-footer">
     Showing <?= count($products) ?> product(s)
    </div>
   </section>
  </main>
 </body>
</html>
