<?php
session_start();   // ← اضافه کنید
include 'includes/db.php';
include 'includes/functions.php';


$invoice_id = intval($_GET['invoice_id']);
// نقش‌محور: فروشنده فقط فاکتور خودش / حسابدار فقط شعبه‌اش / مدیر همه‌جا
$extra = '';
$params = [$invoice_id];
if ($_SESSION['role'] === 'فروشنده') {
    $extra = ' AND invoices.user_id = ?';
    $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'حسابدار') {
    $extra = ' AND invoices.branch_id = ?';
    $params[] = $_SESSION['branch_id'];
}

$invoice = $pdo->prepare("SELECT invoices.*, customers.full_name, customers.mobile 
                          FROM invoices
                          JOIN customers ON invoices.customer_id = customers.id
                          WHERE invoices.id = ? $extra");
$invoice->execute($params);
$invoice = $invoice->fetch();
if (!$invoice) die('❌ فاکتور یافت نشد یا دسترسی غیرمجاز است.');

$invoice_id = intval($_GET['invoice_id']);

$invoice = $pdo->prepare("SELECT invoices.*, customers.full_name, customers.mobile 
                          FROM invoices 
                          JOIN customers ON invoices.customer_id = customers.id 
                          WHERE invoices.id = ?");
$invoice->execute([$invoice_id]);
$invoice = $invoice->fetch();

$items = $pdo->prepare("SELECT invoice_items.*, products.name 
                        FROM invoice_items 
                        JOIN products ON invoice_items.product_id = products.id 
                        WHERE invoice_items.invoice_id = ?");
$items->execute([$invoice_id]);
$items = $items->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>فاکتور فروش</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma; font-size: 12px; width: 80mm; margin: 0 auto; }
        .center { text-align: center; }
        .right { text-align: right; }
        .border-top { border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="center">
        <h4>فروشگاه زنجیره‌ای</h4>
        <p>شماره فاکتور: <?= $invoice_id ?></p>
        <p>تاریخ: <?= mds_date('Y/m/d H:i', strtotime($invoice['created_at'])) ?></p>
        <p>مشتری: <?= htmlspecialchars($invoice['full_name']) ?> (<?= htmlspecialchars($invoice['mobile']) ?>)</p>
    </div>

    <table width="100%">
        <thead>
            <tr>
                <th>کالا</th>
                <th>تعداد</th>
                <th>قیمت</th>
                <th>جمع</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['unit_price']) ?></td>
                    <td><?= number_format($item['total_price']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="border-top center">
        <p><strong>جمع کل: <?= number_format($invoice['total_amount']) ?> تومان</strong></p>
        <p>نحوه پرداخت: <?= $invoice['payment_method'] ?></p>
        <p>با تشکر از خرید شما</p>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>