<?php
require_once 'includes/config.php';

$page_title = "الحرف اليدوية";
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
            <h1 class="text-center"><?= $page_title ?></h1>
            <p class="lead text-center">استكشف مختلف أنواع الحرف اليدوية وتعلم أسرارها</p>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row">
            <?php foreach ($crafts as $craft): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card craft-card h-100">
                    <img src="<?= $craft['image'] ?>" class="card-img-top" alt="<?= $craft['craft_name'] ?>">
                    <div class="card-body">
                        <h3 class="card-title"><?= $craft['craft_name'] ?></h3>
                        <p class="card-text"><?= $craft['description'] ?></p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between">
                            <a href="products.php?craft=<?= $craft['craft_id'] ?>" class="btn btn-outline-primary">تصفح المنتجات</a>
                            <a href="courses.php?craft=<?= $craft['craft_id'] ?>" class="btn btn-outline-success">تعلم الحرفة</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>