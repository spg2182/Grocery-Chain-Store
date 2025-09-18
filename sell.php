<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

$message = '';
$cart = $_SESSION['cart'] ?? [];
$pStock = [];

// چک مجدد شعبه کاربر (دیباگ + امنیت)
$chk = $pdo->prepare("SELECT branch_id FROM users WHERE id = ?");
$chk->execute([$_SESSION['user_id']]);
$realBranch = $chk->fetchColumn();

if ($realBranch != $_SESSION['branch_id']) {
    // اگر شعبه در SESSION با دیتابیس یکی نیست، اصلاح کنیم
    $_SESSION['branch_id'] = $realBranch;
    $message = "⚠️ شعبه شما به‌روزرسانی شد.";
}
// حذف آیتم‌هایی که متعلق به شعبهٔ کاربر نیستند
$cleanCart = [];
foreach ($cart as $pid => $item) {
    $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND branch_id = ?");
    $chk->execute([$pid, $_SESSION['branch_id']]);
    if ($chk->fetch()) $cleanCart[$pid] = $item;
}
if (count($cleanCart) !== count($cart)) {
    $_SESSION['cart'] = $cleanCart;
    $cart = $cleanCart;
    $message = "⚠️ برخی کالاها از شعبهٔ دیگر حذف شدند.";
}

/* --------------------------------------------------
   جستجوی زنده کالا - فقط شعبه خودم
-------------------------------------------------- */
$products = [];
if (!empty($_GET['search'])) {
    $search = $_GET['search'];
$search = "%$search%";
$products = $pdo->prepare("SELECT * FROM products
                           WHERE branch_id = ?
                             AND (name LIKE ? OR code LIKE ? OR color LIKE ?)
                             AND stock > 0
                           ORDER BY name");
$products->execute([
    $_SESSION['branch_id'],
    $search,
    $search,
    $search
]);
    $products = $products->fetchAll();
}


// از این به بعد فقط از $_SESSION['branch_id'] استفاده کنید

// موجودی لحظه‌ای محصولات داخل سبد
foreach (array_keys($cart) as $pid) {
    $st = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND branch_id = ?");
    $st->execute([$pid, $_SESSION['branch_id']]);
    $pStock[$pid] = $st->fetchColumn() ?: 0;
}
/* --------------------------------------------------
   افزودن / ویرایش / حذف سبد
-------------------------------------------------- */
/* --------------------------------------------------
   قبل از هر عملیات روی سبد / فاکتور
   دوباره شعبه را چک می‌کنیم
-------------------------------------------------- */
// افزودن به سبد
if (isset($_POST['add_to_cart'])) {
    $pid = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);
    // فقط کالای همین شعبه و موجودی کافی
    $product = $pdo->prepare("SELECT * FROM products
                              WHERE id = ? AND branch_id = ? AND stock >= ?");
    $product->execute([$pid, $_SESSION['branch_id'], $qty]);
    $p = $product->fetch();
    if (!$p) {
        $message = "❌ کالا در شعبه شما موجود نیست یا موجودی کافی نیست.";
    } else {
        // بقیه منطق افزودن به سبد
        if (isset($cart[$pid])) {
            $newQty = $cart[$pid]['qty'] + $qty;
            if ($newQty > $p['stock']) $newQty = $p['stock'];
            $cart[$pid]['qty'] = $newQty;
            $cart[$pid]['total'] = $newQty * $p['sale_price'];
        } else {
            $cart[$pid] = [
                'name' => $p['name'],
                'price' => $p['sale_price'],
                'qty' => $qty,
                'total' => $qty * $p['sale_price']
            ];
        }
        $_SESSION['cart'] = $cart;
        $message = "✅ افزوده شد (موجودی فعلی: " . ($p['stock'] - $cart[$pid]['qty']) . ")";
    }
}

    // ویرایش تعداد در سبد
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['qty'] as $pid => $newQty) {
            $newQty = intval($newQty);
            if ($newQty <= 0) {
                unset($cart[$pid]);
                continue;
            }
            $max = $pStock[$pid] ?? 0;
            if ($newQty > $max) $newQty = $max;
            $cart[$pid]['qty'] = $newQty;
            $cart[$pid]['total'] = $newQty * $cart[$pid]['price'];
        }
        $_SESSION['cart'] = $cart;
        header("Location: sell.php");
        exit;
    }

    // حذف از سبد
    if (isset($_POST['remove_from_cart'])) {
        $pid = intval($_POST['product_id']);
        unset($cart[$pid]);
        $_SESSION['cart'] = $cart;
        header("Location: sell.php");
        exit;
    }

