<?php

require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('sales.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Security validation failed. Refresh the page and try again.');
    redirect_to('sales.php');
}

$productIds = $_POST['product_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$discount = filter_var($_POST['discount'] ?? 0, FILTER_VALIDATE_FLOAT);
$payment = $_POST['payment_method'] ?? 'cash';
$notes = trim($_POST['notes'] ?? '');

$allowedPayments = ['cash', 'mobile_money', 'bank', 'credit', 'other'];

if (
    !is_array($productIds)
    || !is_array($quantities)
    || count($productIds) !== count($quantities)
    || !$productIds
    || $discount === false
    || $discount < 0
    || !in_array($payment, $allowedPayments, true)
    || mb_strlen($notes) > 500
) {
    set_flash('error', 'Please check the sale form values and try again.');
    redirect_to('sales.php');
}

// Aggregate duplicate product lines before locking stock rows.
$requested = [];

foreach ($productIds as $index => $rawId) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_FLOAT);

    if (
        !$id
        || $quantity === false
        || $quantity <= 0
        || !is_finite((float) $quantity)
    ) {
        set_flash('error', 'Each selected product must have a valid positive quantity.');
        redirect_to('sales.php');
    }

    $requested[$id] = ($requested[$id] ?? 0) + (float) $quantity;
}

ksort($requested, SORT_NUMERIC);

try {
    $pdo->beginTransaction();

    $lockedProducts = [];
    $lockProduct = $pdo->prepare(
        'SELECT id, sku, name, unit, buying_price, selling_price, stock_quantity
         FROM products
         WHERE id = :id AND is_active = 1
         FOR UPDATE'
    );

    foreach ($requested as $id => $quantity) {
        $lockProduct->execute([':id' => $id]);
        $product = $lockProduct->fetch();

        if (!$product) {
            throw new RuntimeException('One of the selected products is no longer available.');
        }

        if ((float) $product['stock_quantity'] + 0.0000001 < $quantity) {
            throw new RuntimeException(
                'Insufficient stock for ' . $product['name']
                . '. Available: ' . $product['stock_quantity'] . ' '
                . $product['unit'] . '.'
            );
        }

        $lockedProducts[$id] = $product;
    }

    $subtotal = 0.0;

    foreach ($requested as $id => $quantity) {
        $subtotal += (float) $lockedProducts[$id]['selling_price'] * $quantity;
    }

    $subtotal = round($subtotal, 2);
    $discount = round((float) $discount, 2);

    if ($discount > $subtotal) {
        throw new RuntimeException('Discount cannot be greater than the sale subtotal.');
    }

    $total = round($subtotal - $discount, 2);
    $saleNumber = 'SS-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $insertSale = $pdo->prepare(
        "INSERT INTO sales
            (sale_number, sold_by, subtotal, discount, total_amount, payment_method, status, notes)
         VALUES
            (:number, :sold_by, :subtotal, :discount, :total, :payment, 'completed', :notes)"
    );

    $insertSale->execute([
        ':number' => $saleNumber,
        ':sold_by' => (int) current_user()['id'],
        ':subtotal' => $subtotal,
        ':discount' => $discount,
        ':total' => $total,
        ':payment' => $payment,
        ':notes' => $notes !== '' ? $notes : null,
    ]);

    $saleId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        'INSERT INTO sale_items
            (sale_id, product_id, product_name_snapshot, sku_snapshot, quantity, unit_price, unit_cost, line_total)
         VALUES
            (:sale_id, :product_id, :name, :sku, :quantity, :price, :cost, :line_total)'
    );

    $updateStock = $pdo->prepare(
        'UPDATE products SET stock_quantity = :after WHERE id = :id'
    );

    $insertMovement = $pdo->prepare(
        "INSERT INTO stock_movements
            (product_id, movement_type, quantity_change, quantity_before, quantity_after,
             reference_type, reference_id, reason, performed_by)
         VALUES
            (:product_id, 'sale', :change_qty, :before_qty, :after_qty,
             'sale', :sale_id, 'Stock deducted for completed sale', :performed_by)"
    );

    foreach ($requested as $id => $quantity) {
        $product = $lockedProducts[$id];
        $before = (float) $product['stock_quantity'];
        $after = round($before - $quantity, 3);
        $price = (float) $product['selling_price'];
        $cost = (float) $product['buying_price'];
        $lineTotal = round($price * $quantity, 2);

        $insertItem->execute([
            ':sale_id' => $saleId,
            ':product_id' => $id,
            ':name' => $product['name'],
            ':sku' => $product['sku'],
            ':quantity' => $quantity,
            ':price' => $price,
            ':cost' => $cost,
            ':line_total' => $lineTotal,
        ]);

        $updateStock->execute([
            ':after' => $after,
            ':id' => $id,
        ]);

        $insertMovement->execute([
            ':product_id' => $id,
            ':change_qty' => -$quantity,
            ':before_qty' => $before,
            ':after_qty' => $after,
            ':sale_id' => $saleId,
            ':performed_by' => (int) current_user()['id'],
        ]);
    }

    $pdo->commit();

    set_flash('success', 'Sale recorded successfully. Stock has been updated.');
    redirect_to('receipt.php?id=' . $saleId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('SmartShop sale error: ' . $e->getMessage());

    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Unable to save the sale because of a system or database error. No stock was changed.';

    set_flash('error', $message);
    redirect_to('sales.php');
}
