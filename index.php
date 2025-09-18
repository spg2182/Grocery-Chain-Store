<?php

include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';
$today = date('Y-m-d');

if ($_SESSION['role'] === 'مدیر') {
    $branchSales = $pdo->prepare("SELECT branches.name, SUM(invoices.total_amount) AS total
                                  FROM invoices JOIN branches ON invoices.branch_id = branches.id
                                  WHERE DATE(invoices.created_at) = ? AND invoices.status = 'نهایی'
                                  GROUP BY branches.id ORDER BY total DESC");
    $branchSales->execute([$today]);
    $branchSales = $branchSales->fetchAll();
}
// آمار امروز
// آمار امروز - فروش
$extraSales = '';
$paramsSales = [$today];
if ($_SESSION['role'] === 'فروشنده') {
    $extraSales = ' AND user_id = ?';
    $paramsSales[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'حسابدار') {
    $extraSales = ' AND branch_id = ?';
    $paramsSales[] = $_SESSION['branch_id'];
}
$salesToday = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0)
                               FROM invoices
                               WHERE DATE(created_at) = ? AND status = 'نهایی' $extraSales");
$salesToday->execute($paramsSales);
$salesToday = $salesToday->fetchColumn();

// مرجوعی امروز
$extraRet = '';
$paramsRet = [$today];
if ($_SESSION['role'] === 'فروشنده') {
    $extraRet = ' AND invoices.user_id = ?';
    $paramsRet[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'حسابدار') {
    $extraRet = ' AND invoices.branch_id = ?';
    $paramsRet[] = $_SESSION['branch_id'];
}
$returnsToday = $pdo->prepare("SELECT COUNT(*) 
                               FROM returns 
                               JOIN invoices ON returns.invoice_id = invoices.id
                               WHERE DATE(returns.created_at) = ? $extraRet");
$returnsToday->execute($paramsRet);
$returnsToday = $returnsToday->fetchColumn();

// ضایعات امروز
$wastesToday = $pdo->prepare("SELECT COUNT(*) 
                              FROM wastes 
                              JOIN products ON wastes.product_id = products.id
                              WHERE DATE(wastes.created_at) = ? 
                                AND wastes.confirmed_at IS NOT NULL
                                AND products.branch_id = ?");
$wastesToday->execute([$today, $_SESSION['branch_id']]);
$wastesToday = $wastesToday->fetchColumn();

// کالاهای کم‌stock (فقط همین شعبه)
$lowStock = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock < 5 AND branch_id = ?");
$lowStock->execute([$_SESSION['branch_id']]);
$lowStock = $lowStock->fetchColumn();


// نمودار 7 روز اخیر
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $paramsChart = [$date];
    $extraChart = '';
    if ($_SESSION['role'] === 'فروشنده') {
        $extraChart = ' AND user_id = ?';
        $paramsChart[] = $_SESSION['user_id'];
    } elseif ($_SESSION['role'] === 'حسابدار') {
        $extraChart = ' AND branch_id = ?';
        $paramsChart[] = $_SESSION['branch_id'];
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0)
                           FROM invoices
                           WHERE DATE(created_at) = ? AND status = 'نهایی' $extraChart");
    $stmt->execute($paramsChart);
    $last7Days[] = [
        'date' => date('Y/m/d H:i', strtotime($date)),
        'amount' => $stmt->fetchColumn()
    ];
}
/* --------------------------------------------------
   اطلاعات کاربر و شعبه
-------------------------------------------------- */
$userInfo = $pdo->prepare("SELECT users.full_name, users.role, branches.name AS branch_name
                           FROM users
                           LEFT JOIN branches ON users.branch_id = branches.id
                           WHERE users.id = ?");
$userInfo->execute([$_SESSION['user_id']]);
$user = $userInfo->fetch(PDO::FETCH_ASSOC); // مطمئن شوید آرایه برمی‌گردد
if (!$user) {
    // اگر کاربر پیدا نشد (غیرعادی) اطلاعات پیش‌فرض نمایش دهید
    $user = ['full_name' => 'ناشناس', 'role' => 'Unknown', 'branch_name' => 'بدون شعبه'];
}

/* --------- تراکنش روزانه کاربر/شعبه --------- */
$extraDash = '';
$paramsDash = [$today];
if ($_SESSION['role'] === 'فروشنده') {
    $extraDash = ' AND payments.invoice_id IN (SELECT id FROM invoices WHERE user_id = ?)';
    $paramsDash[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'حسابدار') {
    $extraDash = ' AND payments.invoice_id IN (SELECT id FROM invoices WHERE branch_id = ?)';
    $paramsDash[] = $_SESSION['branch_id'];
}

// نقدی
$cashToday = $pdo->prepare("SELECT COALESCE(SUM(cash), 0) FROM payments WHERE DATE(created_at) = ? $extraDash");
$cashToday->execute($paramsDash);
$cashToday = $cashToday->fetchColumn();

// کارت‌خوان
$posToday = $pdo->prepare("SELECT COALESCE(SUM(pos), 0) FROM payments WHERE DATE(created_at) = ? $extraDash");
$posToday->execute($paramsDash);
$posToday = $posToday->fetchColumn();

// کارت‌به‌کارت
$c2cToday = $pdo->prepare("SELECT COALESCE(SUM(card2card), 0) FROM payments WHERE DATE(created_at) = ? $extraDash");
$c2cToday->execute($paramsDash);
$c2cToday = $c2cToday->fetchColumn();

// مرجوعی تأییدشده (از شعبه کاربر)
$returnToday = $pdo->prepare("SELECT COALESCE(SUM(returns.refund_amount), 0)
                              FROM returns
                              JOIN invoices ON returns.invoice_id = invoices.id
                              WHERE returns.status = 'واریز شده'
                                AND DATE(returns.confirmed_at) = ?
                                AND invoices.branch_id = ?");
$returnToday->execute([$today, $_SESSION['branch_id']]);
$returnToday = $returnToday->fetchColumn();


?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>داشبورد فروشگاه زنجیره‌ای</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IRANSans', Tahoma, Arial;
        }
        .card-stats {
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: transform 0.3s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .card-stats .icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .chart-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .shortcut {
            background: white;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .shortcut:hover {
            transform: scale(1.05);
        }
        .shortcut i {
            font-size: 2rem;
            color: #667eea;
        }
    </style>
</head>
<body dir="rtl">

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h3 class="mb-4">داشبورد فروشگاه زنجیره‌ای</h3>
<!-- تاریخ و ساعت جاری شمیدل واقعی -->
<div class="alert alert-info d-flex justify-content-between align-items-center">
    <span><i class="bi bi-calendar-date"></i> امروز: <?= mds_date('l، j F Y', time(), 1) ?></span>
    <span><i class="bi bi-clock"></i> ساعت: <?= mds_date('H:i', time(), 1) ?></span>
</div>
<!-- اطلاعات کاربر -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-light d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <i class="bi bi-person-circle fs-5"></i>
                <span class="fw-bold ms-2"><?= htmlspecialchars($user['full_name']) ?></span>
                <small class="text-muted">(<?= $user['role'] ?>)</small>
            </div>
            <div>
                <i class="bi bi-building fs-5"></i>
                <span class="fw-bold ms-2"><?= htmlspecialchars($user['branch_name'] ?? 'بدون شعبه') ?></span>
            </div>
        </div>
    </div>
</div>
    <!-- کارت‌های آمار -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?= number_format($salesToday) ?> تومان</h5>
                        <p class="mb-0">فروش امروز</p>
                    </div>
                    <i class="bi bi-cart-check icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stats" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?= $returnsToday ?></h5>
                        <p class="mb-0">مرجوعی امروز</p>
                    </div>
                    <i class="bi bi-arrow-left-circle icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stats" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?= $wastesToday ?></h5>
                        <p class="mb-0">ضایعات امروز</p>
                    </div>
                    <i class="bi bi-trash icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stats" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?= $lowStock ?></h5>
                        <p class="mb-0">کالای کم‌ موجودی</p>
                    </div>
                    <i class="bi bi-exclamation-triangle icon" style="color: #ff9800;"></i>
                </div>
            </div>
        </div>
    </div>
<div class="row mb-3">
    <div class="col-md-3"><div class="card card-stats bg-gradient-success text-white"><div class="card-body">نقدی امروز<br><span><?= number_format($cashToday) ?> ت</span></div></div></div>
    <div class="col-md-3"><div class="card card-stats bg-gradient-info text-white">کارتخوان<br><span><?= number_format($posToday) ?> ت</span></div></div>
    <div class="col-md-3"><div class="card card-stats bg-gradient-secondary text-white">کارت‌به‌کارت<br><span><?= number_format($c2cToday) ?> ت</span></div></div>
    <div class="col-md-3"><div class="card card-stats bg-gradient-danger text-white">مرجوعی تأییدشده<br><span><?= number_format($returnToday) ?> ت</span></div></div>
</div>
<?php if ($_SESSION['role'] === 'مدیر'): ?>
<div class="col-md-12">
    <h5>فروش امروز به تفکیک شعبه</h5>
    <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>شعبه</th><th>فروش (تومان)</th></tr></thead>
        <tbody><?php foreach ($branchSales as $b): ?>
            <tr><td><?= $b['name'] ?></td><td><?= number_format($b['total']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table>
</div>
<?php endif; ?>
    <!-- نمودار -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="chart-box">
                <h6>نمودار فروش ۷ روز اخیر (تومان)</h6>
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-box">
                <h6>میان‌برهای سریع</h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="sell.php" class="text-decoration-none">
                            <div class="shortcut">
                                <i class="bi bi-basket"></i>
                                <div>فروش جدید</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="return.php" class="text-decoration-none">
                            <div class="shortcut">
                                <i class="bi bi-arrow-left-circle"></i>
                                <div>مرجوعی</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="products.php" class="text-decoration-none">
                            <div class="shortcut">
                                <i class="bi bi-box"></i>
                                <div>کالاها</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="waste.php" class="text-decoration-none">
                            <div class="shortcut">
                                <i class="bi bi-box"></i>
                                <div>ثبت ضایعات</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="reports.php" class="text-decoration-none">
                            <div class="shortcut">
                                <i class="bi bi-graph-up"></i>
                                <div>گزارشات</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($last7Days, 'date')) ?>,
        datasets: [{
            label: 'فروش (تومان)',
            data: <?= json_encode(array_column($last7Days, 'amount')) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('fa-IR') + ' ت';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>