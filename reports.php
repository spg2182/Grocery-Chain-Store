<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';   // ← mds_date در همین فایل تعریف شده

$type = $_GET['type'] ?? 'sales';
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

$extraWhere = '';
$params     = [$from, $to];

/* --------- نقش‌محور --------- */
if ($_SESSION['role'] === 'فروشنده') {
    $extraWhere = ' AND invoices.user_id = ?';
    $params[]   = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'حسابدار') {
    $extraWhere = ' AND invoices.branch_id = ?';
    $params[]   = $_SESSION['branch_id'];
}

/* --------- فیلترهای اختیاری --------- 
if (!empty($_GET['product_code'])) {
    $extraWhere .= ' AND products.code LIKE ?';
    $params[]   = '%' . $_GET['product_code'] . '%';
} */

if (!empty($_GET['user_id']) && hasRole(['مدیر', 'حسابدار'])) {
    $extraWhere .= ' AND invoices.user_id = ?';
    $params[]   = $_GET['user_id'];
}
if (!empty($_GET['branch_id']) && hasRole(['مدیر'])) {
    $extraWhere .= ' AND invoices.branch_id = ?';
    $params[]   = $_GET['branch_id'];
}

/* --------- ریز فروش (سطح آیتم) --------- */
if ($type === 'sales_items') {

    /* فیلتر کد کالا – فقط در این شاخه ساخته می‌شود */
    $productFilter = '';
    if (!empty($_GET['product_code'])) {
        $productFilter = ' AND p.code LIKE ?';
        $params[] = '%' . $_GET['product_code'] . '%';
    }

    $sql = "SELECT  invoices.id,
                     invoices.created_at,
                     invoices.payment_method,
                     customers.full_name  AS customer_name,
                     users.full_name      AS user_name,
                     branches.name        AS branch_name,
                     p.code               AS product_code,
                     p.name               AS product_name,
                     ii.quantity,
                     ii.unit_price,
                     ii.total_price
            FROM    invoices
            JOIN    customers     ON invoices.customer_id = customers.id
            JOIN    users         ON invoices.user_id     = users.id
            JOIN    branches      ON invoices.branch_id   = branches.id
            JOIN    invoice_items ii ON ii.invoice_id     = invoices.id
            JOIN    products      p  ON ii.product_id     = p.id
            WHERE   DATE(invoices.created_at) BETWEEN ? AND ?
                    $extraWhere
                    $productFilter
            ORDER   BY invoices.id DESC, ii.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
}

