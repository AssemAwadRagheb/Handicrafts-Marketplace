<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// معالجة حذف المنتج
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // التحقق من أن المنتج يخص الحرفي
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND craftsman_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
        $_SESSION['success'] = 'تم حذف المنتج بنجاح';
    } else {
        $_SESSION['error'] = 'المنتج غير موجود أو لا تملك صلاحية حذفه';
    }
    
    header('Location: products.php');
    exit;
}

// جلب منتجات الحرفي
$stmt = $pdo->prepare("SELECT * FROM products WHERE craftsman_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

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
    <title>منتجاتي - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">منتجاتي</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-product.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> إضافة منتج جديد
                        </a>
                    </div>
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
                                <th>الصورة</th>
                                <th>اسم المنتج</th>
                                <th>السعر</th>
                                <th>الكمية</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center">لا توجد منتجات حتى الآن</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): 
                                    $images = json_decode($product['images'], true);
                                ?>
                                <tr>
                                    <td><?= $product['product_id'] ?></td>
                                    <td><img src="<?= $images[0] ?>" width="50" height="50" class="img-thumbnail" alt=""></td>
                                    <td><?= $product['product_name'] ?></td>
                                    <td><?= number_format($product['price'], 2) ?> ر.س</td>
                                    <td><?= $product['quantity'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $product['status'] == 'active' ? 'success' : 
                                            ($product['status'] == 'sold_out' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= $product['status'] == 'active' ? 'نشط' : 
                                               ($product['status'] == 'sold_out' ? 'نفذت الكمية' : 'غير نشط') ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y/m/d', strtotime($product['created_at'])) ?></td>
                                    <td>
                                        <a href="edit-product.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="products.php?delete=<?= $product['product_id'] ?>" class="btn btn-sm btn-outline-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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