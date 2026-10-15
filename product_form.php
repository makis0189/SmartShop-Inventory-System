<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$product = ['id'=>null,'sku'=>'','name'=>'','category_id'=>'','supplier_id'=>'','unit'=>'piece','buying_price'=>'','selling_price'=>'','stock_quantity'=>'0','minimum_stock'=>'0','is_active'=>1];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('error', 'Product not found.'); redirect_to('products.php'); }
    $product = $found;
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   <?= $id ? 'Edit' : 'Add' ?> Product | SmartShop
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
  <main class="inv-container inv-form-container">
   <div class="inv-heading">
    <div>
     <p class="eyebrow">
      INVENTORY MANAGEMENT
     </p>
     <h1>
      <?= $id ? 'Edit product' : 'Add a product' ?>
     </h1>
     <p class="muted">
      Enter accurate product details. SKU must be unique.
     </p>
    </div>
    <a class="btn btn-plain" href="products.php">
     ← Back to products
    </a>
   </div>
   <?php if ($flash): ?>
   <div class="alert <?= e($flash['type']) ?>">
    <?= e($flash['message']) ?>
   </div>
   <?php endif; ?>
   <form action="save_product.php" class="inv-card product-form" method="post">
    <?= csrf_input() ?>
    <input name="id" type="hidden" value="<?= e($product['id'] ?? '') ?>"/>
    <div class="form-grid">
     <label>
      Product name *
      <input maxlength="180" name="name" placeholder="e.g. Cement 50kg" required="" value="<?= e($product['name']) ?>"/>
     </label>
     <label>
      SKU / Product code *
      <input maxlength="80" name="sku" placeholder="e.g. CEM-50KG-001" required="" value="<?= e($product['sku']) ?>"/>
     </label>
     <label>
      Category
      <select name="category_id">
       <option value="">
        Choose category
       </option>
       <?php foreach($categories as $c): ?>
       <option value="<?= (int)$c['id'] ?>" <?= (string)($product['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
        <?= e($c['name']) ?>
       </option>
       <?php endforeach; ?>
      </select>
      <small>
       Manage category options from the Categories page.
      </small>
     </label>
     <label>
      Unit *
      <select name="unit" required="">
       <?php foreach(['piece'=>'Piece','bag'=>'Bag','box'=>'Box','kg'=>'Kilogram','litre'=>'Litre','metre'=>'Metre','packet'=>'Packet','roll'=>'Roll','other'=>'Other'] as $val=>$label): ?>
       <option value="<?= e($val) ?>" <?= ($product['unit'] ?? 'piece') === $val ? 'selected' : '' ?>>
        <?= e($label) ?>
       </option>
       <?php endforeach; ?>
      </select>
     </label>
     <label>
      Buying price (TSh) *
      <input min="0" name="buying_price" required="" step="0.01" type="number" value="<?= e($product['buying_price']) ?>"/>
     </label>
     <label>
      Selling price (TSh) *
      <input min="0" name="selling_price" required="" step="0.01" type="number" value="<?= e($product['selling_price']) ?>"/>
     </label>
     <label>
      Current stock *
      <input min="0" name="stock_quantity" <?= $id ? 'readonly' : '' ?> required="" step="0.001" type="number" value="<?= e($product['stock_quantity']) ?>"/>
      <?php if($id): ?>
      <small>
       Stock is read-only here so stock changes can be recorded properly. Stock adjustment is the next improvement.
      </small>
      <?php else: ?>
      <small>
       Opening quantity will be recorded in stock history.
      </small>
      <?php endif; ?>
     </label>
     <label>
      Minimum stock level *
      <input min="0" name="minimum_stock" required="" step="0.001" type="number" value="<?= e($product['minimum_stock']) ?>"/>
      <small>
       Alert appears when stock is at or below this quantity.
      </small>
     </label>
    </div>
    <div class="form-actions">
     <a class="btn btn-plain" href="products.php">
      Cancel
     </a>
     <button class="btn btn-primary" type="submit">
      Save product
     </button>
    </div>
   </form>
  </main>
 </body>
</html>
