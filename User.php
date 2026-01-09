<?php
/**
 * ==========================================
 * فئة إدارة المستخدمين - User.php
 * ==========================================
 * معالجة جميع عمليات المستخدمين
 */

require_once 'Database.php';

class User {
    private $db;
    private $id;
    private $email;
    private $username;
    private $role;
    private $data = [];

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * تسجيل مستخدم جديد
     */
    public function register($email, $username, $password, $fullName, $role = 'patient') {
        // التحقق من البيانات
        if (!isValidEmail($email)) {
            return ['success' => false, 'error' => 'البريد الإلكتروني غير صحيح'];
        }

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'error' => 'كلمة المرور قصيرة جداً'];
        }

        $strength = checkPasswordStrength($password);
        if ($strength['strength'] < 60) {
            return ['success' => false, 'error' => 'كلمة المرور ضعيفة جداً'];
        }

        // التحقق من عدم وجود المستخدم
        $existing = $this->db->getOne(
            "SELECT id FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'المستخدم مسجل بالفعل'];
        }

        try {
            $this->db->beginTransaction();

            $hashedPassword = hashPassword($password);
            $result = $this->db->execute(
                "INSERT INTO users (email, username, password, full_name, role, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$email, $username, $hashedPassword, $fullName, $role]
            );

            if ($result) {
                $userId = $this->db->lastInsertId();

                // إنشاء سجل المريض إذا كان المستخدم مريضاً
                if ($role === 'patient') {
                    $this->db->execute(
                        "INSERT INTO patients (user_id, name, email, phone, created_at) 
                         VALUES (?, ?, ?, ?, NOW())",
                        [$userId, $fullName, $email, '']
                    );
                }

                // تسجيل النشاط
                logActivity($userId, 'REGISTER', "تسجيل مستخدم جديد: $email");

                $this->db->commit();

                return [
                    'success' => true,
                    'message' => 'تم التسجيل بنجاح',
                    'user_id' => $userId
                ];
            } else {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'فشل في التسجيل'];
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            logError($e);
            return ['success' => false, 'error' => 'خطأ في النظام'];
        }
    }

    /**
     * تسجيل الدخول
     */
    public function login($email, $password) {
        if (!isValidEmail($email)) {
            return ['success' => false, 'error' => 'البريد غير صحيح'];
        }

        $user = $this->db->getOne(
            "SELECT * FROM users WHERE email = ? AND is_active = true",
            [$email]
        );

        if (!$user) {
            logActivity(null, 'LOGIN_FAILED', "محاولة دخول فاشلة: $email");
            return ['success' => false, 'error' => 'بيانات الدخول غير صحيحة'];
        }

        if (!verifyPassword($password, $user['password'])) {
            logActivity($user['id'], 'LOGIN_FAILED', 'كلمة مرور خاطئة');
            return ['success' => false, 'error' => 'بيانات الدخول غير صحيحة'];
        }

        // بدء الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();

        // تحديث آخر تسجيل دخول
        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );

        // تسجيل النشاط
        logActivity($user['id'], 'LOGIN', 'تسجيل دخول ناجح');

        return [
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ]
        ];
    }

    /**
     * تسجيل الخروج
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        logActivity($userId, 'LOGOUT', 'تسجيل خروج');
        
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ];
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'error' => 'كلمة المرور الجديدة قصيرة جداً'];
        }

        $user = $this->db->getOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'المستخدم غير موجود'];
        }

        if (!verifyPassword($oldPassword, $user['password'])) {
            logActivity($userId, 'PASSWORD_CHANGE_FAILED', 'كلمة المرور القديمة خاطئة');
            return ['success' => false, 'error' => 'كلمة المرور القديمة غير صحيحة'];
        }

        $hashedPassword = hashPassword($newPassword);

        if ($this->db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedPassword, $userId]
        )) {
            logActivity($userId, 'PASSWORD_CHANGED', 'تم تغيير كلمة المرور');
            return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
        }

        return ['success' => false, 'error' => 'فشل في تغيير كلمة المرور'];
    }

    /**
     * الحصول على بيانات المستخدم
     */
    public function getUser($userId) {
        $user = $this->db->getOne(
            "SELECT id, email, username, full_name, role, phone, avatar, created_at 
             FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return null;
        }

        // الحصول على بيانات إضافية حسب الدور
        if ($user['role'] === 'patient') {
            $patient = $this->db->getOne(
                "SELECT * FROM patients WHERE user_id = ?",
                [$userId]
            );
            $user['patient_data'] = $patient;
        } elseif ($user['role'] === 'doctor') {
            $doctor = $this->db->getOne(
                "SELECT * FROM doctors WHERE user_id = ?",
                [$userId]
            );
            $user['doctor_data'] = $doctor;
        }

        return $user;
    }

    /**
     * تحديث بيانات المستخدم
     */
    public function updateProfile($userId, $data) {
        $updateFields = [];
        $params = [];

        $allowedFields = ['full_name', 'phone', 'avatar'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return ['success' => false, 'error' => 'لا توجد بيانات للتحديث'];
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE id = ?";

        if ($this->db->execute($sql, $params)) {
            logActivity($userId, 'PROFILE_UPDATED', 'تم تحديث الملف الشخصي');
            return ['success' => true, 'message' => 'تم تحديث البيانات بنجاح'];
        }

        return ['success' => false, 'error' => 'فشل في التحديث'];
    }

    /**
     * حذف حساب المستخدم
     */
    public function deleteAccount($userId, $password) {
        $user = $this->db->getOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'المستخدم غير موجود'];
        }

        if (!verifyPassword($password, $user['password'])) {
            return ['success' => false, 'error' => 'كلمة المرور غير صحيحة'];
        }

        try {
            $this->db->beginTransaction();

            // حذف جميع البيانات المرتبطة
            if ($this->db->getOne("SELECT role FROM users WHERE id = ?", [$userId])['role'] === 'patient') {
                $this->db->execute("DELETE FROM patients WHERE user_id = ?", [$userId]);
            }

            $this->db->execute("DELETE FROM users WHERE id = ?", [$userId]);

            logActivity($userId, 'ACCOUNT_DELETED', 'تم حذف الحساب');

            $this->db->commit();

            return ['success' => true, 'message' => 'تم حذف الحساب بنجاح'];
        } catch (Exception $e) {
            $this->db->rollBack();
            logError($e);
            return ['success' => false, 'error' => 'فشل في حذف الحساب'];
        }
    }

    /**
     * التحقق من الجلسة
     */
    public function checkSession() {
        if (!isLoggedIn()) {
            return ['valid' => false, 'message' => 'الجلسة منتهية'];
        }

        $timeElapsed = time() - ($_SESSION['login_time'] ?? time());

        if ($timeElapsed > SESSION_TIMEOUT) {
            session_destroy();
            return ['valid' => false, 'message' => 'انتهت مهلة الجلسة'];
        }

        return [
            'valid' => true,
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'],
            'time_remaining' => SESSION_TIMEOUT - $timeElapsed
        ];
    }

    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword($email) {
        $user = $this->db->getOne(
            "SELECT id, email FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'البريد الإلكتروني غير مسجل'];
        }

        $token = generateToken();
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // في نسخة متقدمة، ستحتاج جدول reset_tokens
        // $this->db->execute(
        //     "INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
        //     [$user['id'], $tokenHash, $expiresAt]
        // );

        // إرسال بريد إلكتروني (في نسخة متقدمة)
        // sendEmail($email, 'إعادة تعيين كلمة المرور', ...);

        return [
            'success' => true,
            'message' => 'تم إرسال رابط إعادة التعيين'
        ];
    }

    /**
     * الحصول على قائمة المستخدمين (للمسؤولين)
     */
    public function getAllUsers($limit = 50, $offset = 0, $role = null) {
        $sql = "SELECT id, email, username, full_name, role, is_active, last_login, created_at 
                FROM users WHERE 1=1";
        $params = [];

        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->getAll($sql, $params);
    }

    /**
     * تعطيل/تفعيل المستخدم
     */
    public function toggleUserStatus($userId, $isActive) {
        if ($this->db->execute(
            "UPDATE users SET is_active = ? WHERE id = ?",
            [$isActive, $userId]
        )) {
            $action = $isActive ? 'تفعيل' : 'تعطيل';
            logActivity(getCurrentUserId(), 'USER_STATUS_CHANGED', "$action المستخدم: $userId");
            return ['success' => true, 'message' => "تم $action المستخدم"];
        }

        return ['success' => false, 'error' => 'فشل العملية'];
    }
}
?>
