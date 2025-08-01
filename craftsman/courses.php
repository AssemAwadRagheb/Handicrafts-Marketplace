<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// معالجة حذف الكورس
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);
    
    // التحقق من أن الكورس يخص الحرفي
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ? AND craftsman_id = ?");
    $stmt->execute([$course_id, $user_id]);
    $course = $stmt->fetch();
    
    if ($course) {
        $pdo->prepare("DELETE FROM courses WHERE course_id = ?")->execute([$course_id]);
        $_SESSION['success'] = 'تم حذف الكورس بنجاح';
    } else {
        $_SESSION['error'] = 'الكورس غير موجود أو لا تملك صلاحية حذفه';
    }
    
    header('Location: courses.php');
    exit;
}

// جلب كورسات الحرفي
$stmt = $pdo->prepare("SELECT c.*, cr.craft_name 
                       FROM courses c 
                       JOIN crafts cr ON c.craft_id = cr.craft_id 
                       WHERE c.craftsman_id = ? 
                       ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

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
    <title>كورساتي - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/craftsman-header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/craftsman-sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">كورساتي</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-course.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> إضافة كورس جديد
                        </a>
                    </div>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>عنوان الكورس</th>
                                <th>الحرفة</th>
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
                                <td colspan="9" class="text-center">لا توجد كورسات حتى الآن</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= $course['course_id'] ?></td>
                                    <td><img src="<?= $course['cover_image'] ?>" width="50" height="50" class="img-thumbnail" alt=""></td>
                                    <td><?= $course['title'] ?></td>
                                    <td><?= $course['craft_name'] ?></td>
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
                                        <a href="edit-course.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="courses.php?delete=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الكورس؟')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>