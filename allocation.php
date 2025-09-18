<?php
include 'includes/auth.php';
include 'includes/db.php';
if (!in_array($_SESSION['role'], ['فروشنده', 'مدیر', 'حسابدار'])) die('دسترسی غیرمجاز');

if (isset($_GET['success'])) echo '<div class="alert alert-success">موجودی به کالا تخصیص پیدا کرد.</div>';

$message = '';
$products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND stock >= 0 ORDER BY name");
$products->execute([$_SESSION['branch_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);
    $reason = trim($_POST['reason'] ?? 'تحویل کالای جدید');

    $stock = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND branch_id = ?");
    $stock->execute([$product_id, $_SESSION['branch_id']]);
    $current = $stock->fetchColumn();

    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ?, updated_at = NOW(), updated_by = ? WHERE id = ? AND branch_id = ?");
    $stmt->execute([$qty, $_SESSION['user_id'], $product_id, $_SESSION['branch_id']]);

    $message = "✅ $qty عدد به موجودی افزوده شد.";
    header("Location: allocation.php?success=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="fa"><head><meta charset="UTF-8"><title>تخصیص / تحویل کالا</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body dir="rtl">
<?php include 'includes/header.php'; ?>
<div class="container mt-4"><h3><i class="bi bi-box-arrow-in-down"></i> تخصیص / تحویل کالا</h3>
<?php if ($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>
<form method="post" class="p-3 border rounded bg-light">
    <div class="row g-2">
        <div class="col-md-5"><label>کالا</label><select name="product_id" class="form-select" required><?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (کد: <?= $p['code'] ?> - موجودی: <?= $p['stock'] ?>)</option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label>تعداد ورودی</label><input type="number" name="quantity" min="1" class="form-control" required></div>
        <div class="col-md-4"><label>توضیح (اختیاری)</label><input type="text" name="reason" class="form-control" placeholder="مثلاً تحویل از انبار مرکزی"></div>
    </div>
    <button class="btn btn-success mt-3 w-100">ثبت تحویل</button>
</form></div></body></html>