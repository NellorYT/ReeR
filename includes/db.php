<?php
require_once dirname(__FILE__) . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $connected = false;

    private function __construct() {
        try {
            // Для PHP 8.4+ убираем устаревшую константу
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];
            
            // Устанавливаем кодировку через SET NAMES после подключения
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET NAMES " . DB_CHARSET);
            
            $this->connected = true;
            
        } catch (PDOException $e) {
            $this->connected = false;
            
            if (DEBUG_MODE) {
                $errorInfo = [
                    'error' => 'Ошибка подключения к базе данных',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'host' => DB_HOST,
                    'port' => DB_PORT,
                    'database' => DB_NAME,
                    'user' => DB_USER
                ];
                
                // Пытаемся подключиться без указания базы данных
                try {
                    $tempDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT;
                    $tempPdo = new PDO($tempDsn, DB_USER, DB_PASS);
                    $errorInfo['mysql_connection'] = 'Успешно';
                    
                    // Проверяем существование базы данных
                    $stmt = $tempPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
                    if ($stmt->rowCount() == 0) {
                        $errorInfo['db_exists'] = false;
                        $errorInfo['solution'] = 'База данных ' . DB_NAME . ' не существует. Создайте её или импортируйте db.sql';
                    } else {
                        $errorInfo['db_exists'] = true;
                    }
                    
                } catch (Exception $ex) {
                    $errorInfo['mysql_connection'] = 'Ошибка подключения к MySQL';
                    $errorInfo['mysql_error'] = $ex->getMessage();
                    
                    // Предлагаем возможные решения
                    $errorInfo['solutions'] = [
                        '1. Запустите MySQL сервер (XAMPP/OpenServer/MAMP)',
                        '2. Проверьте порт MySQL (по умолчанию 3306)',
                        '3. Проверьте логин/пароль в config.php',
                        '4. Попробуйте использовать 127.0.0.1 вместо localhost'
                    ];
                }
                
                echo '<pre style="background: #1a1a2a; color: #fff; padding: 20px; border-radius: 10px; margin: 20px;">';
                echo '<h2 style="color: #ff4b4b;">❌ Ошибка подключения к БД</h2>';
                echo '<p><strong>Текст ошибки:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p><strong>Код ошибки:</strong> ' . $e->getCode() . '</p>';
                echo '<h3>🔧 Проверьте следующее:</h3>';
                echo '<ul>';
                echo '<li>Запущен ли MySQL сервер? (XAMPP/OpenServer/MAMP)</li>';
                echo '<li>Правильный ли порт? (сейчас: ' . DB_PORT . ')</li>';
                echo '<li>Правильный ли пароль? (сейчас: "' . DB_PASS . '")</li>';
                echo '<li>Существует ли база данных "' . DB_NAME . '"?</li>';
                echo '</ul>';
                echo '<h3>📝 Попробуйте:</h3>';
                echo '<ol>';
                echo '<li>Откройте phpMyAdmin (http://localhost/phpmyadmin)</li>';
                echo '<li>Создайте базу данных "unioncase"</li>';
                echo '<li>Импортируйте файл db.sql</li>';
                echo '<li>Или запустите test_connection.php для диагностики</li>';
                echo '</ol>';
                echo '</pre>';
            } else {
                die('Ошибка подключения к базе данных. Пожалуйста, проверьте настройки в config.php');
            }
            
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                echo '<pre style="background: #1a1a2a; color: #fff; padding: 20px;">';
                echo '<h2 style="color: #ff4b4b;">❌ Ошибка SQL запроса</h2>';
                echo '<p><strong>Запрос:</strong> ' . htmlspecialchars($sql) . '</p>';
                echo '<p><strong>Параметры:</strong> ' . htmlspecialchars(print_r($params, true)) . '</p>';
                echo '<p><strong>Ошибка:</strong> ' . $e->getMessage() . '</p>';
                echo '</pre>';
            }
            throw $e;
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    public function checkTables() {
        $requiredTables = ['users', 'marketplaces', 'cases', 'items', 'case_items', 'case_opens', 'user_inventory', 'balance_transactions'];
        $existingTables = [];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            try {
                $result = $this->fetch("SHOW TABLES LIKE ?", [$table]);
                if ($result) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            } catch (Exception $e) {
                $missingTables[] = $table;
            }
        }
        
        return [
            'existing' => $existingTables,
            'missing' => $missingTables
        ];
    }
}

function db() {
    return Database::getInstance();
}
?>