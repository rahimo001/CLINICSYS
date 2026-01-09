<?php
/**
 * ==========================================
 * فئة قاعدة البيانات المتقدمة - Database.php
 * ==========================================
 * معالجة آمنة وشاملة لقاعدة البيانات
 */

class Database {
    private $pdo;
    private $error;
    private $stmt;

    public function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';port=' . DB_PORT . ';charset=utf8mb4';
        
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
            ]);
        } catch (PDOException $e) {
            $this->error = 'خطأ في الاتصال: ' . $e->getMessage();
            die($this->error);
        }
    }

    /**
     * تنفيذ استعلام مع معاملات آمنة
     */
    public function execute($sql, $params = []) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            return $this->stmt->execute($params);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            logError($e);
            return false;
        }
    }

    /**
     * الحصول على سجل واحد
     */
    public function getOne($sql, $params = []) {
        try {
            $this->execute($sql, $params);
            return $this->stmt->fetch();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            logError($e);
            return null;
        }
    }

    /**
     * الحصول على جميع السجلات
     */
    public function getAll($sql, $params = []) {
        try {
            $this->execute($sql, $params);
            return $this->stmt->fetchAll();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            logError($e);
            return [];
        }
    }

    /**
     * الحصول على معرف آخر سجل تم إدراجه
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * الحصول على رسالة الخطأ
     */
    public function getError() {
        return $this->error;
    }

    /**
     * بدء معاملة (Transaction)
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * تأكيد المعاملة
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * التراجع عن المعاملة
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }

    /**
     * إحصاء عدد الصفوف
     */
    public function count($sql, $params = []) {
        try {
            $this->execute($sql, $params);
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * بناء استعلام ديناميكي (Query Builder)
     */
    public function queryBuilder() {
        return new QueryBuilder($this->pdo);
    }
}

/**
 * بناء الاستعلامات الديناميكية
 */
class QueryBuilder {
    private $pdo;
    private $select = '*';
    private $from = '';
    private $where = [];
    private $join = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $params = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function select($fields) {
        $this->select = is_array($fields) ? implode(', ', $fields) : $fields;
        return $this;
    }

    public function from($table) {
        $this->from = $table;
        return $this;
    }

    public function where($condition, $value) {
        $this->where[] = $condition;
        $this->params[] = $value;
        return $this;
    }

    public function join($table, $condition) {
        $this->join[] = "JOIN $table ON $condition";
        return $this;
    }

    public function orderBy($field, $direction = 'ASC') {
        $this->orderBy[] = "$field $direction";
        return $this;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    public function get() {
        $sql = "SELECT {$this->select} FROM {$this->from}";
        
        if (!empty($this->join)) {
            $sql .= " " . implode(" ", $this->join);
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError($e);
            return [];
        }
    }

    public function first() {
        $this->limit(1);
        $result = $this->get();
        return !empty($result) ? $result[0] : null;
    }
}

/**
 * دالة تسجيل الأخطاء
 */
function logError($error) {
    $logDir = __DIR__ . '/logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'errors_' . date('Y-m-d') . '.log';
    $message = '[' . date('Y-m-d H:i:s') . '] ' . $error->getMessage() . PHP_EOL;
    
    file_put_contents($logFile, $message, FILE_APPEND);
}
?>