/* --------------------------------------------------
   قبل از ثبت فاکتور هم دوباره چک می‌کنیم
-------------------------------------------------- */
    if (isset($_POST['finalize_sale'])) {
        $errors = [];

        // 1️⃣ سبد خالی؟
        if (!$cart) {
            $errors[] = "❌ سبد خرید خالی است.";
        }

        // 2️⃣ موبایل مشتری
        $customer_mobile = $_POST['customer_mobile'] ?? '';
        if (!preg_match('/^09[0-9]{9}$/', $customer_mobile)) {
            $errors[] = "❌ موبایل مشتری معتبر نیست (۰۹xxxxxxxxx).";
        }

    // 3️⃣ تطبیق پرداخت با جمع کل
        $cash = intval($_POST['cash'] ?? 0);
        $pos = intval($_POST['pos'] ?? 0);
        $card2card = intval($_POST['card2card'] ?? 0);
        $totalPay = $cash + $pos + $card2card;
        $totalCart = array_sum(array_column($cart, 'total'));

        if ($totalPay != $totalCart) {
            $errors[] = "❌ مجموع پرداخت‌ها (" . number_format($totalPay) . ") با مبلغ کل فاکتور (" . number_format($totalCart) . ") برابر نیست.";
        }

    // 4️⃣ چک مجدد شعبه برای هر آیتم (آپشنال ولی امن)
    foreach ($cart as $pid => $item) {
        $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND branch_id = ?");
        $chk->execute([$pid, $_SESSION['branch_id']]);
        if (!$chk->fetch()) {
            $errors[] = "کالای {$item['name']} در شعبه شما نیست.";
            unset($cart[$pid]);
        }
    }        

        // اگر خطا داریم برگرد و نمایش بده
        if ($errors) {
            $message = implode('<br>', $errors);
        } else {
            // مشتری
            $customer = $pdo->prepare("SELECT * FROM customers WHERE mobile = ?");
            $customer->execute([$customer_mobile]);
            $customer = $customer->fetch();
            if (!$customer) {
                $stmt = $pdo->prepare("INSERT INTO customers (mobile, full_name) VALUES (?, ?)");
                $stmt->execute([$customer_mobile, $_POST['customer_name']]);
                $customer_id = $pdo->lastInsertId();
            } else {
                $customer_id = $customer['id'];
            }

            // ذخیره فاکتور
            $stmt = $pdo->prepare("INSERT INTO invoices (customer_id, user_id, branch_id, total_amount, payment_method, status)
                                   VALUES (?, ?, ?, ?, 'ترکیبی', 'نهایی')");
            $stmt->execute([$customer_id, $_SESSION['user_id'], $_SESSION['branch_id'], $totalCart]);
            $invoice_id = $pdo->lastInsertId();

            // ذخیره آیتم‌ها و کاهش موجودی
            foreach ($cart as $pid => $item) {
                $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, total_price)
                               VALUES (?, ?, ?, ?, ?)")
                    ->execute([$invoice_id, $pid, $item['qty'], $item['price'], $item['total']]);
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['qty'], $pid]);
            }

            // ذخیره جزئیات پرداخت
            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, cash, pos, card2card) VALUES (?, ?, ?, ?)");
            $stmt->execute([$invoice_id, $cash, $pos, $card2card]);

            $_SESSION['cart'] = [];
            $message = "✅ فاکتور شماره $invoice_id ثبت و چاپ شد.";
            echo "<script>window.open('print.php?invoice_id=$invoice_id', '_blank');</script>";
            header("Refresh: 1; url=sell.php");
            exit;
        }
    }


/* --------------------------------------------------
   جستجوی زنده کالا
-------------------------------------------------- */
$products = [];
if (!empty($_GET['search'])) {
    $search = $_GET['search'];
    $products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND (name LIKE ? OR code LIKE ? OR color LIKE ?) AND stock > 0 ORDER BY name");
    $products->execute([$_SESSION['branch_id'], "%$search%", "%$search%", "%$search%"]);
    $products = $products->fetchAll();
}

