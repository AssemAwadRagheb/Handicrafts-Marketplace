<?php
require_once 'includes/config.php';

if (!is_logged_in()) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// التحقق من وجود منتجات في السلة
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'سلة التسوق فارغة، لا يمكن إتمام الشراء';
    header('Location: cart.php');
    exit;
}

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// جلب تفاصيل المنتجات في السلة
$cart_items = [];
$subtotal = 0;

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
        'total' => $total
    ];
    
    $subtotal += $total;
}

// معالجة إتمام الشراء
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // التحقق من الصحة
    if (empty($name)) {
        $errors[] = 'الاسم مطلوب';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    }
    
    if (empty($phone)) {
        $errors[] = 'رقم الهاتف مطلوب';
    }
    
    if (empty($address)) {
        $errors[] = 'عنوان التوصيل مطلوب';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'طريقة الدفع مطلوبة';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // إنشاء الطلب
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, payment_method, shipping_address, notes) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $subtotal,
                $payment_method,
                $address,
                $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // إضافة عناصر الطلب
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // تحديث كمية المنتج
                $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            
            // تفريغ السلة
            unset($_SESSION['cart']);
            
            $success = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الشراء - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header py-5">
        <div class="container">
            <h1 class="text-center">إتمام الشراء</h1>
        </div>
    </div>
    
    <div class="container py-5">
        <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <h4 class="alert-heading">تمت عملية الشراء بنجاح!</h4>
            <p>شكراً لشرائك من متجرنا. سيتم توصيل طلبك في أقرب وقت ممكن.</p>
            <hr>
            <p class="mb-0">رقم طلبك: #<?= $order_id ?></p>
            <a href="profile.php" class="btn btn-primary mt-3">عرض طلباتي</a>
        </div>
        <?php else: ?>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>معلومات العميل</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">الاسم الكامل</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">عنوان التوصيل</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>طريقة الدفع</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash_on_delivery" checked>
                                <label class="form-check-label" for="cash">
                                    الدفع عند الاستلام
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank_transfer">
                                <label class="form-check-label" for="bank">
                                    التحويل البنكي
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="card" value="credit_card" disabled>
                                <label class="form-check-label" for="card">
                                    بطاقة ائتمان (غير متاح حالياً)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>ملاحظات إضافية</h5>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="ملاحظات حول الطلب (اختياري)"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>ملخص الطلب</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>المنتج</th>
                                            <th>المجموع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td><?= $item['name'] ?> × <?= $item['quantity'] ?></td>
                                            <td><?= number_format($item['total'], 2) ?> ر.س</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>المجموع الفرعي</th>
                                            <td><?= number_format($subtotal, 2) ?> ر.س</td>
                                        </tr>
                                        <tr>
                                            <th>التوصيل</th>
                                            <td>سيتم حسابها لاحقاً</td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <th>المجموع الكلي</th>
                                            <td><?= number_format($subtotal, 2) ?> ر.س</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    أوافق على <a href="terms.php">الشروط والأحكام</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 btn-lg">تأكيد الطلب</button>
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