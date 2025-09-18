<?php
include 'includes/auth.php';
include 'includes/db.php';

if (!hasRole(['مدیر'])) {
    die("شما دسترسی به این بخش را ندارید.");
}

if (isset($_GET['edited'])) {
    $message = "✅ تغییرات ذخیره شد.";
}

$message = '';

// ←←← افزودن شعبه ←←←
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $name = trim($_POST['name']);
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO branches (name, address, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $address, $phone]);

    header("Location: branches.php?added=1");
    exit;
}

// ←←← ویرایش شعبه ←←←
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_branch'])) {
    $id = intval($_POST['update_branch']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("UPDATE branches SET name = ?, address = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $address, $phone, $id]);

    header("Location: branches.php?edited=1");
    exit;
}

// ←←← حذف شعبه ←←←
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([$id]);
    header("Location: branches.php");
    exit;
}

// ←←← وضعیت صفحه ←←←
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();

if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$editId]);
    $branch = $stmt->fetch();
    if (!$branch) die('❌ شعبه یافت نشد.');
}
?>


<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت شعب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>مدیریت شعب</h3>
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>


<form method="post" class="mb-4 p-3 border rounded bg-light" onsubmit="return disableAndSubmit(this)">
    <div class="row">
        <div class="col-md-4"><input name="name" class="form-control" placeholder="نام شعبه" required></div>
        <div class="col-md-4"><input name="address" class="form-control" placeholder="آدرس"></div>
        <div class="col-md-3"><input name="phone" class="form-control" placeholder="تلفن"></div>
        <div class="col-md-1">
            <button type="submit" name="add_branch" class="btn btn-primary w-100">افزودن</button>
        </div>
    </div>
</form>
        
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>نام شعبه</th>
                <th>آدرس</th>
                <th>تلفن</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($branches as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['name']) ?></td>
                    <td><?= htmlspecialchars($b['address']) ?></td>
                    <td><?= htmlspecialchars($b['phone']) ?></td>
                    <td><a href="?edit=<?= $b['id'] ?>" class="btn btn-sm btn-primary">ویرایش</a></td>
                    <td>
                        <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (isset($branch)): ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header">ویرایش شعبه</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="update_branch" value="<?= $branch['id'] ?>">
                <div class="mb-3">
                    <label>نام شعبه</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($branch['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>آدرس</label>
                    <textarea name="address" class="form-control"><?= htmlspecialchars($branch['address']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label>تلفن</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($branch['phone']) ?>">
                </div>
                <button class="btn btn-primary" onclick="this.disabled=true;this.form.submit();">ذخیره تغییرات</button>
                <a href="branches.php" class="btn btn-secondary">انصراف</a>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
</body>
</html>