/* --------------------------------------------------
   مشتری بر اساس موبایل (AJAX)
-------------------------------------------------- */
$customerInfo = '';
if (!empty($_GET['customer_mobile'])) {
    $mobile = $_GET['customer_mobile'];
    $cust = $pdo->prepare("SELECT full_name FROM customers WHERE mobile = ?");
    $cust->execute([$mobile]);
    $name = $cust->fetchColumn();
    $customerInfo = $name ?: '';
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>فروش کالا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'IRANSans', Tahoma; }
        .card-product { transition: transform .2s; cursor: pointer; }
        .card-product:hover { transform: scale(1.03); }
        .search-box { border-radius: 25px; padding: 10px 20px; }
        .payment-input { max-width: 120px; }
        .error-alert { animation: shake 0.3s; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body dir="rtl">
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3 class="mb-4"><i class="bi bi-basket"></i> فروش کالا</h3>
    <?php if ($message): ?>
        <div class="alert alert-<?= strpos($message, '❌') !== false ? 'danger error-alert' : 'success' ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?>)</span>
    <span><i class="bi bi-building"></i> شعبه: <?= htmlspecialchars($pdo->query("SELECT name FROM branches WHERE id = " . $_SESSION['branch_id'])->fetchColumn()) ?></span>
</div>

        <!-- جستجو و لیست کالا -->
        <div class="col-md-6">
            <form method="get" class="mb-3">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" value="<?= $_GET['search'] ?? '' ?>" class="form-control search-box" placeholder="جستجو بر اساس کد، نام یا رنگ..." autofocus>
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <?php if ($products): ?>
                <div class="row g-3">
                    <?php foreach ($products as $p): ?>
                        <div class="col-12 col-lg-6">
                            <div class="card card-product shadow-sm">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= $p['name'] ?></h6>
                                        <small class="text-muted">کد: <?= $p['code'] ?> | رنگ: <?= $p['color'] ?></small><br>
                                        <span class="badge bg-success"><?= number_format($p['sale_price']) ?> تومان</span>
                                        <small class="text-muted">موجودی: <?= $p['stock'] ?></small>
                                    </div>
                                    <form method="post" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" class="form-control form-control-sm" style="width: 70px;">
                                        <button name="add_to_cart" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (isset($_GET['search'])): ?>
                <div class="alert alert-warning">کالایی یافت نشد.</div>
            <?php endif; ?>
        </div>

        <!-- سبد خرید -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><i class="bi bi-cart4"></i> سبد خرید</div>
                <div class="card-body">
                    <form method="post">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>کالا</th>
                                    <th width="90">تعداد</th>
                                    <th>قیمت</th>
                                    <th>جمع</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$cart): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">سبد خالی است</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($cart as $pid => $item): ?>
                                    <tr>
                                        <td><?= $item['name'] ?></td>
                                        <td>
                                            <input type="number" name="qty[<?= $pid ?>]" value="<?= $item['qty'] ?>" min="1" max="<?= $pStock[$pid] ?? $item['qty'] ?>" class="form-control form-control-sm">
                                        </td>
                                        <td><?= number_format($item['price']) ?></td>
                                        <td><?= number_format($item['total']) ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?= $pid ?>">
                                                <button name="remove_from_cart" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($cart): ?>
                            <button name="update_cart" class="btn btn-sm btn-secondary mb-3">به‌روزرسانی تعداد</button>
                        <?php endif; ?>
                    </form>

                    <!-- اطلاعات مشتری -->
                    <form method="post" id="finalForm">
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label>موبایل مشتری</label>
                                <input type="text" name="customer_mobile" id="mobile" class="form-control" pattern="09[0-9]{9}" required>
                                <small id="custName" class="text-success"></small>
                            </div>
                            <div class="col-5">
                                <label>نام (در صورت جدید)</label>
                                <input type="text" name="customer_name" class="form-control">
                            </div>
                        </div>

                        <!-- پرداخت ترکیبی -->
                        <div class="border rounded p-2 mb-3 bg-light">
                            <label class="d-block mb-2">جزئیات پرداخت (ترکیبی)</label>
                            <div class="row g-2">
                                <div class="col"><label class="small">نقدی</label><input name="cash" type="number" min="0" class="form-control payment-input" value="0"></div>
                                <div class="col"><label class="small">کارتخوان</label><input name="pos" type="number" min="0" class="form-control payment-input" value="0"></div>
                                <div class="col"><label class="small">کارت‌به‌کارت</label><input name="card2card" type="number" min="0" class="form-control payment-input" value="0"></div>
                            </div>
                            <div class="mt-2 text-center fw-bold">جمع کل سبد: <span id="totalSum"><?= number_format(array_sum(array_column($cart, 'total'))) ?></span> تومان</div>
                        </div>

                        <button name="finalize_sale" class="btn btn-success w-100">ثبت و چاپ فاکتور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// نمایش نام مشتری لحظه‌ای
document.getElementById('mobile').addEventListener('input', function () {
    const mobile = this.value;
    if (mobile.length === 11 && mobile.startsWith('09')) {
        fetch('get_customer_name.php?customer_mobile=' + mobile)
            .then(r => r.text())
            .then(name => {
                document.getElementById('custName').textContent = name ? '✅ ' + name : '';
                if (name) document.querySelector('[name="customer_name"]').value = name;
            });
    } else {
        document.getElementById('custName').textContent = '';
    }
});

// غیرفعال‌سازی اینتر در فرم نهایی اگر شرط‌ها برقرار نباشد
document.getElementById('finalForm').addEventListener('submit', function (e) {
    const total = <?= array_sum(array_column($cart, 'total')) ?>;
    const cash = parseInt(document.querySelector('[name="cash"]').value) || 0;
    const pos = parseInt(document.querySelector('[name="pos"]').value) || 0;
    const card2card = parseInt(document.querySelector('[name="card2card"]').value) || 0;
    const mobile = document.getElementById('mobile').value;
    const paySum = cash + pos + card2card;

    if (total === 0) {
        e.preventDefault();
        alert('❌ سبد خرید خالی است.');
        return;
    }
    if (!mobile.match(/^09[0-9]{9}$/)) {
        e.preventDefault();
        alert('❌ موبایل مشتری معتبر نیست.');
        return;
    }
    if (paySum !== total) {
        e.preventDefault();
        alert('❌ مجموع پرداخت‌ها با مبلغ کل فاکتور برابر نیست.');
    }
});
</script>
</body>
</html>