/* --------- فروش روزانه (سطح فاکتور) --------- */
if ($type === 'sales') {
    $stmt = $pdo->prepare("SELECT invoices.*,
                                  customers.full_name,
                                  customers.mobile,
                                  users.full_name  AS user_name,
                                  branches.name    AS branch_name
                           FROM   invoices
                           JOIN   customers ON invoices.customer_id = customers.id
                           JOIN   users     ON invoices.user_id     = users.id
                           JOIN   branches  ON invoices.branch_id   = branches.id
                           WHERE  DATE(invoices.created_at) BETWEEN ? AND ?
                                  $extraWhere
                           ORDER  BY invoices.id DESC");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
}

/* --------- کوئری مرجوعی --------- */
if ($type === 'returns') {
    $productFilter = '';
    if (!empty($_GET['product_code'])) {
        $productFilter = ' AND products.code LIKE ?';
        $params[] = '%' . $_GET['product_code'] . '%';
    }

    $stmt = $pdo->prepare("SELECT returns.*,
                                  products.name        AS product_name,
                                  products.code        AS product_code,
                                  customers.full_name,
                                  customers.mobile
                           FROM   returns
                           JOIN   products  ON returns.product_id = products.id
                           JOIN   invoices  ON returns.invoice_id = invoices.id
                           JOIN   customers ON invoices.customer_id = customers.id
                           WHERE  DATE(returns.created_at) BETWEEN ? AND ?
                                  $extraWhere
                                  $productFilter
                           ORDER  BY returns.id DESC");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
}

/* --------- کوئری ضایعات --------- */
if ($type === 'wastes') {
    $extraWaste  = '';
    $paramsWaste = [$from, $to];

    if ($_SESSION['role'] === 'فروشنده') {
        $extraWaste = ' AND wastes.created_by = ?';
        $paramsWaste[] = $_SESSION['user_id'];
    } elseif ($_SESSION['role'] === 'حسابدار') {
        $extraWaste = ' AND wastes.branch_id = ?';
        $paramsWaste[] = $_SESSION['branch_id'];
    }

    $wasteStatus = $_GET['waste_status'] ?? 'all';
    if ($wasteStatus === 'pending')   $extraWaste .= ' AND wastes.confirmed_at IS NULL';
    if ($wasteStatus === 'confirmed') $extraWaste .= ' AND wastes.confirmed_at IS NOT NULL';

    if (!empty($_GET['product_code'])) {
        $extraWaste .= ' AND products.code LIKE ?';
        $paramsWaste[] = '%' . $_GET['product_code'] . '%';
    }
    if (!empty($_GET['user_id']) && hasRole(['مدیر', 'حسابدار'])) {
        $extraWaste .= ' AND wastes.created_by = ?';
        $paramsWaste[] = $_GET['user_id'];
    }
    if (!empty($_GET['branch_id']) && hasRole(['مدیر'])) {
        $extraWaste .= ' AND wastes.branch_id = ?';
        $paramsWaste[] = $_GET['branch_id'];
    }

    $stmt = $pdo->prepare("SELECT wastes.*,
                                  products.name        AS product_name,
                                  branches.name        AS branch_name,
                                  creator.full_name    AS created_by_name,
                                  confirmer.full_name  AS confirmed_by_name,
                                  wastes.confirmed_at
                           FROM   wastes
                           JOIN   products  ON wastes.product_id   = products.id
                           JOIN   branches  ON wastes.branch_id    = branches.id
                           JOIN   users     AS creator ON wastes.created_by   = creator.id
                           LEFT JOIN users  AS confirmer ON wastes.confirmed_by = confirmer.id
                           WHERE  DATE(wastes.created_at) BETWEEN ? AND ?
                                  $extraWaste
                           ORDER  BY wastes.id DESC");
    $stmt->execute($paramsWaste);
    $data = $stmt->fetchAll();
}

/* --------- خروجی Excel --------- */
if (isset($_GET['excel'])) {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename={$type}.xls");
    echo "\xEF\xBB\xBF"; // BOM
    include "reports/{$type}_excel.php";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارشات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- MDS Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mds-date-picker@1.0.0/dist/mds.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/mds-date-picker@1.0.0/dist/mds.min.js"></script>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h3>گزارشات</h3>

    <form method="get" class="row g-3 mb-4 p-3 border rounded bg-light">
        <div class="col-md-3">
            <label class="form-label">نوع گزارش</label>
            <select name="type" class="form-select">
                <option value="sales"       <?= $type === 'sales'       ? 'selected' : '' ?>>فروش روزانه</option>
                <option value="sales_items" <?= $type === 'sales_items' ? 'selected' : '' ?>>ریز فروش</option>
                <option value="returns" <?= $type === 'returns' ? 'selected' : '' ?>>مرجوعی</option>
                <option value="wastes"  <?= $type === 'wastes'  ? 'selected' : '' ?>>ضایعات</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">از تاریخ</label>
            <input type="text" name="from" id="from" class="form-control" value="<?= $from ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">تا تاریخ</label>
            <input type="text" name="to" id="to" class="form-control" value="<?= $to ?>">
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary flex-fill">نمایش</button>
            <a href="?type=<?= $type ?>&from=<?= $from ?>&to=<?= $to ?>&excel=1"
               class="btn btn-success flex-fill">خروجی اکسل</a>
        </div>

        <div class="col-md-2">
            <input name="product_code" class="form-control" placeholder="کد کالا"
                   value="<?= $_GET['product_code'] ?? '' ?>">
        </div>

        <?php if (hasRole(['مدیر', 'حسابدار'])):
            $users = $pdo->query(
                "SELECT id, full_name FROM users")->fetchAll(); ?>
            <div class="col-md-2">
                                <select name="user_id" class="form-select">
                    <option value="">همه کاربران</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= (!empty($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= $u['full_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (hasRole(['مدیر'])):
            $branches = $pdo->query("SELECT id, name FROM branches")->fetchAll(); ?>
            <div class="col-md-2">
                <select name="branch_id" class="form-select">
                    <option value="">همه شعب</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"
                            <?= (!empty($_GET['branch_id']) && $_GET['branch_id'] == $b['id']) ? 'selected' : '' ?>>
                            <?= $b['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </form>

    <?php
    /* -------------------------------------------------
       نمایش جداول
    ------------------------------------------------- */
    if ($type === 'sales'): ?>
    <h5>فروش روزانه از <?= mds_date('Y/m/d', strtotime($from)) ?> تا <?= mds_date('Y/m/d', strtotime($to)) ?></h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
            <tr>
                <th>شماره فاکتور</th>
                <th>تاریخ</th>
                <th>مشتری</th>
                <th>کاربر</th>
                <th>شعبه</th>
                <th>مبلغ کل (تومان)</th>
                <th>نحوه پرداخت</th>
            </tr>
            </thead>
            <tbody>
            <?php $total = 0; ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['branch_name']) ?></td>
                    <td><?= number_format($row['total_amount']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                </tr>
                <?php $total += $row['total_amount']; ?>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
            <tr>
                <th colspan="5" class="text-end">جمع کل فروش</th>
                <th colspan="2"><?= number_format($total) ?> تومان</th>
            </tr>
            </tfoot>
        </table>
    </div>


    <?php /* --------- ریز فروش (سطح آیتم) --------- */
        elseif ($type === 'sales_items'): ?>
    <h5>ریز فروش از <?= mds_date('Y/m/d', strtotime($from)) ?> تا <?= mds_date('Y/m/d', strtotime($to)) ?></h5>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
            <tr>
                <th>شماره فاکتور</th>
                <th>تاریخ</th>
                <th>مشتری</th>
                <th>کد کالا</th>
                <th>نام کالا</th>
                <th>تعداد</th>
                <th>قیمت واحد (ت)</th>
                <th>قیمت کل (ت)</th>
                <th>نحوه پرداخت</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $current = 0;
            $factorTotal = 0;
            foreach ($data as $row):
                if ($row['id'] != $current) {
                    if ($current) echo '<tr class="table-info"><td colspan="7" class="text-end fw-bold">جمع فاکتور</td><td class="fw-bold">' . number_format($factorTotal) . '</td><td></td></tr>';
                    $current = $row['id'];
                    $factorTotal = 0;
                }
                $factorTotal += $row['total_price'];
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['product_code']) ?></td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= number_format($row['quantity']) ?></td>
                    <td><?= number_format($row['unit_price']) ?></td>
                    <td><?= number_format($row['total_price']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($current): ?>
                <tr class="table-info">
                    <td colspan="7" class="text-end fw-bold">جمع فاکتور</td>
                    <td class="fw-bold"><?= number_format($factorTotal) ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($type === 'returns'):
        $total = array_sum(array_column($data, 'refund_amount'));
        ?>
        <h5>گزارش مرجوعی از <?= mds_date('Y/m/d', strtotime($from)) ?> تا <?= mds_date('Y/m/d', strtotime($to)) ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                <tr>
                    <th>کالا</th>
                    <th>مشتری</th>
                    <th>تعداد</th>
                    <th>مبلغ مرجوعی (تومان)</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= number_format($row['quantity']) ?></td>
                        <td><?= number_format($row['refund_amount']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="3">جمع کل مرجوعی</th>
                    <th colspan="3"><?= number_format($total) ?> تومان</th>
                </tr>
                </tfoot>
            </table>
        </div>

    <?php elseif ($type === 'wastes'): ?>
        <h5>گزارش ضایعات از <?= mds_date('Y/m/d', strtotime($from)) ?> تا <?= mds_date('Y/m/d', strtotime($to)) ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                <tr>
                    <th>کالا</th>
                    <th>تعداد</th>
                    <th>دلیل</th>
                    <th>شعبه</th>
                    <th>ثبت‌کننده</th>
                    <th>تاریخ ثبت</th>
                    <th>وضعیت تأیید</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= number_format($row['quantity']) ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td><?= htmlspecialchars($row['branch_name']) ?></td>
                        <td><?= htmlspecialchars($row['created_by_name']) ?></td>
                        <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if ($row['confirmed_at']): ?>
                                ✅ <?= htmlspecialchars($row['confirmed_by_name']) ?>
                                <br><small><?= mds_date('Y/m/d H:i', strtotime($row['confirmed_at'])) ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning">در انتظار تأیید</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // فعال‌سازی MDS-Datepicker روی دو input
    mds.datepicker('from', { dateFormat: 'YYYY-MM-DD' });
    mds.datepicker('to',   { dateFormat: 'YYYY-MM-DD' });
</script>
</body>
</html>