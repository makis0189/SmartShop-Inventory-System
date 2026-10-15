<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Security validation failed. Refresh the page and try again.'); redirect_to('purchases.php'); }
    $supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT) ?: null;
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $costs = $_POST['unit_cost'] ?? [];
    $notes = trim($_POST['notes'] ?? '');
    if (!is_array($productIds) || !is_array($quantities) || !is_array($costs) || count($productIds) < 1 || count($productIds) > 100 || count($productIds) !== count($quantities) || count($productIds) !== count($costs) || mb_strlen($notes) > 500) {
        set_flash('error', 'Add at least one valid purchase line and try again.'); redirect_to('purchases.php');
    }
    $lines = []; $valid = true;
    foreach ($productIds as $i => $rawId) {
        $pid = filter_var($rawId, FILTER_VALIDATE_INT);
        $qty = filter_var($quantities[$i] ?? null, FILTER_VALIDATE_FLOAT);
        $cost = filter_var($costs[$i] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$pid || $qty === false || $qty <= 0 || $cost === false || $cost < 0 || !is_finite((float)$qty) || !is_finite((float)$cost)) { $valid = false; break; }
        $lines[] = ['product_id'=>(int)$pid, 'quantity'=>(float)$qty, 'unit_cost'=>(float)$cost];
    }
    if (!$valid || !$lines) { set_flash('error', 'Each purchase line needs a product, positive quantity and non-negative unit cost.'); redirect_to('purchases.php'); }
    try {
        $pdo->beginTransaction();
        if ($supplierId) { $checkSupplier = $pdo->prepare('SELECT id FROM suppliers WHERE id=:id'); $checkSupplier->execute([':id'=>$supplierId]); if (!$checkSupplier->fetch()) { throw new RuntimeException('Choose a valid supplier.'); } }
        $purchaseNumber = 'PO-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $insertPurchase = $pdo->prepare("INSERT INTO purchases (purchase_number, supplier_id, purchased_by, total_amount, status, notes) VALUES (:number, :supplier, :user, 0, 'received', :notes)");
        $insertPurchase->execute([':number'=>$purchaseNumber, ':supplier'=>$supplierId, ':user'=>current_user()['id'], ':notes'=>$notes !== '' ? $notes : null]);
        $purchaseId = (int)$pdo->lastInsertId(); $total = 0.0;
        $getProduct = $pdo->prepare('SELECT id, name, buying_price, stock_quantity, unit FROM products WHERE id=:id AND is_active=1 FOR UPDATE');
        $updateProduct = $pdo->prepare('UPDATE products SET stock_quantity=:after, buying_price=:cost WHERE id=:id');
        $insertItem = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, product_name_snapshot, quantity, unit_cost, line_total) VALUES (:purchase, :product, :name, :quantity, :cost, :line_total)');
        $insertMovement = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity_change, quantity_before, quantity_after, reference_type, reference_id, reason, performed_by) VALUES (:product, 'purchase', :change, :before, :after, 'purchase', :reference, :reason, :user)");
        foreach ($lines as $line) {
            $getProduct->execute([':id'=>$line['product_id']]); $product = $getProduct->fetch();
            if (!$product) { throw new RuntimeException('One selected product is no longer active. Refresh and try again.'); }
            $before = (float)$product['stock_quantity']; $after = $before + $line['quantity']; $lineTotal = round($line['quantity'] * $line['unit_cost'], 2); $total += $lineTotal;
            $updateProduct->execute([':after'=>$after, ':cost'=>$line['unit_cost'], ':id'=>$product['id']]);
            $insertItem->execute([':purchase'=>$purchaseId, ':product'=>$product['id'], ':name'=>$product['name'], ':quantity'=>$line['quantity'], ':cost'=>$line['unit_cost'], ':line_total'=>$lineTotal]);
            $insertMovement->execute([':product'=>$product['id'], ':change'=>$line['quantity'], ':before'=>$before, ':after'=>$after, ':reference'=>$purchaseId, ':reason'=>'Stock received via ' . $purchaseNumber, ':user'=>current_user()['id']]);
        }
        $updatePurchase = $pdo->prepare('UPDATE purchases SET total_amount=:total WHERE id=:id'); $updatePurchase->execute([':total'=>$total, ':id'=>$purchaseId]);
        $pdo->commit(); set_flash('success', 'Purchase ' . $purchaseNumber . ' recorded. Stock quantities have been updated.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof RuntimeException) { set_flash('error', $e->getMessage()); }
        else { error_log('SmartShop purchase processing failed: ' . $e->getMessage()); set_flash('error', 'The purchase could not be saved. No stock changes were committed.'); }
    }
    redirect_to('purchases.php');
}
$products = $pdo->query('SELECT id, sku, name, unit, buying_price, stock_quantity FROM products WHERE is_active=1 ORDER BY name')->fetchAll();
$suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$purchases = $pdo->query('SELECT p.id, p.purchase_number, p.purchase_date, p.total_amount, p.status, s.name AS supplier_name, u.full_name AS buyer_name, COUNT(pi.id) AS line_count FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id LEFT JOIN users u ON u.id=p.purchased_by LEFT JOIN purchase_items pi ON pi.purchase_id=p.id GROUP BY p.id ORDER BY p.purchase_date DESC LIMIT 100')->fetchAll();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn(); $flash = get_flash();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Purchases | SmartShop</title><link rel="stylesheet" href="Assets/css/style.css"><link rel="stylesheet" href="Assets/css/inventory.css"><link rel="stylesheet" href="Assets/fontawesome-free-7.3.1-web/css/all.min.css"><script src="Assets/js/purchase-form.js" defer></script>  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page"><header class="inv-topbar"><a class="inv-brand" href="dashboard.php">SmartShop <span>Inventory</span></a><?php require __DIR__ . '/includes/nav.php'; ?></header>
<main class="inv-container"><div class="inv-heading"><div><p class="eyebrow">PROCUREMENT & RESTOCKING</p><h1>Purchases</h1><p class="muted">Record received stock from suppliers. Saving a purchase updates stock and records each movement atomically.</p></div><a class="btn btn-plain" href="suppliers.php"><i class="fa-solid fa-truck-field" aria-hidden="true"></i> Suppliers</a></div>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<section class="inv-card purchase-entry"><div class="card-title"><h2>Record received purchase</h2><p class="muted">Only submit goods that have actually been received. Stock updates after successful save.</p></div>
<?php if (!$products): ?><div class="dashboard-empty">Add at least one active product before recording a purchase.</div><?php else: ?><form method="post" action="purchases.php" class="purchase-form" id="purchaseForm"><?= csrf_input() ?><div class="purchase-meta"><label>Supplier<select name="supplier_id"><option value="">Not specified</option><?php foreach ($suppliers as $supplier): ?><option value="<?= (int)$supplier['id'] ?>"><?= e($supplier['name']) ?></option><?php endforeach; ?></select><?php if (!$suppliers): ?><small>No suppliers have been added yet. You can still record an unsourced purchase.</small><?php endif; ?></label><label>Notes (optional)<input name="notes" maxlength="500" placeholder="Invoice number or delivery note"></label></div><div class="purchase-lines-head"><h3>Items received</h3><button class="btn btn-secondary" type="button" id="addPurchaseLine"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add item</button></div><div class="table-wrap"><table class="purchase-lines"><thead><tr><th>Product</th><th>Current stock</th><th>Quantity received</th><th>Unit cost (TSh)</th><th></th></tr></thead><tbody id="purchaseLines"><tr class="purchase-line"><td><select name="product_id[]" required><option value="">Choose product</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['id'] ?>" data-stock="<?= e($product['stock_quantity']) ?>" data-cost="<?= e($product['buying_price']) ?>"><?= e($product['name']) ?> (<?= e($product['sku']) ?>)</option><?php endforeach; ?></select></td><td class="purchase-stock">—</td><td><input type="number" name="quantity[]" min="0.001" step="0.001" value="1" required></td><td><input type="number" name="unit_cost[]" min="0" step="0.01" value="0.00" required></td><td><button type="button" class="remove-purchase-line" aria-label="Remove item" title="Remove item">×</button></td></tr></tbody></table></div><div class="purchase-total"><span>Estimated purchase total</span><strong id="purchaseTotalView">TSh 0.00</strong></div><div class="purchase-submit"><p class="muted">The server validates quantities and costs before committing stock changes.</p><button class="btn btn-primary" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> Save purchase & update stock</button></div></form><?php endif; ?></section>
<section class="inv-card purchase-history"><div class="card-title"><h2>Recent purchases</h2><p class="muted">Latest 100 purchase records.</p></div><div class="table-wrap"><table><thead><tr><th>Purchase reference</th><th>Date</th><th>Supplier</th><th>Items</th><th>Recorded by</th><th>Status</th><th>Total</th></tr></thead><tbody><?php if (!$purchases): ?><tr><td colspan="7" class="empty-state">No purchases have been recorded yet.</td></tr><?php endif; ?><?php foreach ($purchases as $purchase): ?><tr><td><strong><?= e($purchase['purchase_number']) ?></strong></td><td><?= e(date('d M Y, H:i', strtotime($purchase['purchase_date']))) ?></td><td><?= e($purchase['supplier_name'] ?: 'Not specified') ?></td><td><?= (int)$purchase['line_count'] ?></td><td><?= e($purchase['buyer_name'] ?: 'Former user') ?></td><td><span class="status-pill <?= $purchase['status'] === 'received' ? 'status-active' : 'status-inactive' ?>"><?= e(ucfirst($purchase['status'])) ?></span></td><td><strong>TSh <?= e(number_format((float)$purchase['total_amount'], 2)) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></section></main></body></html>
