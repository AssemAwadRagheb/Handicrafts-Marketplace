<?php
require_once 'includes/config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $user_type = trim($_POST['user_type']);
    $phone = trim($_POST['phone']);
    
    // التحقق من الصحة
    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (strlen($username) < 4) {
        $errors[] = 'اسم المستخدم يجب أن يكون على الأقل 4 أحرف';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'اسم المستخدم موجود بالفعل';
        }
    }
    
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'البريد الإلكتروني موجود بالفعل';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون على الأقل 6 أحرف';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'كلمة المرور غير متطابقة';
    }
    
    if (empty($user_type)) {
        $errors[] = 'نوع المستخدم مطلوب';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = $user_type === 'customer' ? 'active' : 'pending';
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed_password, $email, $full_name, $phone, $user_type, $status])) {
            $success = true;
            
            // تسجيل الدخول تلقائياً إذا كان عميلاً
            if ($user_type === 'customer') {
                login($username, $password);
                header('Location: index.php');
                exit;
            }
        } else {
            $errors[] = 'حدث خطأ أثناء التسجيل، يرجى المحاولة مرة أخرى';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="card-title text-center mb-4">تسجيل حساب جديد</h2>
                        
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
                            تم التسجيل بنجاح! <?= $user_type === 'craftsman' ? 'سيتم مراجعة حسابك من قبل الإدارة وتفعيله قريباً.' : 'يمكنك الآن تسجيل الدخول.' ?>
                        </div>
                        <?php else: ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">الاسم الكامل</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">اسم المستخدم</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">كلمة المرور</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirm" class="form-label">تأكيد كلمة المرور</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">نوع الحساب</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="customer" value="customer" checked>
                                    <label class="form-check-label" for="customer">
                                        عميل (لشراء المنتجات والكورسات)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="craftsman" value="craftsman">
                                    <label class="form-check-label" for="craftsman">
                                        حرفي (لبيع المنتجات وعرض الكورسات)
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">تسجيل الحساب</button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول الآن</a></p>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>