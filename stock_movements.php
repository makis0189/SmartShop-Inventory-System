<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

$q = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :name_q OR p.sku LIKE :sku_q OR sm.reason LIKE :reason_q OR sm.reference_type LIKE :ref_q)';
    $term = '%' . $q . '%';
    $params[':name_q'] = $term; $params[':sku_q'] = $term; $params[':reason_q'] = $term; $params[':ref_q'] = $term;
}
$allowedTypes = ['opening','purchase','sale','adjustment_in','adjustment_out','return_in','return_out'];
if (in_array($type, $allowedTypes, true)) { $where[] = 'sm.movement_type = :movement_type'; $params[':movement_type'] = $type; }
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'sm.created_at >= :from_date'; $params[':from_date'] = $from . ' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where[] = 'sm.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)'; $params[':to_date'] = $to; }
$sql = 'SELECT sm.id, sm.movement_type, sm.quantity_change, sm.quantity_before, sm.quantity_after, sm.reference_type, sm.reference_id, sm.reason, sm.created_at, p.name AS product_name, p.sku, u.full_name AS performed_by_name FROM stock_movements sm INNER JOIN products p ON p.id=sm.product_id LEFT JOIN users u ON u.id=sm.performed_by';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY sm.created_at DESC, sm.id DESC LIMIT 250';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $movements = $stmt->fetchAll();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn();
$flash = get_flash();
function movement_quantity($n) { return ($n > 0 ? '+' : '') . rtrim(rtrim(number_format((float)$n, 3, '.', ''), '0'), '.'); }
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Stock History | SmartShop</title><link rel="stylesheet" href="Assets/css/style.css"><link rel="stylesheet" href="Assets/css/inventory.css"><link rel="stylesheet" href="Assets/fontawesome-free-7.3.1-web/css/all.min.css">  <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
<body class="inventory-page">
<header class="inv-topbar"><a class="inv-brand" href="dashboard.php">SmartShop <span>Inventory</span></a><?php require __DIR__ . '/includes/nav.php'; ?></header>
<main class="inv-container">
<div class="inv-heading"><div><p class="eyebrow">INVENTORY AUDIT TRAIL</p><h1>Stock history</h1><p class="muted">Review recorded stock movements and trace quantity changes. Latest 250 matching records are shown.</p></div><a class="btn btn-primary" href="products.php"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> View products</a></div>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<section class="inv-card"><form class="movement-filter" method="get" action="stock_movements.php"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search product, SKU, reason" aria-label="Search stock movements"><select name="type" aria-label="Movement type"><option value="">All movement types</option><?php foreach ($allowedTypes as $option): ?><option value="<?= e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$option))) ?></option><?php endforeach; ?></select><label>From <input type="date" name="from" value="<?= e($from) ?>"></label><label>To <input type="date" name="to" value="<?= e($to) ?>"></label><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-plain" href="stock_movements.php">Reset</a></form>
<div class="table-wrap"><table><thead><tr><th>Date / time</th><th>Product</th><th>SKU</th><th>Movement</th><th>Change</th><th>Before</th><th>After</th><th>Reference</th><th>Reason</th><th>Performed by</th></tr></thead><tbody><?php if (!$movements): ?><tr><td class="empty-state" colspan="10">No stock movements match these filters.</td></tr><?php endif; ?><?php foreach ($movements as $m): ?><tr><td><?= e(date('d M Y, H:i', strtotime($m['created_at']))) ?></td><td><strong><?= e($m['product_name']) ?></strong></td><td><?= e($m['sku']) ?></td><td><span class="movement-type <?= e($m['movement_type']) ?>"><?= e(str_replace('_',' ',$m['movement_type'])) ?></span></td><td><strong><?= e(movement_quantity($m['quantity_change'])) ?></strong></td><td><?= e(movement_quantity($m['quantity_before'])) ?></td><td><?= e(movement_quantity($m['quantity_after'])) ?></td><td><?= e($m['reference_type'] ? $m['reference_type'] . ($m['reference_id'] ? ' #' . $m['reference_id'] : '') : '—') ?></td><td><?= e($m['reason'] ?: '—') ?></td><td><?= e($m['performed_by_name'] ?: 'System / former user') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<p class="muted dashboard-footnote">This page is read-only. Correct stock through approved workflows so that the movement ledger remains traceable.</p>
</main></body></html>
