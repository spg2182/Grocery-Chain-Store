<?php
include 'includes/auth.php';
include 'includes/db.php';

if (!hasRole(['مدیر'])) die('دسترسی غیرمجاز');

$message = '';
$branches = $pdo->query("SELECT * FROM branches")->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   افزودن کاربر جدید
-------------------------------------------------- */
if (isset($_POST['add_user'])) {
    $errors = validateUser($_POST);
    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, mobile, email, role, branch_id)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['username'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['full_name'],
            $_POST['mobile'],
            $_POST['email'],
            $_POST['role'],
            $_POST['branch_id']
        ]);
        $message = 'کاربر جدید افزوده شد.';
    } else {
        $message = implode('<br>', $errors);
    }
}

/* --------------------------------------------------
   ویرایش کاربر
-------------------------------------------------- */
if (isset($_POST['edit_user'])) {
    $id = intval($_POST['user_id']);
    $errors = validateUser($_POST, $id);
    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users
                               SET username=?, full_name=?, mobile=?, email=?, role=?, branch_id=?
                               WHERE id=?");
        $stmt->execute([
            $_POST['username'], $_POST['full_name'], $_POST['mobile'],
            $_POST['email'], $_POST['role'], $_POST['branch_id'], $id
        ]);
        $message = 'اطلاعات کاربر به‌روز شد.';
    } else {
        $message = implode('<br>', $errors);
    }
}

/* --------------------------------------------------
   حذف کاربر
-------------------------------------------------- */
if (isset($_POST['delete_user'])) {
    $id = intval($_POST['user_id']);
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    $message = 'کاربر حذف شد.';
}


/* --------------------------------------------------
   اعتبارسنجی
-------------------------------------------------- */
function validateUser($data, $ignoreId = null)
{
    global $pdo;
    $errors = [];

    // موبایل
    if (!preg_match('/^0[0-9]{10}$/', $data['mobile']))
        $errors[] = 'موبایل باید ۱۱ رقمی و با صفر شروع شود.';

    // ایمیل
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'ایمیل معتبر وارد کنید.';

    // یکتایی موبایل
    $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile=? " . ($ignoreId ? "AND id<>?" : ""));
    $stmt->execute($ignoreId ? [$data['mobile'], $ignoreId] : [$data['mobile']]);
    if ($stmt->fetch()) $errors[] = 'این موبایل قبلاً ثبت شده.';

    // یکتایی ایمیل
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? " . ($ignoreId ? "AND id<>?" : ""));
    $stmt->execute($ignoreId ? [$data['email'], $ignoreId] : [$data['email']]);
    if ($stmt->fetch()) $errors[] = 'این ایمیل قبلاً ثبت شده.';

    // یکتایی نام‌کاربری
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? " . ($ignoreId ? "AND id<>?" : ""));
    $stmt->execute($ignoreId ? [$data['username'], $ignoreId] : [$data['username']]);
    if ($stmt->fetch()) $errors[] = 'این نام‌کاربری قبلاً ثبت شده.';

    return $errors;
}

/* --------------------------------------------------
   لیست کاربران
-------------------------------------------------- */
$users = $pdo->query("SELECT u.*, b.name AS branch_name
                      FROM users u
                      LEFT JOIN branches b ON u.branch_id = b.id
                      ORDER BY u.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>مدیریت کاربران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .toast-container { position: fixed; bottom: 20px; left: 20px; z-index: 1055; }
    </style>
</head>
<body dir="rtl">
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h3>مدیریت کاربران</h3>

    <!-- فرم افزودن -->
    <form method="post" class="p-3 border rounded bg-light mb-4">
        <div class="row">
            <div class="col-md-3"><input name="username" class="form-control" placeholder="نام‌کاربری" required></div>
            <div class="col-md-3"><input name="password" type="password" class="form-control" placeholder="رمز عبور" required></div>
            <div class="col-md-3"><input name="full_name" class="form-control" placeholder="نام و نام‌خانوادگی" required></div>
            <div class="col-md-3"><input name="mobile" class="form-control" placeholder="موبایل (۰۹xxxxxxxxx)" pattern="09[0-9]{9}" required></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="ایمیل" required></div>
            <div class="col-md-2">
                <select name="role" class="form-select" required>
                    <option value="">نقش</option>
                    <option>مدیر</option>
                    <option>حسابدار</option>
                    <option>فروشنده</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="branch_id" class="form-select" required>
                    <option value="">شعبه</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><button name="add_user" class="btn btn-success w-100">افزودن کاربر</button></div>
        </div>
    </form>

    <!-- جدول کاربران -->
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
        <tr>
            <th>نام‌کاربری</th>
            <th>نام کامل</th>
            <th>موبایل</th>
            <th>ایمیل</th>
            <th>نقش</th>
            <th>شعبه</th>
            <th>عملیات</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td dir="ltr"><?= htmlspecialchars($u['mobile']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= $u['branch_name'] ?? '-' ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">ویرایش</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= $u['full_name'] ?>')">حذف</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal ویرایش -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش کاربر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="edit_id">
                <div class="mb-3"><input name="username" id="edit_username" class="form-control" required></div>
                <div class="mb-3"><input name="full_name" id="edit_full_name" class="form-control" required></div>
                <div class="mb-3"><input name="mobile" id="edit_mobile" class="form-control" pattern="09[0-9]{9}" required></div>
                <div class="mb-3"><input name="email" type="email" id="edit_email" class="form-control" required></div>
                <div class="mb-3">
                    <select name="role" id="edit_role" class="form-select" required>
                        <option>مدیر</option>
                        <option>حسابدار</option>
                        <option>فروشنده</option>
                    </select>
                </div>
                <div class="mb-3">
                    <select name="branch_id" id="edit_branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button name="edit_user" class="btn btn-primary">ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast پیغام -->
<div class="toast-container">
    <div id="liveToast" class="toast" role="alert">
        <div class="toast-header">
            <strong class="me-auto">پیغام سیستم</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body"><?= $message ?></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($message): ?>
    const toastLive = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastLive);
    toast.show();
    <?php endif; ?>

    function editUser(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_mobile').value = user.mobile;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_branch_id').value = user.branch_id;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function deleteUser(id, name) {
        Swal.fire({
            title: 'حذف کاربر',
            text: `آیا از حذف "${name}" اطمینان دارید؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف'
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `<input name="delete_user" value="1"><input name="user_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
</body>
</html>