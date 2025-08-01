<?php
require_once 'includes/config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $craft_id = intval($_POST['craft_id']);
    $description = trim($_POST['description']);
    $budget = isset($_POST['budget']) ? floatval($_POST['budget']) : null;
    $deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : null;
    
    // التحقق من الصحة
    if (empty($title)) {
        $errors[] = 'عنوان الطلب مطلوب';
    }
    
    if (empty($craft_id)) {
        $errors[] = 'الحرفة مطلوبة';
    }
    
    if (empty($description)) {
        $errors[] = 'وصف الطلب مطلوب';
    } elseif (strlen($description) < 20) {
        $errors[] = 'وصف الطلب يجب أن يكون على الأقل 20 حرفاً';
    }
    
    // معالجة الصور
    $reference_images = [];
    if (!empty($_FILES['reference_images']['name'][0])) {
        foreach ($_FILES['reference_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['reference_images']['error'][$key] === UPLOAD_ERR_OK) {
                $result = upload_file([
                    'name' => $_FILES['reference_images']['name'][$key],
                    'type' => $_FILES['reference_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['reference_images']['error'][$key],
                    'size' => $_FILES['reference_images']['size'][$key]
                ], 'image');
                
                if ($result['success']) {
                    $reference_images[] = $result['path'];
                } else {
                    $errors[] = 'حدث خطأ أثناء رفع الصورة: ' . $result['message'];
                }
            }
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO custom_requests (customer_id, craft_id, title, description, reference_images, budget, deadline, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $images_json = !empty($reference_images) ? json_encode($reference_images) : null;
        
        if ($stmt->execute([
            $_SESSION['user_id'],
            $craft_id,
            $title,
            $description,
            $images_json,
            $budget,
            $deadline
        ])) {
            $success = true;
        } else {
            $errors[] = 'حدث خطأ أثناء إرسال الطلب، يرجى المحاولة مرة أخرى';
        }
    }
}

// جلب جميع الحرف
$crafts = get_all_crafts();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب منتج مخصص - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header py-5">
        <div class="container">
            <h1 class="text-center">طلب منتج مخصص</h1>
            <p class="lead text-center">اطلب منتجاً مصنوعاً خصيصاً لك حسب مواصفاتك</p>
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
                    تم إرسال طلبك بنجاح! سيقوم الحرفيون بالاطلاع عليه وسيتم إعلامك بأي تحديثات.
                </div>
                <?php else: ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="title" class="form-label">عنوان الطلب</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="form-text">مثال: أريد سجادة صلاة مطرزة بالخيوط الذهبية</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="craft_id" class="form-label">نوع الحرفة</label>
                        <select class="form-select" id="craft_id" name="craft_id" required>
                            <option value="">اختر الحرفة...</option>
                            <?php foreach ($crafts as $craft): ?>
                            <option value="<?= $craft['craft_id'] ?>"><?= $craft['craft_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">وصف تفصيلي للطلب</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                        <div class="form-text">صف منتجك المطلوب بالتفصيل (الأبعاد، الألوان، المواد، التصميم، إلخ)</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="budget" class="form-label">الميزانية المتوقعة (اختياري)</label>
                            <input type="number" class="form-control" id="budget" name="budget" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="deadline" class="form-label">الموعد النهائي (اختياري)</label>
                            <input type="date" class="form-control" id="deadline" name="deadline">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="reference_images" class="form-label">صور مرجعية (اختياري)</label>
                        <input type="file" class="form-control" id="reference_images" name="reference_images[]" multiple accept="image/*">
                        <div class="form-text">يمكنك رفع صور توضح ما تريده (الحد الأقصى 5 صور)</div>
                        
                        <div id="image-preview" class="d-flex flex-wrap mt-3"></div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">إرسال الطلب</button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // معاينة الصور قبل الرفع
    document.getElementById('reference_images').addEventListener('change', function(e) {
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
                    img.className = 'img-thumbnail me-2 mb-2';
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