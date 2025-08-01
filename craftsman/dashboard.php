<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// إحصائيات لوحة التحكم
$products_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE craftsman_id = ?")->execute([$user_id])->fetchColumn();
$courses_count = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE craftsman_id = ?")->execute([$user_id])->fetchColumn();
$orders_count = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.order_id = oi.order_id WHERE oi.product_id IN (SELECT product_id FROM products WHERE craftsman_id = ?)")->execute([$user_id])->fetchColumn();
$pending_orders = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.order_id = oi.order_id WHERE o.status = 'pending' AND oi.product_id IN (SELECT product_id FROM products WHERE craftsman_id = ?)")->execute([$user_id])->fetchColumn();
$bookings_count = $pdo->prepare("SELECT COUNT(*) FROM exhibition_bookings WHERE craftsman_id = ?")->execute([$user_id])->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">لوحة التحكم</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">منتجاتي</h5>
                                <h2 class="card-text"><?= $products_count ?></h2>
                                <a href="products.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">كورساتي</h5>
                                <h2 class="card-text"><?= $courses_count ?></h2>
                                <a href="courses.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">طلباتي</h5>
                                <h2 class="card-text"><?= $orders_count ?></h2>
                                <a href="orders.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">طلبات بانتظار التجهيز</h5>
                                <h2 class="card-text"><?= $pending_orders ?></h2>
                                <a href="orders.php?status=pending" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5 class="card-title">حجوزات المعرض</h5>
                                <h2 class="card-text"><?= $bookings_count ?></h2>
                                <a href="bookings.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>آخر الطلبات</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>رقم الطلب</th>
                                                <th>المجموع</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $orders = $pdo->prepare("SELECT o.* FROM orders o JOIN order_items oi ON o.order_id = oi.order_id WHERE oi.product_id IN (SELECT product_id FROM products WHERE craftsman_id = ?) ORDER BY o.order_date DESC LIMIT 5")->execute([$user_id])->fetchAll();
                                            foreach ($orders as $order):
                                            ?>
                                            <tr>
                                                <td><a href="order-details.php?id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a></td>
                                                <td><?= number_format($order['total_amount'], 2) ?> ر.س</td>
                                                <td><span class="badge bg-<?= get_status_badge($order['status']) ?>"><?= $order['status'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>آخر المنتجات</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>السعر</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $products = $pdo->prepare("SELECT * FROM products WHERE craftsman_id = ? ORDER BY created_at DESC LIMIT 5")->execute([$user_id])->fetchAll();
                                            foreach ($products as $product):
                                            ?>
                                            <tr>
                                                <td><?= $product['product_name'] ?></td>
                                                <td><?= number_format($product['price'], 2) ?> ر.س</td>
                                                <td><span class="badge bg-<?= get_status_badge($product['status']) ?>"><?= $product['status'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>