<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

// الفلترة والبحث
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where = '1=1';
$params = [];

if ($status) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if ($payment_status) {
    $where .= " AND o.payment_status = ?";
    $params[] = $payment_status;
}

if ($search) {
    $where .= " AND (o.order_id = ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where .= " AND o.order_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where .= " AND o.order_date <= ?";
    $params[] = $date_to . ' 23:59:59';
}

// التصنيف
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "ORDER BY ";
switch ($sort) {
    case 'newest':
        $order_by .= "o.order_date DESC";
        break;
    case 'oldest':
        $order_by .= "o.order_date ASC";
        break;
    case 'price_low':
        $order_by .= "o.total_amount ASC";
        break;
    case 'price_high':
        $order_by .= "o.total_amount DESC";
        break;
    default:
        $order_by .= "o.order_date DESC";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// جلب الطلبات
$sql = "SELECT o.*, u.full_name AS customer_name, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS items_count 
        FROM orders o 
        JOIN users u ON o.customer_id = u.user_id 
        WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// جلب العدد الإجمالي للطلبات
$count_sql = "SELECT COUNT(*) FROM orders o WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// معالجة تغيير الحالة
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = $_GET['status'];
    
    $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")
        ->execute([$new_status, $order_id]);
    
    $_SESSION['success'] = 'تم تحديث حالة الطلب بنجاح';
    header('Location: orders.php');
    exit;
}

// معالجة تغيير حالة الدفع
if (isset($_GET['update_payment_status'])) {
    $order_id = intval($_GET['order_id']);
    $new_status = $_GET['payment_status'];
    
    $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?")
        ->execute([$new_status, $order_id]);
    
    $_SESSION['success'] = 'تم تحديث حالة الدفع بنجاح';
    header('Location: orders.php');
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
    <title>إدارة الطلبات - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">إدارة الطلبات</h1>
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
                                <label for="status" class="form-label">حالة الطلب</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>قيد المعالجة</option>
                                    <option value="shipped" <?= $status == 'shipped' ? 'selected' : '' ?>>تم الشحن</option>
                                    <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>تم التوصيل</option>
                                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>ملغى</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="payment_status" class="form-label">حالة الدفع</label>
                                <select id="payment_status" name="payment_status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="pending" <?= $payment_status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="completed" <?= $payment_status == 'completed' ? 'selected' : '' ?>>مكتمل</option>
                                    <option value="failed" <?= $payment_status == 'failed' ? 'selected' : '' ?>>فشل</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">من تاريخ</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">بحث</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث برقم الطلب أو اسم العميل">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
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
                                <th>العميل</th>
                                <th>تاريخ الطلب</th>
                                <th>عدد العناصر</th>
                                <th>المجموع</th>
                                <th>حالة الطلب</th>
                                <th>حالة الدفع</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center">لا توجد نتائج</td>
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
                                        <span class="badge bg-<?= 
                                            $order['payment_status'] == 'completed' ? 'success' : 
                                            ($order['payment_status'] == 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= $order['payment_status'] == 'completed' ? 'مكتمل' : 
                                               ($order['payment_status'] == 'pending' ? 'قيد الانتظار' : 'فشل') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                حالة الطلب
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
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                حالة الدفع
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_payment_status&payment_status=pending">قيد الانتظار</a></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_payment_status&payment_status=completed">مكتمل</a></li>
                                                <li><a class="dropdown-item" href="orders.php?order_id=<?= $order['order_id'] ?>&update_payment_status&payment_status=failed">فشل</a></li>
                                            </ul>
                                        </div>
                                        <a href="order-details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">التفاصيل</a>
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
                            <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= $status ?>&payment_status=<?= $payment_status ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&payment_status=<?= $payment_status ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= $status ?>&payment_status=<?= $payment_status ?>&search=<?= urlencode($search) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort ?>" aria-label="Next">
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