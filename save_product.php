<?php

require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('products.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Security validation failed. Refresh the form and try again.');
    redirect_to('products.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$name = trim($_POST['name'] ?? '');
$sku = strtoupper(trim($_POST['sku'] ?? ''));
$categoryRaw = trim($_POST['category_id'] ?? '');
$categoryId = $categoryRaw === '' ? null : filter_var($categoryRaw, FILTER_VALIDATE_INT);
$unit = trim($_POST['unit'] ?? 'piece');

$allowedUnits = ['piece', 'bag', 'box', 'kg', 'litre', 'metre', 'packet', 'roll', 'other'];

$buying = filter_var($_POST['buying_price'] ?? null, FILTER_VALIDATE_FLOAT);
$selling = filter_var($_POST['selling_price'] ?? null, FILTER_VALIDATE_FLOAT);
$minimum = filter_var($_POST['minimum_stock'] ?? null, FILTER_VALIDATE_FLOAT);
$stock = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_FLOAT);

$invalidInput = (
    $name === ''
    || $sku === ''
    || !in_array($unit, $allowedUnits, true)
    || $buying === false
    || $selling === false
    || $minimum === false
    || $stock === false
    || $buying < 0
    || $selling < 0
    || $minimum < 0
    || $stock < 0
    || ($categoryRaw !== '' && $categoryId === false)
);

if ($invalidInput) {
    set_flash('error', 'Please fill in all required fields with valid, non-negative values.');
    redirect_to($id ? 'product_form.php?id=' . $id : 'product_form.php');
}

try {
    if ($categoryId !== null) {
        $checkCategory = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $checkCategory->execute([$categoryId]);

        if (!$checkCategory->fetch()) {
            throw new RuntimeException('Selected category does not exist.');
        }
    }

    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE products
             SET sku = ?, name = ?, category_id = ?, unit = ?, buying_price = ?,
                 selling_price = ?, minimum_stock = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $sku,
            $name,
            $categoryId,
            $unit,
            $buying,
            $selling,
            $minimum,
            $id,
        ]);

        set_flash('success', 'Product updated successfully.');
    } else {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO products
                (sku, name, category_id, unit, buying_price, selling_price, stock_quantity, minimum_stock)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $sku,
            $name,
            $categoryId,
            $unit,
            $buying,
            $selling,
            $stock,
            $minimum,
        ]);

        $productId = (int) $pdo->lastInsertId();

        if ($stock > 0) {
            $movement = $pdo->prepare(
                "INSERT INTO stock_movements
                    (product_id, movement_type, quantity_change, quantity_before, quantity_after,
                     reference_type, reason)
                 VALUES (?, 'opening', ?, 0, ?, 'product', 'Opening stock when product was created')"
            );

            $movement->execute([$productId, $stock, $stock]);
        }

        $pdo->commit();
        set_flash('success', 'Product added successfully.');
    }

    redirect_to('products.php');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('SmartShop save product failed: ' . $e->getMessage());

    $message = $e->getCode() === '23000'
        ? 'That SKU already exists. Use a unique SKU.'
        : 'Could not save product. Please check the database and try again.';

    set_flash('error', $message);
    redirect_to($id ? 'product_form.php?id=' . $id : 'product_form.php');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', $e->getMessage());
    redirect_to($id ? 'product_form.php?id=' . $id : 'product_form.php');
}
