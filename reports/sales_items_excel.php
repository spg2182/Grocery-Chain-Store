<?php
// بارگذاری پایه‌ها (همان سه خطی که در reports.php هست)
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';
// فعلاً خروجی ساده برای تست
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=sales_items.xls");
echo "\xEF\xBB\xBF"; // BOM UTF-8
?>
<table border="1">
    <tr>
        <th>شماره فاکتور</th>
        <th>تاریخ</th>
        <th>مشتری</th>
        <th>کد کالا</th>
        <th>نام کالا</th>
        <th>تعداد</th>
        <th>قیمت واحد</th>
        <th>قیمت کل</th>
    </tr>
    <?php foreach ($data as $row): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= mds_date('Y/m/d H:i', strtotime($row['created_at'])) ?></td>
        <td><?= $row['customer_name'] ?></td>
        <td><?= $row['product_code'] ?></td>
        <td><?= $row['product_name'] ?></td>
        <td><?= $row['quantity'] ?></td>
        <td><?= $row['unit_price'] ?></td>
        <td><?= $row['total_price'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>