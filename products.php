<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php'; // برای mds_date

if (!hasRole(['مدیر', 'حسابدار'])) {
    die("شما دسترسی به این بخش را ندارید.");
}

$message = '';
$branches = $pdo->query("SELECT * FROM branches")->fetchAll();

/* --------------------------------------------------
   افزودن / ویرایش کالا
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ←←← افزودن کالا ←←←
    if (isset($_POST['add_product'])) {
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $color = trim($_POST['color'] ?? '');
        $sale_price = intval($_POST['sale_price']);
        $buy_price = intval($_POST['buy_price']);
        $final_price = intval($_POST['final_price']);
        $branch_id = intval($_POST['branch_id']);
        $stock = intval($_POST['stock']);
        $barcode = trim($_POST['barcode'] ?? '');

        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_path = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        }

        $stmt = $pdo->prepare("INSERT INTO products 
            (code, name, color, sale_price, buy_price, final_price, image_path, branch_id, stock, barcode, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $code, $name, $color, $sale_price, $buy_price, $final_price,
            $image_path, $branch_id, $stock, $barcode, $_SESSION['user_id']
        ]);
        $message = "✅ کالا با موفقیت افزوده شد.";
        header("Location: products.php?added=1");
        exit;
    }

    // ←←← ویرایش کالا ←←←
    if (isset($_POST['update_product'])) {
        $id = intval($_POST['update_product']);
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $color = trim($_POST['color'] ?? '');
        $sale_price = intval($_POST['sale_price']);
        $buy_price = intval($_POST['buy_price']);
        $final_price = intval($_POST['final_price']);
        $branch_id = intval($_POST['branch_id']);
        $stock = intval($_POST['stock']);
        $barcode = trim($_POST['barcode'] ?? '');

        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_path = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        }

        $stmt = $pdo->prepare("UPDATE products 
            SET code = ?, name = ?, color = ?, sale_price = ?, buy_price = ?, final_price = ?, 
                image_path = COALESCE(?, image_path), branch_id = ?, stock = ?, barcode = ?, updated_at = NOW(), updated_by = ?
            WHERE id = ?");
        $stmt->execute([
            $code, $name, $color, $sale_price, $buy_price, $final_price,
            $image_path, $branch_id, $stock, $barcode, $_SESSION['user_id'], $id
        ]);
        $message = "✅ کالا با موفقیت به‌روزرسانی شد.";
        header("Location: products.php?edited=1");
        exit;
    }
}

/* --------------------------------------------------
   جستجو و لیست کالاها
-------------------------------------------------- */
$search = $_GET['search'] ?? '';
$extraWhere = '';
$params   = [];

if ($_SESSION['role'] === 'حسابدار') {
    $extraWhere = ' WHERE products.branch_id = ?';
    $params[]   = $_SESSION['branch_id'];
}

if (!empty($search)) {
    $extraWhere .= $extraWhere ? ' AND ' : ' WHERE ';
    $extraWhere .= '(products.name LIKE ? OR products.code LIKE ? OR products.color LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$products = $pdo->prepare("SELECT products.*, branches.name AS branch_name 
                          FROM products 
                          LEFT JOIN branches ON products.branch_id = branches.id
                          $extraWhere AND products.stock > 0
                          ORDER BY products.name");
$products->execute($params);
$products = $products->fetchAll();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت کالاها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3><i class="bi bi-box"></i> مدیریت کالاها</h3>
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">✅ کالا افزوده شد.</div>
    <?php elseif (isset($_GET['edited'])): ?>
        <div class="alert alert-success">✅ کالا به‌روزرسانی شد.</div>
    <?php endif; ?>

    <!-- جستجو -->
    <form method="get" class="mb-3">
        <div class="input-group shadow-sm">
            <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس نام، کد یا رنگ..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <!-- فرم افزودن / ویرایش -->
    <form method="post" enctype="multipart/form-data" class="mb-4 p-3 border rounded bg-light" onsubmit="return disableAndSubmit(this)">
        <input type="hidden" name="<?= isset($_GET['edit']) ? 'update_product' : 'add_product' ?>">
            <?php if (isset($_GET['edit'])) {
                $editId = intval($_GET['edit']);
                
                // ←←← مدیر: هر شعبه‌ای / حسابدار: فقط شعبه خودش ←←←
                if ($_SESSION['role'] === 'حسابدار') {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND branch_id = ?");
                    $stmt->execute([$editId, $_SESSION['branch_id']]);
                } else {
                    // مدیر: بدون محدودیت شعبه
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt->execute([$editId]);
                }
                
                $product = $stmt->fetch();
                if (!$product) die('❌ شما مجاز به ویرایش کالای شعب دیگر نیستید.');
            }

           ?>
            <input type="hidden" name="update_product" value="<?= $product['id'] ?>">
        <div class="row g-2">
            <div class="col-md-2"><input name="code" class="form-control" placeholder="کد کالا" value="<?= $product['code'] ?? '' ?>" required></div>
            <div class="col-md-2"><input name="name" class="form-control" placeholder="نام کالا" value="<?= $product['name'] ?? '' ?>" required></div>
            <div class="col-md-2"><input name="color" class="form-control" placeholder="رنگ" value="<?= $product['color'] ?? '' ?>"></div>
            <div class="col-md-2"><input name="sale_price" type="number" class="form-control" placeholder="قیمت فروش" value="<?= $product['sale_price'] ?? '' ?>" required></div>
            <div class="col-md-2"><input name="buy_price" type="number" class="form-control" placeholder="قیمت خرید" value="<?= $product['buy_price'] ?? '' ?>" required></div>
            <div class="col-md-2"><input name="final_price" type="number" class="form-control" placeholder="قیمت تمام‌شده" value="<?= $product['final_price'] ?? '' ?>" required></div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-2"><input name="stock" type="number" class="form-control" placeholder="موجودی" value="<?= $product['stock'] ?? '' ?>" required></div>
            <div class="col-md-2"><input name="barcode" class="form-control" placeholder="بارکد" value="<?= $product['barcode'] ?? '' ?>"></div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select" required>
                    <option value="">شعبه</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= isset($product) && $product['branch_id'] == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="file" name="image" class="form-control">
                <?php if (isset($product) && $product['image_path']): ?>
                    <small class="text-muted">فایل فعلی: <a href="<?= $product['image_path'] ?>" target="_blank">مشاهده</a></small>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100"><?= isset($_GET['edit']) ? 'به‌روزرسانی' : 'افزودن' ?></button>
            </div>
        </div>
    </form>
    <script>
        function disableAndSubmit(form) {
            form.querySelector('button[type="submit"]').disabled = true;
            return true;
        }
    </script>

    <!-- جدول کالاها -->
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>کد</th>
                <th>نام</th>
                <th>رنگ</th>
                <th>قیمت فروش</th>
                <th>موجودی</th>
                <th>شعبه</th>
                <th>تصویر</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['code'] ?></td>
                    <td><?= $p['name'] ?></td>
                    <td><?= $p['color'] ?></td>
                    <td><?= number_format($p['sale_price']) ?> تومان</td>
                    <td><?= $p['stock'] ?></td>
                    <td><?= $p['branch_name'] ?></td>
                    <td>
                        <?php if ($p['image_path']): ?>
                            <img src="<?= $p['image_path'] ?>" width="50" class="rounded">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil-square"></i> ویرایش
                        </a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>