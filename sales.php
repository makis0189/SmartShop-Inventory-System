<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require_role(['admin', 'manager', 'cashier']);
$flash = get_flash();
$products = $pdo->query("SELECT id, sku, name, unit, selling_price, stock_quantity FROM products WHERE is_active=1 AND stock_quantity > 0 ORDER BY name")->fetchAll();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= minimum_stock')->fetchColumn();
$from = trim($_GET['from'] ?? ''); $to = trim($_GET['to'] ?? '');
$sql = "SELECT s.id,s.sale_number,s.sale_date,s.total_amount,s.payment_method,s.status,COUNT(si.id) AS item_count FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id";
$where=[]; $params=[];
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) { $where[]='s.sale_date >= :from_date'; $params[':from_date']=$from.' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) { $where[]='s.sale_date < DATE_ADD(:to_date, INTERVAL 1 DAY)'; $params[':to_date']=$to; }
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 100';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $sales=$stmt->fetchAll();
$todayTotal=(float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE status='completed' AND sale_date >= CURDATE() AND sale_date < CURDATE()+INTERVAL 1 DAY")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Sales Management | SmartShop
  </title>
  <link href="Assets/css/style.css" rel="stylesheet"/>
  <link href="Assets/css/inventory.css" rel="stylesheet"/>
  <link rel="stylesheet" href="Assets/css/sales.css">
   <link rel="stylesheet" href="Assets/css/enterprise-overrides.css">
</head>
 <body class="inventory-page">
  <header class="inv-topbar">
   <?php if ((current_user()['role'] ?? '') === 'cashier'): ?><a class="inv-brand" href="sales.php"><?php else: ?><a class="inv-brand" href="dashboard.php"><?php endif; ?>
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
      SALES MANAGEMENT
     </p>
     <h1>
      Sales
     </h1>
     <p class="muted">
      Record sales, update stock automatically and print receipts.
     </p>
    </div>
   </div>
   <?php if($flash): ?>
   <div class="alert <?= e($flash['type']) ?>">
    <?= e($flash['message']) ?>
   </div>
   <?php endif; ?>
   <div class="stats-grid">
    <div class="stat-card">
     <span>
      Today's completed sales
     </span>
     <strong>
      <?= e(number_format($todayTotal,2)) ?>
      <small>
       TSh
      </small>
     </strong>
    </div>
    <div class="stat-card">
     <span>
      Recent transactions shown
     </span>
     <strong>
      <?= count($sales) ?>
     </strong>
    </div>
   </div>
   <div class="sale-layout">
    <section class="inv-card">
     <div class="card-title">
      <div>
       <h2>
        Record a new sale
       </h2>
       <p class="muted">
        Choose products and quantities below.
       </p>
      </div>
     </div>
     <form action="process_sale.php" class="sale-panel" id="saleForm" method="post">
      <input name="csrf_token" type="hidden" value="<?= e(csrf_token()) ?>"/>
      <div class="sale-toolbar">
       <strong>
        Sale items
       </strong>
       <button class="btn btn-secondary" id="addLine" type="button">
        ＋ Add product
       </button>
      </div>
      <?php if(!$products): ?>
      <div class="alert error">
       No active products with available stock. Add products or update stock before recording a sale.
      </div>
      <?php else: ?>
      <div class="table-wrap">
       <table class="sale-lines">
        <thead>
         <tr>
          <th class="sale-product-heading">
           Product
          </th>
          <th>
           Qty
          </th>
          <th>
           In stock
          </th>
          <th>
          </th>
         </tr>
        </thead>
        <tbody id="saleLines">
         <tr class="sale-line">
          <td>
           <select class="product-select" name="product_id[]" required="">
            <option value="">
             Choose product
            </option>
            <?php foreach($products as $p): ?>
            <option data-price="<?= e($p['selling_price']) ?>" data-stock="<?= e($p['stock_quantity']) ?>" value="<?= (int)$p['id'] ?>">
             <?= e($p['name']) ?> (<?= e($p['sku']) ?>) — <?= e(number_format((float)$p['selling_price'],2)) ?> TSh
            </option>
            <?php endforeach; ?>
           </select>
          </td>
          <td>
           <input class="qty" min="0.001" name="quantity[]" required="" step="0.001" type="number" value="1"/>
          </td>
          <td class="stock-cell">
           —
          </td>
          <td>
           <button aria-label="Remove row" class="remove" title="Remove row" type="button">
            ×
           </button>
          </td>
         </tr>
        </tbody>
       </table>
      </div>
      <div class="sale-fields sale-fields--spaced">
       <label>
        Discount (TSh)
        <input id="discount" min="0" name="discount" required="" step="0.01" type="number" value="0"/>
        <small class="sale-note">
         Discount cannot exceed the subtotal. Product prices are read from the database.
        </small>
       </label>
       <label>
        Payment method
        <select name="payment_method" required="">
         <option value="cash">
          Cash
         </option>
         <option value="mobile_money">
          Mobile money
         </option>
         <option value="bank">
          Bank
         </option>
         <option value="credit">
          Credit
         </option>
         <option value="other">
          Other
         </option>
        </select>
       </label>
       <label>
        Notes (optional)
        <input maxlength="500" name="notes" placeholder="Customer or transaction notes" type="text"/>
       </label>
      </div>
      <div class="sale-total">
       <span>
        Estimated subtotal
       </span>
       <strong id="subtotalView">
        TSh 0.00
       </strong>
      </div>
      <div class="sale-total final">
       <span>
        Estimated total
       </span>
       <strong id="totalView">
        TSh 0.00
       </strong>
      </div>
      <p class="sale-note">
       The estimate is for convenience only. The server re-checks prices and available stock before saving.
      </p>
      <div class="sale-actions">
       <button class="btn btn-primary" type="submit">
        Complete sale
       </button>
      </div>
      <?php endif; ?>
     </form>
    </section>
    <section class="inv-card">
     <div class="card-title">
      <div>
       <h2>
        Recent sales
       </h2>
       <p class="muted">
        Open a transaction to view or print its receipt.
       </p>
      </div>
     </div>
     <form class="filter-row" method="get">
      <label class="search-field sale-date-field">
       <span>
        From
       </span>
       <input aria-label="From date" name="from" type="date" value="<?= e($from) ?>"/>
      </label>
      <label class="search-field sale-date-field">
       <span>
        To
       </span>
       <input aria-label="To date" name="to" type="date" value="<?= e($to) ?>"/>
      </label>
      <button class="btn btn-secondary" type="submit">
       Filter
      </button>
     </form>
     <div class="table-wrap">
      <table>
       <thead>
        <tr>
         <th>
          Sale
         </th>
         <th>
          Date
         </th>
         <th>
          Total
         </th>
         <th>
          Receipt
         </th>
        </tr>
       </thead>
       <tbody>
        <?php if(!$sales): ?>
        <tr>
         <td class="empty-state" colspan="4">
          No sales recorded yet.
         </td>
        </tr>
        <?php else: foreach($sales as $s): ?>
        <tr>
         <td>
          <strong>
           <?= e($s['sale_number']) ?>
          </strong>
          <small>
           <?= (int)$s['item_count'] ?> item line(s)
          </small>
         </td>
         <td>
          <?= e(date('d M Y, H:i',strtotime($s['sale_date']))) ?>
         </td>
         <td>
          <?= e(number_format((float)$s['total_amount'],2)) ?> TSh
         </td>
         <td>
          <a href="receipt.php?id=<?= (int)$s['id'] ?>">
           View receipt
          </a>
         </td>
        </tr>
        <?php endforeach; endif; ?>
       </tbody>
      </table>
     </div>
     <div class="table-footer">
      Showing up to 100 transactions.
     </div>
    </section>
   </div>
  </main>
  <script>
    (function () {
        const products = <?= json_encode(
            array_map(
                fn($p) => [
                    'id' => (int) $p['id'],
                    'sku' => $p['sku'],
                    'name' => $p['name'],
                    'unit' => $p['unit'],
                    'price' => (float) $p['selling_price'],
                    'stock' => (float) $p['stock_quantity'],
                ],
                $products
            ),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const tbody = document.getElementById('saleLines');
        if (!tbody) {
            return;
        }

        const addButton = document.getElementById('addLine');
        const discountInput = document.getElementById('discount');
        const saleForm = document.getElementById('saleForm');

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function optionHtml() {
            const options = products.map((product) => {
                const price = product.price.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                return `
                    <option
                        value="${product.id}"
                        data-stock="${product.stock}"
                        data-price="${product.price}"
                    >
                        ${escapeHtml(product.name)} (${escapeHtml(product.sku)}) — ${price} TSh
                    </option>
                `;
            });

            return '<option value="">Choose product</option>' + options.join('');
        }

        function updateTotals() {
            let subtotal = 0;

            tbody.querySelectorAll('tr').forEach((row) => {
                const select = row.querySelector('select');
                const quantityInput = row.querySelector('.qty');
                const selectedOption = select.options[select.selectedIndex];
                const stockCell = row.querySelector('.stock-cell');

                if (select.value) {
                    stockCell.textContent = `${selectedOption.dataset.stock} available`;
                    subtotal +=
                        (Number(selectedOption.dataset.price) || 0) *
                        (Number(quantityInput.value) || 0);
                } else {
                    stockCell.textContent = '—';
                }
            });

            const discount = Math.max(0, Number(discountInput.value) || 0);
            const total = Math.max(0, subtotal - discount);
            const moneyFormat = {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            };

            document.getElementById('subtotalView').textContent =
                `TSh ${subtotal.toLocaleString(undefined, moneyFormat)}`;
            document.getElementById('totalView').textContent =
                `TSh ${total.toLocaleString(undefined, moneyFormat)}`;
        }

        function addRow() {
            const row = document.createElement('tr');
            row.className = 'sale-line';
            row.innerHTML = `
                <td>
                    <select name="product_id[]" required class="product-select">
                        ${optionHtml()}
                    </select>
                </td>
                <td>
                    <input
                        class="qty"
                        type="number"
                        name="quantity[]"
                        min="0.001"
                        step="0.001"
                        value="1"
                        required
                    >
                </td>
                <td class="stock-cell">—</td>
                <td>
                    <button
                        type="button"
                        class="remove"
                        title="Remove row"
                        aria-label="Remove row"
                    >×</button>
                </td>
            `;

            tbody.appendChild(row);
            updateTotals();
        }

        addButton.addEventListener('click', addRow);
        tbody.addEventListener('change', updateTotals);
        tbody.addEventListener('input', updateTotals);

        tbody.addEventListener('click', (event) => {
            if (!event.target.closest('.remove')) {
                return;
            }

            const rows = tbody.querySelectorAll('tr');

            if (rows.length > 1) {
                event.target.closest('tr').remove();
            } else {
                rows[0].querySelector('select').value = '';
                rows[0].querySelector('.qty').value = '1';
            }

            updateTotals();
        });

        discountInput.addEventListener('input', updateTotals);

        saleForm.addEventListener('submit', (event) => {
            const rows = [...tbody.querySelectorAll('tr')];
            const hasSelectedProduct = rows.some(
                (row) => row.querySelector('select').value
            );

            if (!hasSelectedProduct) {
                event.preventDefault();
                alert('Please choose at least one product.');
            }
        });

        updateTotals();
    })();
</script>
 </body>
</html>
