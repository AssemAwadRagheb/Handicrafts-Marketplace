<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

// الفلترة والبحث
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '1=1';
$params = [];

if ($status) {
    $where .= " AND b.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $where .= " AND b.booking_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where .= " AND b.booking_date <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where .= " AND (u.full_name LIKE ? OR c.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// التصنيف
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "ORDER BY ";
switch ($sort) {
    case 'newest':
        $order_by .= "b.booking_date DESC, b.time_slot";
        break;
    case 'oldest':
        $order_by .= "b.booking_date ASC, b.time_slot";
        break;
    default:
        $order_by .= "b.booking_date DESC, b.time_slot";
}

// الصفحة الحالية
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// جلب الحجوزات
$sql = "SELECT b.*, u.full_name AS customer_name, u.phone AS customer_phone, 
        c.full_name AS craftsman_name 
        FROM exhibition_bookings b 
        JOIN users u ON b.customer_id = u.user_id 
        JOIN users c ON b.craftsman_id = c.user_id 
        WHERE $where $order_by LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// جلب العدد الإجمالي للحجوزات
$count_sql = "SELECT COUNT(*) FROM exhibition_bookings b 
              JOIN users u ON b.customer_id = u.user_id 
              JOIN users c ON b.craftsman_id = c.user_id 
              WHERE $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_bookings = $stmt->fetchColumn();
$total_pages = ceil($total_bookings / $per_page);

// معالجة تغيير الحالة
if (isset($_GET['update_status'])) {
    $booking_id = intval($_GET['booking_id']);
    $new_status = $_GET['status'];
    
    $pdo->prepare("UPDATE exhibition_bookings SET status = ? WHERE booking_id = ?")
        ->execute([$new_status, $booking_id]);
    
    $_SESSION['success'] = 'تم تحديث حالة الحجز بنجاح';
    header('Location: bookings.php');
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
    <title>إدارة حجوزات المعرض - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">إدارة حجوزات المعرض</h1>
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
                                <label for="status" class="form-label">الحالة</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="confirmed" <?= $status == 'confirmed' ? 'selected' : '' ?>>مؤكد</option>
                                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>ملغى</option>
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
                            <div class="col-md-4">
                                <label for="search" class="form-label">بحث</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث باسم العميل أو الحرفي">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
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
                                <th>الحرفي</th>
                                <th>التاريخ</th>
                                <th>الميعاد</th>
                                <th>الغرض</th>
                                <th>الحالة</th>
                                <th>تاريخ الحجز</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" class="text-center">لا توجد نتائج</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= $booking['booking_id'] ?></td>
                                    <td>
                                        <?= $booking['customer_name'] ?>
                                        <div class="text-muted small"><?= $booking['customer_phone'] ?></div>
                                    </td>
                                    <td><?= $booking['craftsman_name'] ?></td>
                                    <td><?= date('Y/m/d', strtotime($booking['booking_date'])) ?></td>
                                    <td><?= $booking['time_slot'] ?></td>
                                    <td><?= substr($booking['purpose'], 0, 30) ?>...</td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] == 'confirmed' ? 'success' : 
                                            ($booking['status'] == 'pending' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= $booking['status'] == 'confirmed' ? 'مؤكد' : 
                                               ($booking['status'] == 'pending' ? 'قيد الانتظار' : 'ملغى') ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y/m/d', strtotime($booking['created_at'])) ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                تغيير الحالة
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="bookings.php?booking_id=<?= $booking['booking_id'] ?>&update_status&status=confirmed">تأكيد</a></li>
                                                <li><a class="dropdown-item" href="bookings.php?booking_id=<?= $booking['booking_id'] ?>&update_status&status=pending">قيد الانتظار</a></li>
                                                <li><a class="dropdown-item" href="bookings.php?booking_id=<?= $booking['booking_id'] ?>&update_status&status=cancelled">إلغاء</a></li>
                                            </ul>
                                        </div>
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
                            <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= $status ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= $status ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" aria-label="Next">
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