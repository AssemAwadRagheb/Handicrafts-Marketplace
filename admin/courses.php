<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

// الفلترة والبحث
$craft_id = isset($_GET['craft']) ? intval($_GET['craft']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '1=1';
$params = [];

if ($craft_id) {
    $where .= " AND c.craft_id = ?";
    $params[] = $craft_id;
}

if ($status) {
    $where .= " AND c.status = ?";
    $params[] = $status;
}

if ($level) {
    $where .= " AND c.level = ?";
    $params[] = $level;
}

if ($search) {
    $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// التصنيف
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "ORDER BY ";
switch ($sort) {
    case 'newest':
        $order_by .= "c.created_at DESC";
        break;
    case 'oldest':
        $order_by .= "c.created_at ASC";
        break;
    case 'price_low':
        $order_by .= "c.price ASC";
        break;
    case 'price_high':
        $order_by .= "c.price DESC";
        break;
    case 'popular':
        $order_by .= "(SELECT COUNT(*) FROM order_items WHERE course_id = c.course_id) DESC";
        break;
    default:
        $order_by .= "c.created_at DESC";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// جلب الكورسات
$sql = "SELECT c.*, u.full_name AS craftsman_name, cr.craft_name 
        FROM courses c 
        JOIN users u ON c.craftsman_id = u.user_id 
        JOIN crafts cr ON c.craft_id = cr.craft_id 
        WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// جلب العدد الإجمالي للكورسات
$count_sql = "SELECT COUNT(*) FROM courses c WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_courses = $stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// جلب الحرف للفلترة
$crafts = $pdo->query("SELECT * FROM crafts ORDER BY craft_name")->fetchAll();

// معالجة تغيير الحالة
if (isset($_GET['update_status'])) {
    $course_id = intval($_GET['course_id']);
    $new_status = $_GET['status'];
    
    $pdo->prepare("UPDATE courses SET status = ? WHERE course_id = ?")
        ->execute([$new_status, $course_id]);
    
    $_SESSION['success'] = 'تم تحديث حالة الكورس بنجاح';
    header('Location: courses.php');
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
    <title>إدارة الكورسات - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">إدارة الكورسات</h1>
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
                            <div class="col-md-2">
                                <label for="craft" class="form-label">الحرفة</label>
                                <select id="craft" name="craft" class="form-select">
                                    <option value="">الكل</option>
                                    <?php foreach ($crafts as $craft): ?>
                                    <option value="<?= $craft['craft_id'] ?>" <?= $craft_id == $craft['craft_id'] ? 'selected' : '' ?>>
                                        <?= $craft['craft_name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">الحالة</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>نشط</option>
                                    <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>غير نشط</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="level" class="form-label">المستوى</label>
                                <select id="level" name="level" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="beginner" <?= $level == 'beginner' ? 'selected' : '' ?>>مبتدئ</option>
                                    <option value="intermediate" <?= $level == 'intermediate' ? 'selected' : '' ?>>متوسط</option>
                                    <option value="advanced" <?= $level == 'advanced' ? 'selected' : '' ?>>متقدم</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">بحث</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بعنوان الكورس أو الوصف">
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
                                <th>عنوان الكورس</th>
                                <th>الحرفة</th>
                                <th>الحرفي</th>
                                <th>المستوى</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="10" class="text-center">لا توجد نتائج</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= $course['course_id'] ?></td>
                                    <td><img src="<?= $course['cover_image'] ?>" width="50" height="50" class="img-thumbnail" alt=""></td>
                                    <td><?= $course['title'] ?></td>
                                    <td><?= $course['craft_name'] ?></td>
                                    <td><?= $course['craftsman_name'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $course['level'] == 'beginner' ? 'info' : 
                                            ($course['level'] == 'intermediate' ? 'primary' : 'warning') 
                                        ?>">
                                            <?= $course['level'] == 'beginner' ? 'مبتدئ' : 
                                               ($course['level'] == 'intermediate' ? 'متوسط' : 'متقدم') ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($course['price'], 2) ?> ر.س</td>
                                    <td>
                                        <span class="badge bg-<?= $course['status'] == 'active' ? 'success' : 'secondary' ?>">
                                            <?= $course['status'] == 'active' ? 'نشط' : 'غير نشط' ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y/m/d', strtotime($course['created_at'])) ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                تغيير الحالة
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="courses.php?course_id=<?= $course['course_id'] ?>&update_status&status=active">نشط</a></li>
                                                <li><a class="dropdown-item" href="courses.php?course_id=<?= $course['course_id'] ?>&update_status&status=inactive">غير نشط</a></li>
                                            </ul>
                                        </div>
                                        <a href="edit-course.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary ms-2">تعديل</a>
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
                            <a class="page-link" href="?page=<?= $page-1 ?>&craft=<?= $craft_id ?>&status=<?= $status ?>&level=<?= $level ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&craft=<?= $craft_id ?>&status=<?= $status ?>&level=<?= $level ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&craft=<?= $craft_id ?>&status=<?= $status ?>&level=<?= $level ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Next">
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