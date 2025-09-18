<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

$step = 1;
$invoices = [];
$items = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_customer'])) {
        $mobile = $_POST['mobile'];
        $stmt = $pdo->prepare("SELECT invoices.*, customers.full_name 
                               FROM invoices 
                               JOIN customers ON invoices.customer_id = customers.id 
                               WHERE customers.mobile = ? AND invoices.status = 'نهایی'
                               ORDER BY invoices.id DESC");
        $stmt->execute([$mobile]);
        $invoices = $stmt->fetchAll();
        $step = 2;
    }

    if (isset($_POST['select_invoice'])) {
        $invoice_id = intval($_POST['invoice_id']);
        $stmt = $pdo->prepare("SELECT invoice_items.*, products.name 
                               FROM invoice_items 
                               JOIN products ON invoice_items.product_id = products.id 
                               WHERE invoice_items.invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $items = $stmt->fetchAll();
        $_SESSION['return_invoice_id'] = $invoice_id;
        $step = 3;
    }

    if (isset($_POST['submit_return'])) {
        $invoice_id = $_SESSION['return_invoice_id'];
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $reason = $_POST['reason'];
        $refund_method = $_POST['refund_method'];
        $refund_amount = intval($_POST['refund_amount']);
        $bank_info = $_POST['bank_info'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO returns 
            (invoice_id, product_id, quantity, reason, refund_amount, refund_method, bank_info)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $product_id, $quantity, $reason, $refund_amount, $refund_method, $bank_info]);

        // افزودن به انبار
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
            ->execute([$quantity, $product_id]);

        $message = "مرجوعی ثبت شد و در انتظار تایید مالی است.";
        $step = 1;
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مرجوعی کالا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>مرجوعی کالا</h3>
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="post">
            <div class="mb-3">
                <label>موبایل مشتری</label>
                <input type="text" name="mobile" class="form-control" required>
            </div>
            <button name="search_customer" class="btn btn-primary">جستجو</button>
        </form>
    <?php elseif ($step === 2): ?>
        <h5>فاکتورهای یافت‌شده:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>شماره فاکتور</th>
                    <th>نام مشتری</th>
                    <th>تاریخ</th>
                    <th>انتخاب</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= $inv['id'] ?></td>
                        <td><?= htmlspecialchars($inv['full_name']) ?></td>
                        <td><?= mds_date('Y/m/d H:i', strtotime($inv['created_at'])) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                <button name="select_invoice" class="btn btn-sm btn-info">انتخاب</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($step === 3): ?>
        <h5>کالاهای این فاکتور:</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>کالا</th>
                    <th>تعداد فروخته‌شده</th>
                    <th>تعداد مرجوعی</th>
                    <th>دلیل</th>
                    <th>مبلغ مرجوعی (تومان)</th>
                    <th>نحوه بازپرداخت</th>
                    <th>شماره کارت/شبا</th>
                    <th>ثبت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <form method="post">
                        <tr>
                            <td><?= $item['name'] ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><input type="number" name="quantity" class="form-control form-control-sm" min="1" max="<?= $item['quantity'] ?>" required></td>
                            <td><input type="text" name="reason" class="form-control form-control-sm" required></td>
                            <td><input type="number" name="refund_amount" class="form-control form-control-sm" required></td>
                            <td>
                                <select name="refund_method" class="form-select form-select-sm" required>
                                    <option value="نقدی">نقدی</option>
                                    <option value="کارت به کارت">کارت به کارت</option>
                                    <option value="شبا">شبا</option>
                                    <option value="ترکیبی">ترکیبی</option>
                                </select>
                            </td>
                            <td><input type="text" name="bank_info" class="form-control form-control-sm" placeholder="اختیاری"></td>
                            <td>
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button name="submit_return" class="btn btn-sm btn-warning">ثبت مرجوعی</button>
                            </td>
                        </tr>
                    </form>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>