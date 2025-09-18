<?php
include 'includes/auth.php';
include 'includes/db.php';

if (!hasRole(['مدیر', 'حسابدار'])) {
    die("شما دسترسی به این بخش را ندارید.");
}

if (isset($_GET['success'])) echo '<div class="alert alert-success">ضایعه تأیید شد.</div>';

if (isset($_POST['confirm_waste'])) {
    $waste_id = intval($_POST['waste_id']);
    $pdo->prepare("UPDATE wastes SET confirmed_at = NOW(), confirmed_by = ? WHERE id = ?")
        ->execute([$_SESSION['user_id'], $waste_id]);

    // کسر از موجودی
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM wastes WHERE id = ?");
	$stmt->execute([$waste_id]);
	$waste = $stmt->fetch();
    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
        ->execute([$waste['quantity'], $waste['product_id']]);
    $message = "✅ ضایعه تأیید و از انبار کسر شد.";
    header("Location: waste_admin.php?success=1");
    exit;
}

$extra = '';
$params = [];
if ($_SESSION['role'] === 'حسابدار') {
    $extra = ' AND wastes.branch_id = ?';
    $params[] = $_SESSION['branch_id'];
}

$wastes = $pdo->prepare("SELECT wastes.*, products.name AS product_name, branches.name AS branch_name, users.full_name AS created_by_name
                       FROM wastes
                       JOIN products ON wastes.product_id = products.id
                       JOIN branches ON wastes.branch_id = branches.id
                       JOIN users ON wastes.created_by = users.id
                       WHERE wastes.confirmed_at IS NULL $extra
                       ORDER BY wastes.id DESC");
$wastes->execute($params);
$wastes = $wastes->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>تایید ضایعات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>ضایعات در انتظار تایید</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>کالا</th>
                <th>تعداد</th>
                <th>دلیل</th>
                <th>شعبه</th>
                <th>ثبت‌کننده</th>
                <th>تایید</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($wastes as $w): ?>
                <tr>
                    <td><?= $w['product_name'] ?></td>
                    <td><?= $w['quantity'] ?></td>
                    <td><?= $w['reason'] ?></td>
                    <td><?= $w['branch_name'] ?></td>
                    <td><?= $w['created_by_name'] ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="waste_id" value="<?= $w['id'] ?>">
                            <button name="confirm_waste" class="btn btn-sm btn-danger">تایید ضایع شدن</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>