<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

// الفلترة والبحث
$type = isset($_GET['type']) ? $_GET['type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '1=1';
$params = [];

if ($type) {
    $where .= " AND user_type = ?";
    $params[] = $type;
}

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// التصنيف
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "ORDER BY ";
switch ($sort) {
    case 'newest':
        $order_by .= "created_at DESC";
        break;
    case 'oldest':
        $order_by .= "created_at ASC";
        break;
    case 'name_asc':
        $order_by .= "full_name ASC";
        break;
    case 'name_desc':
        $order_by .= "full_name DESC";
        break;
    default:
        $order_by .= "created_at DESC";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// جلب المستخدمين
$sql = "SELECT * FROM users WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// جلب العدد الإجمالي للمستخدمين
$count_sql = "SELECT COUNT(*) FROM users WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// معالجة تغيير الحالة
if (isset($_GET['update_status'])) {
    $user_id = intval($_GET['user_id']);
    $new_status = $_GET['status'];
    
    $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")
        ->execute([$new_status, $user_id]);
    
    $_SESSION['success'] = 'تم تحديث حالة المستخدم بنجاح';
    header('Location: users.php');
    exit;
}

// رسائل التنبيه
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin-sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">إدارة المستخدمين</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-user.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> إضافة مستخدم جديد
                        </a>
                    </div>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="type" class="form-label">نوع المستخدم</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="admin" <?= $type == 'admin' ? 'selected' : '' ?>>مدير</option>
                                    <option value="craftsman" <?= $type == 'craftsman' ? 'selected' : '' ?>>حرفي</option>
                                    <option value="customer" <?= $type == 'customer' ? 'selected' : '' ?>>عميل</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>نشط</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="suspended" <?= $status == 'suspended' ? 'selected' : '' ?>>موقوف</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">بحث</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بالاسم أو البريد أو اسم المستخدم">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">تصفية</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>اسم المستخدم</th>
                                <th>الاسم الكامل</th>
                                <th>البريد</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">لا توجد نتائج</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['user_id'] ?></td>
                                    <td>
                                        <img src="<?= $user['profile_image'] ?: '../assets/images/default-profile.jpg' ?>" width="40" height="40" class="rounded-circle" alt="">
                                    </td>
                                    <td><?= $user['username'] ?></td>
                                    <td><?= $user['full_name'] ?></td>
                                    <td><?= $user['email'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['user_type'] == 'admin' ? 'danger' : 
                                            ($user['user_type'] == 'craftsman' ? 'primary' : 'secondary') 
                                        ?>">
                                            <?= $user['user_type'] == 'admin' ? 'مدير' : 
                                               ($user['user_type'] == 'craftsman' ? 'حرفي' : 'عميل') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['status'] == 'active' ? 'success' : 
                                            ($user['status'] == 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= $user['status'] == 'active' ? 'نشط' : 
                                               ($user['status'] == 'pending' ? 'قيد الانتظار' : 'موقوف') ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y/m/d', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                تغيير الحالة
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="users.php?user_id=<?= $user['user_id'] ?>&update_status&status=active">نشط</a></li>
                                                <li><a class="dropdown-item" href="users.php?user_id=<?= $user['user_id'] ?>&update_status&status=pending">قيد الانتظار</a></li>
                                                <li><a class="dropdown-item" href="users.php?user_id=<?= $user['user_id'] ?>&update_status&status=suspended">موقوف</a></li>
                                            </ul>
                                        </div>
                                        <a href="edit-user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-primary ms-2">تعديل</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?>&type=<?= $type ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= $type ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&type=<?= $type ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>