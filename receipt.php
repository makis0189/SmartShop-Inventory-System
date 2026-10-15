<?php
require __DIR__ . '/config/db.php';require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager', 'cashier']);
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){http_response_code(400);exit('Invalid receipt reference.');}
$stmt=$pdo->prepare('SELECT * FROM sales WHERE id=:id');$stmt->execute([':id'=>$id]);$sale=$stmt->fetch();if(!$sale){http_response_code(404);exit('Sale not found.');}
$stmt=$pdo->prepare('SELECT product_name_snapshot,sku_snapshot,quantity,unit_price,unit_cost,line_total FROM sale_items WHERE sale_id=:id ORDER BY id');$stmt->execute([':id'=>$id]);$items=$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width,initial-scale=1" name="viewport"/>
  <title>
   Receipt <?= e($sale['sale_number']) ?> | SmartShop
  </title>
  <link href="Assets/css/inventory.css" rel="stylesheet"/>
  <link rel="stylesheet" href="Assets/css/receipt.css">
   <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
 <body class="inventory-page">
  <main class="receipt">
   <div class="receipt-head">
    <div>
     <h1>
      Smart
      <span class="receipt-brand-accent">
       Shop
      </span>
     </h1>
     <p>
      Sales receipt
     </p>
     <p>
      Thank you for shopping with us.
     </p>
    </div>
    <div class="receipt-reference">
     <strong>
      <?= e($sale['sale_number']) ?>
     </strong>
     <p>
      <?= e(date('d M Y, H:i',strtotime($sale['sale_date']))) ?>
     </p>
     <p>
      <?= e(strtoupper(str_replace('_',' ',$sale['payment_method']))) ?>
     </p>
    </div>
   </div>
   <div class="receipt-meta">
    <div>
     <span>
      Transaction status
     </span>
     <strong>
      <?= e(ucfirst($sale['status'])) ?>
     </strong>
    </div>
    <div>
     <span>
      Number of product lines
     </span>
     <strong>
      <?= count($items) ?>
     </strong>
    </div>
   </div>
   <div class="table-wrap">
    <table>
     <thead>
      <tr>
       <th>
        Product
       </th>
       <th>
        Qty
       </th>
       <th>
        Price
       </th>
       <th>
        Total
       </th>
      </tr>
     </thead>
     <tbody>
      <?php foreach($items as $item): ?>
      <tr>
       <td>
        <strong>
         <?= e($item['product_name_snapshot']) ?>
        </strong>
        <small>
         <?= e($item['sku_snapshot']) ?>
        </small>
       </td>
       <td>
        <?= e(rtrim(rtrim(number_format((float)$item['quantity'],3,'.',''),'0'),'.')) ?>
       </td>
       <td>
        <?= e(number_format((float)$item['unit_price'],2)) ?>
       </td>
       <td>
        <?= e(number_format((float)$item['line_total'],2)) ?>
       </td>
      </tr>
      <?php endforeach; ?>
     </tbody>
    </table>
   </div>
   <div class="receipt-totals">
    <div>
     <span>
      Subtotal
     </span>
     <strong>
      TSh <?= e(number_format((float)$sale['subtotal'],2)) ?>
     </strong>
    </div>
    <div>
     <span>
      Discount
     </span>
     <strong>
      − TSh <?= e(number_format((float)$sale['discount'],2)) ?>
     </strong>
    </div>
    <div class="grand">
     <span>
      Total paid / due
     </span>
     <strong>
      TSh <?= e(number_format((float)$sale['total_amount'],2)) ?>
     </strong>
    </div>
   </div>
   <?php if($sale['notes']): ?>
   <p class="receipt-notes">
    <strong>
     Notes:
    </strong>
    <?= e($sale['notes']) ?>
   </p>
   <?php endif; ?>
   <div class="receipt-actions">
    <a class="btn btn-plain" href="sales.php">
     ← Back to sales
    </a>
    <button class="btn btn-primary" onclick="window.print()">
     Print receipt
    </button>
   </div>
  </main>
 </body>
</html>
