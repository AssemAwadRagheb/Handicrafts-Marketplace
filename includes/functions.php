<?php
// دالة تسجيل الدخول
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['logged_in'] = true;
        
        // تحديث آخر زيارة
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
            ->execute([$user['user_id']]);
            
        return true;
    }
    
    return false;
}

// دالة تسجيل الخروج
function logout() {
    session_unset();
    session_destroy();
}

// دالة التحقق من تسجيل الدخول
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// دالة التحقق من نوع المستخدم
function is_admin() {
    return is_logged_in() && $_SESSION['user_type'] === 'admin';
}

function is_craftsman() {
    return is_logged_in() && $_SESSION['user_type'] === 'craftsman';
}

function is_customer() {
    return is_logged_in() && $_SESSION['user_type'] === 'customer';
}

// دالة رفع الملفات
function upload_file($file, $type = 'image') {
    global $allowed_image_types, $allowed_video_types;
    
    $target_dir = UPLOAD_DIR;
    $target_file = $target_dir . basename($file["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // التحقق من حجم الملف
    if ($file["size"] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File is too large.'];
    }
    
    // التحقق من نوع الملف
    if ($type === 'image' && !in_array($file["type"], $allowed_image_types)) {
        return ['success' => false, 'message' => 'Only JPG, PNG & GIF files are allowed.'];
    }
    
    if ($type === 'video' && !in_array($file["type"], $allowed_video_types)) {
        return ['success' => false, 'message' => 'Only MP4 & WebM videos are allowed.'];
    }
    
    // إنشاء اسم فريد للملف
    $new_filename = uniqid() . '.' . $file_type;
    $target_path = $target_dir . $new_filename;
    
    // رفع الملف
    if (move_uploaded_file($file["tmp_name"], $target_path)) {
        return ['success' => true, 'path' => $target_path];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// دالة جلب جميع الحرف
function get_all_crafts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM crafts ORDER BY craft_name");
    return $stmt->fetchAll();
}

// دالة جلب المنتجات حسب الحرفة
function get_products_by_craft($craft_id, $limit = null) {
    global $pdo;
    $sql = "SELECT * FROM products WHERE craft_id = ? AND status = 'active' ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$craft_id]);
    return $stmt->fetchAll();
}

// دالة جلب الكورسات حسب الحرفة
function get_courses_by_craft($craft_id, $limit = null) {
    global $pdo;
    $sql = "SELECT * FROM courses WHERE craft_id = ? AND status = 'active' ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$craft_id]);
    return $stmt->fetchAll();
}
?>