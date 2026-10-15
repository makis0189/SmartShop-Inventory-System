<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Security validation failed. Refresh and try again.'); redirect_to('suppliers.php'); }
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '' || mb_strlen($name) > 150 || mb_strlen($phone) > 30 || mb_strlen($address) > 255 || ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150))) {
        set_flash('error', 'Enter a supplier name and valid contact details.'); redirect_to('suppliers.php');
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (:name, :phone, :email, :address)');
        $stmt->execute([':name'=>$name, ':phone'=>$phone !== '' ? $phone : null, ':email'=>$email !== '' ? $email : null, ':address'=>$address !== '' ? $address : null]);
        set_flash('success', 'Supplier added successfully.');
    } catch (PDOException $e) {
        error_log('SmartShop supplier create failed: ' . $e->getMessage());
        set_flash('error', 'The supplier could not be saved. Check the details and try again.');
    }
    redirect_to('suppliers.php');
}
$suppliers = $pdo->query('SELECT s.id, s.name, s.phone, s.email, s.address, s.created_at, COUNT(p.id) AS product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id GROUP BY s.id ORDER BY s.name ASC')->fetchAll();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn();
$flash = get_flash();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Suppliers | SmartShop</title><link rel="stylesheet" href="Assets/css/style.css"><link rel="stylesheet" href="Assets/css/inventory.css"><link rel="stylesheet" href="Assets/fontawesome-free-7.3.1-web/css/all.min.css">  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page"><header class="inv-topbar"><a class="inv-brand" href="dashboard.php">SmartShop <span>Inventory</span></a><?php require __DIR__ . '/includes/nav.php'; ?></header>
<main class="inv-container"><div class="inv-heading"><div><p class="eyebrow">SUPPLIER DIRECTORY</p><h1>Suppliers</h1><p class="muted">Maintain trusted supplier contacts for purchasing and replenishment.</p></div><a class="btn btn-primary" href="purchases.php"><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i> Record purchase</a></div>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="supplier-layout"><section class="inv-card supplier-form-card"><div class="card-title"><h2>Add supplier</h2><p class="muted">Contact information is optional except for the supplier name.</p></div><form method="post" action="suppliers.php" class="supplier-form"><?= csrf_input() ?><label>Supplier name *<input name="name" maxlength="150" required placeholder="Company or supplier name"></label><label>Phone<input name="phone" maxlength="30" type="tel" placeholder="+255 ..."></label><label>Email<input name="email" maxlength="150" type="email" placeholder="supplier@example.com"></label><label>Address<input name="address" maxlength="255" placeholder="Town, street or postal address"></label><button class="btn btn-primary" type="submit">Save supplier</button></form></section>
<section class="inv-card"><div class="card-title"><h2>Supplier directory <span class="muted">(<?= count($suppliers) ?>)</span></h2></div><div class="table-wrap"><table><thead><tr><th>Supplier</th><th>Contact</th><th>Address</th><th>Linked products</th><th>Added</th></tr></thead><tbody><?php if (!$suppliers): ?><tr><td colspan="5" class="empty-state">No suppliers yet. Add your first supplier using the form.</td></tr><?php endif; ?><?php foreach ($suppliers as $supplier): ?><tr><td><strong><?= e($supplier['name']) ?></strong><small><?= e($supplier['email'] ?: 'No email provided') ?></small></td><td><?= e($supplier['phone'] ?: '—') ?></td><td><?= e($supplier['address'] ?: '—') ?></td><td><?= number_format((int)$supplier['product_count']) ?></td><td><?= e(date('d M Y', strtotime($supplier['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div></section></div></main></body></html>
