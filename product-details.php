<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = intval($_GET['id']);

// جلب بيانات المنتج
$stmt = $pdo->prepare("SELECT p.*, u.full_name AS craftsman_name, u.profile_image AS craftsman_image, c.craft_name 
                      FROM products p 
                      JOIN users u ON p.craftsman_id = u.user_id 
                      JOIN crafts c ON p.craft_id = c.craft_id 
                      WHERE p.product_id = ? AND p.status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$images = json_decode($product['images'], true);

// جلب منتجات مشابهة
$similar_products = $pdo->prepare("SELECT * FROM products 
                                  WHERE craft_id = ? AND product_id != ? AND status = 'active' 
                                  ORDER BY RAND() LIMIT 4")
                       ->execute([$product['craft_id'], $product_id])
                       ->fetchAll();

// زيادة عدد المشاهدات
$pdo->prepare("UPDATE products SET views = views + 1 WHERE product_id = ?")
    ->execute([$product_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product['product_name'] ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-md-6">
                <div class="product-images">
                    <img src="<?= $images[0] ?>" class="img-fluid main-image rounded shadow" alt="<?= $product['product_name'] ?>">
                    
                    <div class="d-flex mt-3">
                        <?php foreach ($images as $image): ?>
                        <img src="<?= $image ?>" class="thumbnail img-thumbnail" alt="<?= $product['product_name'] ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="product-details">
                    <h1><?= $product['product_name'] ?></h1>
                    
                    <div class="d-flex align-items-center mb-3">
                        <span class="product-price"><?= number_format($product['price'], 2) ?> ر.س</span>
                        <span class="badge bg-success ms-3">متوفر</span>
                    </div>
                    
                    <div class="mb-4">
                        <h5>حول المنتج</h5>
                        <p class="product-description"><?= nl2br($product['description']) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>الحرفة</h5>
                        <p><a href="products.php?craft=<?= $product['craft_id'] ?>"><?= $product['craft_name'] ?></a></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>الحرفي</h5>
                        <div class="d-flex align-items-center">
                            <img src="<?= $product['craftsman_image'] ?: 'assets/images/default-profile.jpg' ?>" class="rounded-circle me-3" width="50" height="50" alt="<?= $product['craftsman_name'] ?>">
                            <div>
                                <h6 class="mb-0"><?= $product['craftsman_name'] ?></h6>
                                <a href="craftsman-profile.php?id=<?= $product['craftsman_id'] ?>" class="text-muted">عرض الملف الشخصي</a>
                            </div>
                        </div>
                    </div>
                    
                    <form class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        
                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-auto">
                                <label for="quantity" class="col-form-label">الكمية</label>
                            </div>
                            <div class="col-auto">
                                <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" max="<?= $product['quantity'] ?>">
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-lg add-to-cart" data-product-id="<?= $product['product_id'] ?>">
                            <i class="bi bi-cart-plus"></i> أضف إلى السلة
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <hr class="my-5">
        
        <div class="row">
            <div class="col-12">
                <h3 class="mb-4">منتجات مشابهة</h3>
                
                <div class="row">
                    <?php foreach ($similar_products as $similar): 
                        $similar_images = json_decode($similar['images'], true);
                    ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="<?= $similar_images[0] ?>" class="card-img-top" alt="<?= $similar['product_name'] ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= $similar['product_name'] ?></h5>
                                <p class="card-text"><?= substr($similar['description'], 0, 50) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price"><?= number_format($similar['price'], 2) ?> ر.س</span>
                                    <a href="product-details.php?id=<?= $similar['product_id'] ?>" class="btn btn-sm btn-primary">التفاصيل</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>