<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_craftsman()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// جلب الحرف الخاصة بالحرفي
$crafts = $pdo->query("SELECT * FROM crafts ORDER BY craft_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $craft_id = intval($_POST['craft_id']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $status = $_POST['status'];
    
    // التحقق من الصحة
    if (empty($product_name)) {
        $errors[] = 'اسم المنتج مطلوب';
    }
    
    if (empty($craft_id)) {
        $errors[] = 'الحرفة مطلوبة';
    }
    
    if (empty($description)) {
        $errors[] = 'وصف المنتج مطلوب';
    }
    
    if ($price <= 0) {
        $errors[] = 'السعر يجب أن يكون أكبر من الصفر';
    }
    
    if ($quantity < 0) {
        $errors[] = 'الكمية يجب أن تكون عدد صحيح موجب';
    }
    
    // معالجة الصور
    $images = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $result = upload_file([
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ], 'image');
                
                if ($result['success']) {
                    $images[] = $result['path'];
                } else {
                    $errors[] = 'حدث خطأ أثناء رفع الصورة: ' . $result['message'];
                }
            }
        }
    }
    
    if (count($images) < 1) {
        $errors[] = 'يجب رفع صورة واحدة على الأقل للمنتج';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (craftsman_id, craft_id, product_name, description, price, quantity, images, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $images_json = json_encode($images);
        
        if ($stmt->execute([
            $user_id,
            $craft_id,
            $product_name,
            $description,
            $price,
            $quantity,
            $images_json,
            $status
        ])) {
            $success = true;
            $_SESSION['success'] = 'تم إضافة المنتج بنجاح';
            header('Location: products.php');
            exit;
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة المنتج، يرجى المحاولة مرة أخرى';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد - <?= SITE_NAME ?></title>
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
                    <h1 class="h2">إضافة منتج جديد</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="products.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> رجوع
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>معلومات المنتج</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">اسم المنتج</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="craft_id" class="form-label">الحرفة</label>
                                        <select class="form-select" id="craft_id" name="craft_id" required>
                                            <option value="">اختر الحرفة...</option>
                                            <?php foreach ($crafts as $craft): ?>
                                            <option value="<?= $craft['craft_id'] ?>"><?= $craft['craft_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">وصف المنتج</label>
                                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>معرض الصور</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="images" class="form-label">صور المنتج</label>
                                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                                        <div class="form-text">يمكنك رفع أكثر من صورة (الحد الأقصى 5 صور)</div>
                                    </div>
                                    
                                    <div id="image-preview" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>التسعير والمخزون</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">السعر (ر.س)</label>
                                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">الكمية المتاحة</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">حالة المنتج</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="active" selected>نشط</option>
                                            <option value="inactive">غير نشط</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100">حفظ المنتج</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // معاينة الصور قبل الرفع
    document.getElementById('images').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        
        if (this.files.length > 5) {
            alert('الحد الأقصى للصور هو 5 صور');
            this.value = '';
            return;
        }
        
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            
            if (file.type.match('image.*')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.maxHeight = '100px';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        }
    });
    </script>
</body>
</html>