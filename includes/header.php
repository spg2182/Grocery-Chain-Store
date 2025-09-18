<!DOCTYPE html>
<html lang="fa">
<head>
    <link href="https://cdn.fontcdn.ir/Font/Persian/IRANSans/IRANSans.css" rel="stylesheet">
<style>
    body {
        font-family: 'IRANSans', Tahoma, Arial;
        .navbar{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)!important;}
        }
</style>
</style>
    <meta charset="UTF-8">
    <title>فروشگاه زنجیره‌ای</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body dir="rtl">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-shop"></i> فروشگاه زنجیره‌ای</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> داشبورد</a></li>
                <li class="nav-item"><a class="nav-link" href="sell.php"><i class="bi bi-basket"></i> فروش</a></li>
                <li class="nav-item"><a class="nav-link" href="return.php"><i class="bi bi-arrow-left-circle"></i> مرجوعی</a></li>
                <li class="nav-item"><a class="nav-link" href="allocation.php"><i class="bi bi-box-arrow-in-down"></i> تحویل کالا</a></li>
                <li class="nav-item"><a class="nav-link" href="waste.php"><i class="bi bi-trash"></i> ثبت ضایعات</a></li>                
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> گزارشات</a></li>
                <?php if (hasRole(['مدیر'])): ?>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-box"></i> کالاها</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> کاربران</a></li>
                    <li class="nav-item"><a class="nav-link" href="branches.php"><i class="bi bi-building"></i> شعب</a></li>
                    <li class="nav-item"><a class="nav-link" href="returns_admin.php"><i class="bi bi-trash"></i> تایید مرجوعی</a></li>
                    <li class="nav-item"><a class="nav-link" href="waste_admin.php"><i class="bi bi-trash"></i> تایید ضایعات</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-left"></i> خروج</a></li>
            </ul>
        </div>
    </div>
</nav>