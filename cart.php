<?php
require_once 'includes/config.php';

if (!is_logged_in()) {
    header('Location: login.php?redirect=cart.php');
    exit;
}

// معالجة إزالة العناصر من السلة
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = 'تمت إزالة المنتج من السلة';
    }
}

// معالجة تحديث الكميات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = max(1, intval($quantity));
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }
    }
    $_SESSION['success'] = 'تم تحديث السلة بنجاح';
}

// جلب تفاصيل المنتجات في السلة
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['product_id']]['quantity'];
        $total = $product['price'] * $quantity;
        
        $cart_items[] = [
            'product_id' => $product['product_id'],
            'name' => $product['product_name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $total,
            'image' => json_decode($product['images'], true)[0]
        ];
        
        $subtotal += $total;
    }
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
    <title>عربة التسوق - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header py-5">
        <div class="container">
            <h1 class="text-center">عربة التسوق</h1>
        </div>
    </div>
    
    <div class="container py-5">
        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <h3 class="mb-4">سلة التسوق فارغة</h3>
            <p class="lead mb-4">لم تقم بإضافة أي منتجات إلى سلة التسوق بعد</p>
            <a href="products.php" class="btn btn-primary btn-lg">تصفح المنتجات</a>
        </div>
        <?php else: ?>
        <form method="POST">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>الكمية</th>
                            <th>المجموع</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= $item['image'] ?>" class="img-thumbnail me-3" width="80" alt="<?= $item['name'] ?>">
                                    <h6 class="mb-0"><?= $item['name'] ?></h6>
                                </div>
                            </td>
                            <td><?= number_format($item['price'], 2) ?> ر.س</td>
                            <td>
                                <input type="number" name="quantity[<?= $item['product_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 80px;">
                            </td>
                            <td><?= number_format($item['total'], 2) ?> ر.س</td>
                            <td>
                                <a href="cart.php?remove=<?= $item['product_id'] ?>" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <button type="submit" name="update_cart" class="btn btn-outline-secondary">تحديث السلة</button>
                    <a href="products.php" class="btn btn-outline-primary">مواصلة التسوق</a>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">ملخص الطلب</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>المجموع الفرعي:</span>
                                <span><?= number_format($subtotal, 2) ?> ر.س</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>التوصيل:</span>
                                <span>سيتم حسابها لاحقاً</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>المجموع الكلي:</span>
                                <span><?= number_format($subtotal, 2) ?> ر.س</span>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100 mt-3">إتمام الشراء</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>