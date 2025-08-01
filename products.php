<?php
require_once 'includes/config.php';

$craft_id = isset($_GET['craft']) ? intval($_GET['craft']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$price_filter = isset($_GET['price']) ? $_GET['price'] : null;

$page_title = "جميع المنتجات";
$where = "status = 'active'";
$params = [];

if ($craft_id) {
    $stmt = $pdo->prepare("SELECT craft_name FROM crafts WHERE craft_id = ?");
    $stmt->execute([$craft_id]);
    $craft = $stmt->fetch();
    $page_title = "منتجات " . $craft['craft_name'];
    $where .= " AND craft_id = ?";
    $params[] = $craft_id;
}

if ($search) {
    $where .= " AND (product_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $page_title = "نتائج البحث عن: " . htmlspecialchars($search);
}

if ($price_filter) {
    switch ($price_filter) {
        case 'under50':
            $where .= " AND price < 50";
            break;
        case '50to100':
            $where .= " AND price BETWEEN 50 AND 100";
            break;
        case '100to200':
            $where .= " AND price BETWEEN 100 AND 200";
            break;
        case 'over200':
            $where .= " AND price > 200";
            break;
    }
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
        $order_by .= "(SELECT COUNT(*) FROM order_items WHERE product_id = products.product_id) DESC";
        break;
    default:
        $order_by .= "created_at DESC";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// جلب المنتجات
$sql = "SELECT * FROM products WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// جلب العدد الإجمالي للمنتجات
$count_sql = "SELECT COUNT(*) FROM products WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

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
                    <p class="lead"><?= $total_products ?> منتج متاح</p>
                </div>
                <div class="col-md-6">
                    <form id="search-form" class="d-flex mb-3">
                        <input type="text" id="search-query" class="form-control" placeholder="ابحث عن منتجات..." value="<?= htmlspecialchars($search) ?>">
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
                        
                        <h6 class="mb-3">نطاق السعر</h6>
                        <select id="price-filter" class="form-select mb-4">
                            <option value="">جميع الأسعار</option>
                            <option value="under50" <?= $price_filter == 'under50' ? 'selected' : '' ?>>أقل من 50 ر.س</option>
                            <option value="50to100" <?= $price_filter == '50to100' ? 'selected' : '' ?>>50 - 100 ر.س</option>
                            <option value="100to200" <?= $price_filter == '100to200' ? 'selected' : '' ?>>100 - 200 ر.س</option>
                            <option value="over200" <?= $price_filter == 'over200' ? 'selected' : '' ?>>أكثر من 200 ر.س</option>
                        </select>
                        
                        <h6 class="mb-3">ترتيب حسب</h6>
                        <select id="sort-filter" class="form-select" onchange="window.location.href=this.value">
                            <option value="?sort=newest<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" <?= $sort == 'newest' ? 'selected' : '' ?>>الأحدث</option>
                            <option value="?sort=oldest<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" <?= $sort == 'oldest' ? 'selected' : '' ?>>الأقدم</option>
                            <option value="?sort=price_low<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" <?= $sort == 'price_low' ? 'selected' : '' ?>>السعر من الأقل للأعلى</option>
                            <option value="?sort=price_high<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" <?= $sort == 'price_high' ? 'selected' : '' ?>>السعر من الأعلى للأقل</option>
                            <option value="?sort=popular<?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" <?= $sort == 'popular' ? 'selected' : '' ?>>الأكثر شعبية</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="row">
                    <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">لا توجد منتجات متاحة</div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): 
                            $images = json_decode($product['images'], true);
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <img src="<?= $images[0] ?>" class="card-img-top" alt="<?= $product['product_name'] ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $product['product_name'] ?></h5>
                                    <p class="card-text"><?= substr($product['description'], 0, 100) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price"><?= number_format($product['price'], 2) ?> ر.س</span>
                                        <a href="product-details.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-primary">التفاصيل</a>
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
                            <a class="page-link" href="?page=<?= $page-1 ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $price_filter ? '&price='.$price_filter : '' ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $price_filter ? '&price='.$price_filter : '' ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?><?= $craft_id ? '&craft='.$craft_id : '' ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $price_filter ? '&price='.$price_filter : '' ?>&sort=<?= $sort ?>" aria-label="Next">
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