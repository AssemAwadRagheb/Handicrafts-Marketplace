<?php
require_once 'includes/config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

// جلب الحرفيين المقبولين
$craftsmen = $pdo->query("SELECT user_id, full_name, profile_image FROM users WHERE user_type = 'craftsman' AND status = 'active' ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $craftsman_id = intval($_POST['craftsman_id']);
    $booking_date = trim($_POST['booking_date']);
    $time_slot = trim($_POST['time_slot']);
    $purpose = trim($_POST['purpose']);
    
    // التحقق من الصحة
    if (empty($craftsman_id)) {
        $errors[] = 'الحرفي مطلوب';
    }
    
    if (empty($booking_date)) {
        $errors[] = 'تاريخ الحجز مطلوب';
    } elseif (strtotime($booking_date) < strtotime('today')) {
        $errors[] = 'لا يمكن حجز موعد في تاريخ قديم';
    }
    
    if (empty($time_slot)) {
        $errors[] = 'ميعاد الحجز مطلوب';
    }
    
    if (empty($purpose)) {
        $errors[] = 'الغرض من الزيارة مطلوب';
    } elseif (strlen($purpose) < 10) {
        $errors[] = 'الغرض من الزيارة يجب أن يكون على الأقل 10 أحرف';
    }
    
    // التحقق من توفر الموعد
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM exhibition_bookings 
                              WHERE craftsman_id = ? AND booking_date = ? AND time_slot = ? AND status != 'cancelled'");
        $stmt->execute([$craftsman_id, $booking_date, $time_slot]);
        $existing_bookings = $stmt->fetchColumn();
        
        if ($existing_bookings > 0) {
            $errors[] = 'هذا الموعد محجوز بالفعل، يرجى اختيار موعد آخر';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO exhibition_bookings (customer_id, craftsman_id, booking_date, time_slot, purpose, status) 
                              VALUES (?, ?, ?, ?, ?, 'pending')");
        
        if ($stmt->execute([
            $_SESSION['user_id'],
            $craftsman_id,
            $booking_date,
            $time_slot,
            $purpose
        ])) {
            $success = true;
            
            // إرسال إشعار للحرفي
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                          VALUES (?, ?, ?, 'booking')")
               ->execute([
                   $craftsman_id,
                   'حجز موعد جديد',
                   'لديك حجز موعد جديد في المعرض بتاريخ ' . $booking_date . ' في ' . $time_slot,
               ]);
        } else {
            $errors[] = 'حدث خطأ أثناء حجز الموعد، يرجى المحاولة مرة أخرى';
        }
    }
}

// الأوقات المتاحة
$time_slots = [
    '10:00 ص - 12:00 م',
    '12:00 م - 2:00 م',
    '2:00 م - 4:00 م',
    '4:00 م - 6:00 م'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز موعد في المعرض - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header py-5">
        <div class="container">
            <h1 class="text-center">حجز موعد في المعرض</h1>
            <p class="lead text-center">احجز موعداً لمقابلة الحرفيين ورؤية منتجاتهم عن قرب</p>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    تم حجز موعدك بنجاح! سيتواصل معك الحرفي لتأكيد الحجز.
                </div>
                <?php else: ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="craftsman_id" class="form-label">الحرفي</label>
                        <select class="form-select" id="craftsman_id" name="craftsman_id" required>
                            <option value="">اختر الحرفي...</option>
                            <?php foreach ($craftsmen as $craftsman): ?>
                            <option value="<?= $craftsman['user_id'] ?>" <?= isset($_POST['craftsman_id']) && $_POST['craftsman_id'] == $craftsman['user_id'] ? 'selected' : '' ?>>
                                <?= $craftsman['full_name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="booking_date" class="form-label">تاريخ الحجز</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="time_slot" class="form-label">ميعاد الحجز</label>
                            <select class="form-select" id="time_slot" name="time_slot" required>
                                <option value="">اختر الميعاد...</option>
                                <?php foreach ($time_slots as $slot): ?>
                                <option value="<?= $slot ?>" <?= isset($_POST['time_slot']) && $_POST['time_slot'] == $slot ? 'selected' : '' ?>>
                                    <?= $slot ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="purpose" class="form-label">الغرض من الزيارة</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="4" required><?= isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : '' ?></textarea>
                        <div class="form-text">اذكر سبب زيارتك وما الذي ترغب في مناقشته مع الحرفي</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">حجز الموعد</button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // تعطيل أيام الجمعة (عطلة نهاية الأسبوع)
    document.getElementById('booking_date').addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const day = selectedDate.getDay();
        
        if (day === 5) { // 5 هو يوم الجمعة
            alert('المعرض مغلق يوم الجمعة، يرجى اختيار يوم آخر');
            this.value = '';
        }
    });
    </script>
</body>
</html>