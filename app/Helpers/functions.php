<?php
/**
 * HELPERS - Các hàm tiện ích chung
 * Format tiền, ngày tháng, chuỗi
 */

/**
 * Format tiền tệ VND
 */
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

/**
 * Format ngày tháng
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Format ngày giờ
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Cắt chuỗi với dấu ...
 */
function truncateString($string, $length = 50) {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
    }
    return $string;
}

/**
 * Làm sạch input
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect trang
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Kiểm tra đã đăng nhập
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra quyền admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir = 'uploads/') {
    $targetFile = $targetDir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Kiểm tra file có phải ảnh
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Kiểm tra kích thước file (max 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Cho phép các định dạng
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    }
    
    return false;
}

/**
 * Tạo slug từ chuỗi
 */
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Tính giá sau giảm
 */
function calculateDiscountPrice($price, $discount) {
    return $price - ($price * $discount / 100);
}

/**
 * Hiển thị thông báo session
 */
function flashMessage($key) {
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Set flash message
 */
function setFlashMessage($key, $message) {
    $_SESSION[$key] = $message;
}
