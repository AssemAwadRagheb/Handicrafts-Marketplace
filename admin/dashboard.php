<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

// إحصائيات لوحة التحكم
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$craftsmen_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'craftsman'")->fetchColumn();
$products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending' AND user_type = 'craftsman'")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
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
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin-sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">لوحة التحكم</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">إجمالي المستخدمين</h5>
                                <h2 class="card-text"><?= $users_count ?></h2>
                                <a href="users.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">الحرفيون</h5>
                                <h2 class="card-text"><?= $craftsmen_count ?></h2>
                                <a href="users.php?type=craftsman" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">المنتجات</h5>
                                <h2 class="card-text"><?= $products_count ?></h2>
                                <a href="products.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">الطلبات</h5>
                                <h2 class="card-text"><?= $orders_count ?></h2>
                                <a href="orders.php" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5 class="card-title">طلبات انتظار</h5>
                                <h2 class="card-text"><?= $pending_orders ?></h2>
                                <a href="orders.php?status=pending" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-secondary">
                            <div class="card-body">
                                <h5 class="card-title">حرفيون بانتظار الموافقة</h5>
                                <h2 class="card-text"><?= $pending_users ?></h2>
                                <a href="users.php?status=pending" class="text-white">عرض التفاصيل <i class="bi bi-arrow-left"></i></a>
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
                                            $orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5")->fetchAll();
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
                                <h5>آخر الحرفيون المسجلين</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>الاسم</th>
                                                <th>البريد</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $craftsmen = $pdo->query("SELECT * FROM users WHERE user_type = 'craftsman' ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                            foreach ($craftsmen as $user):
                                            ?>
                                            <tr>
                                                <td><?= $user['full_name'] ?></td>
                                                <td><?= $user['email'] ?></td>
                                                <td><span class="badge bg-<?= get_status_badge($user['status']) ?>"><?= $user['status'] ?></span></td>
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