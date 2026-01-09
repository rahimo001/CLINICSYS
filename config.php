<?php
/**
 * ==========================================
 * إعدادات النظام المركزية - config.php
 * ==========================================
 * جميع إعدادات قاعدة البيانات والأمان
 */

// ==================== إعدادات قاعدة البيانات ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_system');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// ==================== إعدادات التطبيق ====================
define('APP_NAME', 'نظام إدارة العيادة');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', false);
define('APP_URL', 'http://localhost/clinic_system');
define('TIMEZONE', 'Asia/Riyadh');

// ==================== إعدادات الأمان ====================
define('SECRET_KEY', 'your_secret_key_change_this_in_production');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// ==================== إعدادات البريد ====================
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your_email@gmail.com');
define('MAIL_PASS', 'your_app_password');
define('MAIL_FROM', 'clinic@example.com');
define('MAIL_FROM_NAME', 'نظام إدارة العيادة');

// ==================== إعدادات الملفات ====================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// ==================== إعدادات الصفحات ====================
define('ITEMS_PER_PAGE', 20);
define('MAX_QUERY_RESULTS', 10000);

// ==================== إعدادات السجلات ====================
define('LOG_DIR', __DIR__ . '/logs/');
define('LOG_ERRORS', true);
define('LOG_ACTIVITIES', true);

// ==================== رموز الأخطاء ====================
define('ERROR_CODES', [
    'INVALID_EMAIL' => 1001,
    'PASSWORD_WEAK' => 1002,
    'USER_EXISTS' => 1003,
    'USER_NOT_FOUND' => 1004,
    'INVALID_PASSWORD' => 1005,
    'SESSION_EXPIRED' => 1006,
    'UNAUTHORIZED' => 1007,
    'FORBIDDEN' => 1008,
    'NOT_FOUND' => 1009,
    'SERVER_ERROR' => 5000
]);

// ==================== تعيين المنطقة الزمنية ====================
date_default_timezone_set(TIMEZONE);

// ==================== بدء الجلسة ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== معالجة الأخطاء ====================
error_reporting(APP_DEBUG ? E_ALL : E_CRITICAL);
ini_set('display_errors', APP_DEBUG ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . 'php_errors.log');

// ==================== دوال مساعدة عامة ====================

/**
 * تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
}

/**
 * التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * توليد token عشوائي
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * تنظيف المدخلات
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * التحقق من قوة كلمة المرور
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $feedback = [];
    
    if (strlen($password) >= 8) {
        $strength += 20;
    } else {
        $feedback[] = 'على الأقل 8 أحرف';
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $strength += 20;
    } else {
        $feedback[] = 'أحرف صغيرة';
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 20;
    } else {
        $feedback[] = 'أحرف كبيرة';
    }
    
    if (preg_match('/[0-9]/', $password)) {
        $strength += 20;
    } else {
        $feedback[] = 'أرقام';
    }
    
    if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $strength += 20;
    } else {
        $feedback[] = 'رموز خاصة';
    }
    
    return [
        'strength' => $strength,
        'feedback' => $feedback,
        'level' => $strength >= 80 ? 'قوية' : ($strength >= 60 ? 'متوسطة' : 'ضعيفة')
    ];
}

/**
 * إرسال استجابة JSON
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * الحصول على معرّف المستخدم الحالي
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * الحصول على دور المستخدم الحالي
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * التحقق من الصلاحية
 */
function hasPermission($requiredRole) {
    $userRole = getCurrentUserRole();
    
    $roleHierarchy = [
        'admin' => 3,
        'doctor' => 2,
        'staff' => 1,
        'patient' => 0
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? -1;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? -1;
    
    return $userLevel >= $requiredLevel;
}

/**
 * إعادة التوجيه مع الحفاظ على الرسالة
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * الحصول على الرسالة والحذف
 */
function getFlashMessage() {
    $message = $_SESSION['message'] ?? null;
    $type = $_SESSION['message_type'] ?? 'success';
    
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    
    return [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * تسجيل النشاط
 */
function logActivity($userId, $action, $details, $metadata = []) {
    if (!LOG_ACTIVITIES) return;
    
    $logFile = LOG_DIR . 'activities_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logData = [
        'timestamp' => $timestamp,
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    if (!empty($metadata)) {
        $logData['metadata'] = json_encode($metadata);
    }
    
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);
}

/**
 * تسجيل الأخطاء
 */
function logError($error, $context = []) {
    if (!LOG_ERRORS) return;
    
    $logFile = LOG_DIR . 'errors_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $errorData = [
        'timestamp' => $timestamp,
        'error' => $error instanceof Exception ? $error->getMessage() : $error,
        'file' => $error instanceof Exception ? $error->getFile() : '',
        'line' => $error instanceof Exception ? $error->getLine() : '',
        'trace' => $error instanceof Exception ? $error->getTraceAsString() : '',
        'context' => $context
    ];
    
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    file_put_contents($logFile, json_encode($errorData, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

/**
 * توليد UUID
 */
function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * تنسيق التاريخ بالعربية
 */
function formatArabicDate($date) {
    $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
               'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    
    $timestamp = strtotime($date);
    $dayName = $days[date('w', $timestamp)];
    $day = date('d', $timestamp);
    $month = $months[date('m', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    return "$dayName، $day $month $year";
}

/**
 * حساب العمر
 */
function calculateAge($dateOfBirth) {
    $birthDate = new DateTime($dateOfBirth);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}

/**
 * تنسيق الأموال
 */
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',') . ' ر.س';
}

/**
 * التحقق من صيغة رقم الهاتف
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9\-\+\(\)]{10,}$/', $phone);
}

/**
 * التحقق من صيغة البريد
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * حساب الفرق الزمني
 */
function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'للتو';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' دقيقة';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' ساعة';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' يوم';
    } else {
        return floor($diff / 604800) . ' أسبوع';
    }
}
?>
