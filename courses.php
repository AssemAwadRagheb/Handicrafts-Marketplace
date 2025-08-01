<?php
require_once 'includes/config.php';

$craft_id = isset($_GET['craft']) ? intval($_GET['craft']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$level_filter = isset($_GET['level']) ? $_GET['level'] : null;

$page_title = "جميع الكورسات";
$where = "status = 'active'";
$params = [];

if ($craft_id) {
    $stmt = $pdo->prepare("SELECT craft_name FROM crafts WHERE craft_id = ?");
    $stmt->execute([$craft_id]);
    $craft = $stmt->fetch();
    $page_title = "كورسات " . $craft['craft_name'];
    $where .= " AND craft_id = ?";
    $params[] = $craft_id;
}

if ($search) {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $page_title = "نتائج البحث عن: " . htmlspecialchars($search);
}

if ($level_filter) {
    $where .= " AND level = ?";
    $params[] = $level_filter;
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
    case 'price_low':
        $order_by .= "price ASC";
        break;
    case 'price_high':
        $order_by .= "price DESC";
        break;
    case 'popular':
        $order_by .= "(SELECT COUNT(*) FROM order_items WHERE course_id = courses.course_id) DESC";
        break;
    default:
        $order_by .= "created_at DESC";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// جلب الكورسات
$sql = "SELECT * FROM courses WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// جلب العدد الإجمالي للكورسات
$count_sql = "SELECT COUNT(*) FROM courses WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_courses = $stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// جلب جميع الحرف للفلترة
$crafts = get_all_crafts();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1><?= $page_title ?></h1>
                    <p class="lead"><?= $total_courses ?> كورس متاح</p>
                </div>
                <div class="col-md-6">
                    <form id="search-form" class="d-flex mb-3">
                        <input type="text" id="search-query" class="form-control" placeholder="ابحث عن كورسات..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary me-2">بحث</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>تصفية النتائج</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">الحرفة اليدوية</h6>
                        <select id="craft-filter" class="form-select mb-4">
                            <option value="">جميع الحرف</option>
                            <?php foreach ($crafts as $craft): ?>
                            <option value="<?= $craft['craft_id'] ?>" <?= $craft_id == $craft['craft_id'] ? 'selected' : '' ?>>
                                <?= $craft['craft_name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <h6 class="mb-3">مستوى الصعوبة</h6>
                        <select id="level-filter" class="form-select mb-4">
                            <option value="">جميع المستويات</option>
                            <option value="beginner" <?= $level_filter == 'beginner' ? 'selected' : '' ?>>مبتدئ</option>
                            <option value="intermediate" <?= $level_filter == 'intermediate' ? 'selected' : '' ?>>متوسط</option>
                            <option value="advanced" <?= $level_filter == 'advanced' ? 'selected' : '' ?>>متقدم</option>
                        </select>
                        
                        <h6 class="mb-3">ترتيب حسب</h6>
                        <select id="sort-filter" class="form-select" onchange="window.location.href=this.value">
                            <option value="?sort=newest<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>" <?= $sort == 'newest' ? 'selected' : '' ?>>الأحدث</option>
                            <option value="?sort=oldest<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>" <?= $sort == 'oldest' ? 'selected' : '' ?>>الأقدم</option>
                            <option value="?sort=price_low<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>" <?= $sort == 'price_low' ? 'selected' : '' ?>>السعر من الأقل للأعلى</option>
                            <option value="?sort=price_high<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>" <?= $sort == 'price_high' ? 'selected' : '' ?>>السعر من الأعلى للأقل</option>
                            <option value="?sort=popular<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>" <?= $sort == 'popular' ? 'selected' : '' ?>>الأكثر شعبية</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="row">
                    <?php if (empty($courses)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">لا توجد كورسات متاحة</div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card course-card h-100">
                                <img src="<?= $course['cover_image'] ?>" class="card-img-top" alt="<?= $course['title'] ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $course['title'] ?></h5>
                                    <p class="card-text"><?= substr($course['description'], 0, 100) ?>...</p>
                                    
                                    <div class="course-meta mb-3">
                                        <span class="badge bg-secondary me-2"><?= get_level_name($course['level']) ?></span>
                                        <span class="text-muted"><i class="bi bi-clock"></i> <?= $course['duration'] ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price"><?= number_format($course['price'], 2) ?> ر.س</span>
                                        <a href="course-details.php?id=<?= $course['course_id'] ?>" class="btn btn-sm btn-primary">التفاصيل</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $level_filter ? '&level='.$level_filter : '' ?>&sort=<?= $sort ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>