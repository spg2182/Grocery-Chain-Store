<?php
include 'includes/auth.php';
include 'includes/db.php';

if (!hasRole(['مدیر', 'حسابدار'])) {
    die("شما دسترسی به این بخش را ندارید.");
}

if (isset($_GET['ok'])) echo '<div class="alert alert-success">مرجوعی تأیید شد.</div>';

if (isset($_POST['confirm_return'])) {
    $return_id = intval($_POST['return_id']);
    $transaction_code = $_POST['transaction_code'];
    $pdo->prepare("UPDATE returns SET status = 'واریز شده', confirmed_at = NOW(), confirmed_by = ?, transaction_code = ? WHERE id = ?")
        ->execute([$_SESSION['user_id'], $transaction_code, $return_id]);
}

$returns = $pdo->query("SELECT returns.*, products.name AS product_name, customers.full_name, customers.mobile
                        FROM returns
                        JOIN products ON returns.product_id = products.id
                        JOIN invoices ON returns.invoice_id = invoices.id
                        JOIN customers ON invoices.customer_id = customers.id
                        WHERE returns.status = 'در حال بررسی'
                        ORDER BY returns.id DESC")->fetchAll();

if (isset($_POST['confirm_return'])) {
    // ... کد تایید ...
    $message = "✅ مرجوعی تأیید و مبلغ به مشتری بازگردانده شد.";
    header("Location: returns_admin.php?ok=1");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>تایید مرجوعی‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>مرجوعی‌های در انتظار تایید</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>مشتری</th>
                <th>کالا</th>
                <th>تعداد</th>
                <th>دلیل</th>
                <th>مبلغ</th>
                <th>نحوه بازپرداخت</th>
                <th>اطلاعات بانکی</th>
                <th>تایید</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($returns as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['mobile']) ?>)</td>
                    <td><?= $r['product_name'] ?></td>
                    <td><?= $r['quantity'] ?></td>
                    <td><?= $r['reason'] ?></td>
                    <td><?= number_format($r['refund_amount']) ?> تومان</td>
                    <td><?= $r['refund_method'] ?></td>
                    <td><?= $r['bank_info'] ?: '-' ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="return_id" value="<?= $r['id'] ?>">
                            <input type="text" name="transaction_code" class="form-control form-control-sm mb-1" placeholder="کد تراکنش">
                            <button name="confirm_return" class="btn btn-sm btn-success">تایید واریز</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>