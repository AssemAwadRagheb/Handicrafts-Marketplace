<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// معالجة تغيير حالة الحجز
if (isset($_GET['update_status'])) {
    $booking_id = intval($_GET['booking_id']);
    $status = $_GET['status'];
    
    // التحقق من أن الحجز يخص الحرفي
    $stmt = $pdo->prepare("SELECT * FROM exhibition_bookings WHERE booking_id = ? AND craftsman_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $pdo->prepare("UPDATE exhibition_bookings SET status = ? WHERE booking_id = ?")
            ->execute([$status, $booking_id]);
        
        $_SESSION['success'] = 'تم تحديث حالة الحجز بنجاح';
        
        // إرسال إشعار للعميل
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                      VALUES (?, ?, ?, 'booking')")
           ->execute([
               $booking['customer_id'],
               'تحديث حالة الحجز',
               'تم تحديث حالة حجزك في المعرض إلى: ' . $status,
           ]);
    } else {
        $_SESSION['error'] = 'الحجز غير موجود أو لا تملك صلاحية التعديل عليه';
    }
    
    header('Location: bookings.php');
    exit;
}

// جلب حجوزات المعرض للحرفي
$stmt = $pdo->prepare("SELECT b.*, u.full_name AS customer_name, u.phone AS customer_phone 
                       FROM exhibition_bookings b 
                       JOIN users u ON b.customer_id = u.user_id 
                       WHERE b.craftsman_id = ? 
                       ORDER BY b.booking_date DESC, b.time_slot");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

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
    <title>حجوزات المعرض - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">حجوزات المعرض</h1>
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
                                <th>التاريخ</th>
                                <th>الميعاد</th>
                                <th>الغرض</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center">لا توجد حجوزات حتى الآن</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= $booking['booking_id'] ?></td>
                                    <td>
                                        <?= $booking['customer_name'] ?>
                                        <div class="text-muted small"><?= $booking['customer_phone'] ?></div>
                                    </td>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>