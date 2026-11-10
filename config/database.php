<?php
declare(strict_types=1);

/**
 * =====================================================================
 * SGI-ABS — config/database.php
 * PARCHE M-02: Se oculta mensaje técnico de PDO al usuario final.
 *              Solo se escribe en error_log. El Singleton PDO es correcto.
 * =====================================================================
 */

if (!class_exists('Database', false)) {
    class Database {
        private static ?PDO    $instance = null;
        private static array   $config   = [];

        private static function getDefaultConfig(): array {
            return [
                'host'     => getenv('DB_HOST') ?: 'localhost',
                'port'     => getenv('DB_PORT') ?: '3306',
                'dbname'   => getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'sgi_abs'),
                'username' => getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root'),
                'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ''),
                'charset'  => 'utf8mb4',
            ];
        }

        /** Singleton — constructor y clone privados */
        private function __construct() {}
        private function __clone() {}

        /** Sobrescribe configuración (resetea instancia) */
        public static function configure(array $config): void {
            self::$config   = array_merge(self::getConfig(), $config);
            self::$instance = null;
        }

        /** Obtiene configuración efectiva */
        public static function getConfig(): array {
            return array_merge(self::getDefaultConfig(), self::$config);
        }

        /**
         * Obtiene la instancia PDO única.
         * PARCHE M-02: El mensaje de excepción pública es genérico;
         * el detalle técnico (host, usuario, etc.) solo va a error_log.
         */
        public static function getInstance(): PDO {
            if (self::$instance === null) {
                $cfg = self::getConfig();
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['dbname'],
                    $cfg['charset']
                );
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ];
                try {
                    self::$instance = new PDO(
                        $dsn,
                        $cfg['username'],
                        $cfg['password'],
                        $options
                    );
                } catch (PDOException $e) {
                    // ✅ Solo error_log — NUNCA exponer DSN/credenciales al cliente
                    error_log('[SGI-ABS][DB] Error crítico de conexión PDO: ' . $e->getMessage());
                    throw new RuntimeException(
                        'No fue posible conectar con el sistema de datos. Contacte al administrador.',
                        500
                    );
                }
            }
            return self::$instance;
        }
        public static function prepare(string $sql): \PDOStatement { return self::getInstance()->prepare($sql); }

        public static function fetchAll(string $sql, array $params = []): array {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        public static function fetchOne(string $sql, array $params = []): ?array {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        }

        public static function execute(string $sql, array $params = []): int {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }

        public static function lastInsertId(): string  { return self::getInstance()->lastInsertId(); }
        public static function beginTransaction(): bool { return self::getInstance()->beginTransaction(); }
        public static function commit(): bool           { return self::getInstance()->commit(); }
        public static function rollBack(): bool         { return self::getInstance()->rollBack(); }
        public static function inTransaction(): bool    { return self::getInstance()->inTransaction(); }
    }
}
