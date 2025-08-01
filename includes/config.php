<?php
session_start();

// إعدادات الموقع
define('SITE_NAME', 'HandyCraft');
define('SITE_URL', 'http://localhost/handycraft');
define('ADMIN_EMAIL', 'admin@handycraft.com');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'handycraft');

// إعدادات أخرى
define('UPLOAD_DIR', 'assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// السماح بأنواع الملفات المسموح بها
$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
$allowed_video_types = ['video/mp4', 'video/webm'];

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// تضمين الدوال المساعدة
require_once 'functions.php';
?>