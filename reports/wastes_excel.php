<?php
// بارگذاری پایه‌ها (همان سه خطی که در reports.php هست)
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';
// فعلاً خروجی ساده برای تست
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=wastes.xls");
echo "\xEF\xBB\xBF"; // BOM UTF-8
?>
<table border="1">
    <thead>
        <tr>
            <th>کالا</th>
            <th>تعداد</th>
            <th>دلیل</th>
            <th>شعبه</th>
            <th>ثبت‌کننده</th>
            <th>تاریخ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= $row['product_name'] ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['reason'] ?></td>
                <td><?= $row['branch_name'] ?></td>
                <td><?= $row['created_by_name'] ?></td>
                <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>