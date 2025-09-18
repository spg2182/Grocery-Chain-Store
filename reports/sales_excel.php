<?php
// بارگذاری پایه‌ها (همان سه خطی که در reports.php هست)
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=sales.xls");
echo "\xEF\xBB\xBF"; // BOM UTF-8 برای اکسل فارسی
?>
<table border="1">
    <tr>
        <th>شماره فاکتور</th>
        <th>تاریخ</th>
        <th>مشتری</th>
        <th>کاربر</th>
        <th>شعبه</th>
        <th>مبلغ کل (تومان)</th>
        <th>نحوه پرداخت</th>
    </tr>
    <?php foreach ($data as $row): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
        <td><?= $row['full_name'] ?></td>
        <td><?= $row['user_name'] ?></td>
        <td><?= $row['branch_name'] ?></td>
        <td><?= number_format($row['total_amount']) ?></td>
        <td><?= $row['payment_method'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
