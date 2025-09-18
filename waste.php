<?php
// اگر session استارت نشده، استارت کنیم
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();

include 'includes/auth.php';
include 'includes/db.php';

if (!in_array($_SESSION['role'], ['فروشنده', 'مدیر', 'حسابدار'])) {
    die('❌ دسترسی غیرمجاز');
}
if (isset($_GET['success'])) {
    $message = "✅ ضایعات ثبت شد و در انتظار تایید است.";
}
$message = '';
$products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND stock > 0 ORDER BY name");
$products->execute([$_SESSION['branch_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_waste'])) {
    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);
    $reason     = trim($_POST['reason']);

    $stock = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND branch_id = ?");
    $stock->execute([$product_id, $_SESSION['branch_id']]);
    $available = $stock->fetchColumn();

    if ($available >= $quantity) {
        $stmt = $pdo->prepare("INSERT INTO wastes (product_id, quantity, reason, branch_id, created_by)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $quantity, $reason, $_SESSION['branch_id'], $_SESSION['user_id']]);
        $message = "✅ ضایعات ثبت شد و در انتظار تایید است.";
        // پاک کردن مقادیر فرم برای جلوگیری از ری‌سابمیت
        $_POST = [];
            // پاک کردن مقادیر فرم + ریدایرکت برای جلوگیری از ری‌سابمیت
    header("Location: waste.php?success=1");
    exit;
    } else {
        $message = "❌ موجودی کافی نیست.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ثبت ضایعات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>ثبت ضایعات کالا</h3>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" class="p-3 border rounded bg-light">
        <div class="mb-3">
            <label>کالا</label>
            <select name="product_id" class="form-select" required>
                <option value="">انتخاب کنید</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (کد: <?= $p['code'] ?> - موجودی: <?= $p['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>تعداد ضایع شده</label>
            <input type="number" name="quantity" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
            <label>دلیل ضایع شدن</label>
            <textarea name="reason" class="form-control" rows="2" required></textarea>
        </div>

<button name="submit_waste" class="btn btn-warning w-100">
    <i class="bi bi-send"></i> ثبت ضایعات
</button>
    </form>
</div>
</body>
</html>