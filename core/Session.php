<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: Session.php (en core/)
 * Propósito: Manejo seguro y estático de sesiones de usuario y flash messages
 * =====================================================================
 */

class Session {
    private const USER_SESSION_KEY = 'sgi_auth_user';
    private const FLASH_SESSION_KEY = 'sgi_flash_messages';

    /**
     * Inicia la sesión con parámetros estrictos de seguridad
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Almacena un valor en la sesión
     */
    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión con valor por defecto opcional
     */
    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si una clave existe en la sesión
     */
    public static function has(string $key): bool {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Elimina una clave de la sesión
     */
    public static function delete(string $key): void {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destruye completamente la sesión actual y elimina su cookie
     */
    public static function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
        }
    }

    /**
     * Regenera el ID de sesión para prevenir Session Fixation
     */
    public static function regenerate(bool $deleteOldSession = true): bool {
        self::start();
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Establece un mensaje flash en la sesión (tipo: success, error, warning, info)
     */
    public static function setFlash(string $type, string $message): void {
        self::start();
        if (!isset($_SESSION[self::FLASH_SESSION_KEY]) || !is_array($_SESSION[self::FLASH_SESSION_KEY])) {
            $_SESSION[self::FLASH_SESSION_KEY] = [];
        }
        $_SESSION[self::FLASH_SESSION_KEY][] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    /**
     * Recupera y limpia los mensajes flash de la sesión
     */
    public static function getFlash(): ?array {
        self::start();
        if (!isset($_SESSION[self::FLASH_SESSION_KEY])) {
            return null;
        }

        $flashes = $_SESSION[self::FLASH_SESSION_KEY];
        unset($_SESSION[self::FLASH_SESSION_KEY]);

        return !empty($flashes) ? $flashes : null;
    }

    /**
     * Asigna la información del usuario autenticado en sesión
     */
    public static function setUser(array $userData): void {
        self::start();
        // Omitir almacenar el hash de la contraseña en sesión por seguridad
        unset($userData['password_hash']);
        $_SESSION[self::USER_SESSION_KEY] = $userData;
    }

    /**
     * Retorna los datos del usuario autenticado o null si es anónimo
     */
    public static function getUser(): ?array {
        self::start();
        return $_SESSION[self::USER_SESSION_KEY] ?? null;
    }

    /**
     * Retorna el id_usuario del usuario autenticado o null
     */
    public static function getUserId(): ?int {
        $user = self::getUser();
        return isset($user['id_usuario']) ? (int)$user['id_usuario'] : null;
    }

    /**
     * Retorna el rol del usuario autenticado o null
     */
    public static function getUserRole(): ?string {
        $user = self::getUser();
        return $user['rol'] ?? null;
    }

    /**
     * Verifica si existe un usuario autenticado
     */
    public static function isLoggedIn(): bool {
        return self::getUserId() !== null;
    }
}
