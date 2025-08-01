<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// معالجة تغيير حالة الطلب
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['order_id']);
    $status = $_GET['status'];
    
    // التحقق من أن الطلب يحتوي على منتجات الحرفي
    $stmt = $pdo->prepare("SELECT o.* FROM orders o 
                          JOIN order_items oi ON o.order_id = oi.order_id 
                          JOIN products p ON oi.product_id = p.product_id 
                          WHERE o.order_id = ? AND p.craftsman_id = ? 
                          GROUP BY o.order_id");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")
            ->execute([$status, $order_id]);
        
        $_SESSION['success'] = 'تم تحديث حالة الطلب بنجاح';
        
        // إرسال إشعار للعميل
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                      VALUES (?, ?, ?, 'order')")
           ->execute([
               $order['customer_id'],
               'تحديث حالة الطلب',
               'تم تحديث حالة طلبك #' . $order_id . ' إلى: ' . $status,
           ]);
    } else {
        $_SESSION['error'] = 'الطلب غير موجود أو لا تملك صلاحية التعديل عليه';
    }
    
    header('Location: orders.php');
    exit;
}

// جلب طلبات الحرفي
$stmt = $pdo->prepare("SELECT o.order_id, o.order_date, o.total_amount, o.status, 
                       u.full_name AS customer_name, 
                       COUNT(oi.item_id) AS items_count 
                       FROM orders o 
                       JOIN order_items oi ON o.order_id = oi.order_id 
                       JOIN products p ON oi.product_id = p.product_id 
                       JOIN users u ON o.customer_id = u.user_id 
                       WHERE p.craftsman_id = ? 
                       GROUP BY o.order_id 
                       ORDER BY o.order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

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
    <title>طلباتي - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">طلباتي</h1>
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
                                <th>العميل</th>
                                <th>تاريخ الطلب</th>
                                <th>عدد المنتجات</th>
                                <th>المجموع</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">لا توجد طلبات حتى الآن</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['order_id'] ?></td>
                                    <td><?= $order['customer_name'] ?></td>
                                    <td><?= date('Y/m/d', strtotime($order['order_date'])) ?></td>
                                    <td><?= $order['items_count'] ?></td>
                                    <td><?= number_format($order['total_amount'], 2) ?> ر.س</td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['status'] == 'delivered' ? 'success' : 
                                            ($order['status'] == 'shipped' ? 'info' : 
                                            ($order['status'] == 'processing' ? 'primary' : 
                                            ($order['status'] == 'pending' ? 'warning' : 'danger'))) 
                                        ?>">
                                            <?= $order['status'] == 'delivered' ? 'تم التوصيل' : 
                                               ($order['status'] == 'shipped' ? 'تم الشحن' : 
                                               ($order['status'] == 'processing' ? 'قيد المعالجة' : 
                                               ($order['status'] == 'pending' ? 'قيد الانتظار' : 'ملغى'))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                تغيير الحالة
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_status&status=pending">قيد الانتظار</a></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_status&status=processing">قيد المعالجة</a></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_status&status=shipped">تم الشحن</a></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_status&status=delivered">تم التوصيل</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_status&status=cancelled">إلغاء</a></li>
                                            </ul>
                                        </div>
                                        <a href="order-details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary ms-2">التفاصيل</a>
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