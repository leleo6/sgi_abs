<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: Auth.php (en core/)
 * Propósito: Servicio de autenticación, control de accesos y roles RBAC
 * =====================================================================
 */

class Auth {
    /**
     * Intenta autenticar a un usuario mediante correo y contraseña.
     */
    public static function attempt(string $email, string $password): bool {
        Session::start();
        $email = trim(strtolower($email));

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM `usuarios` WHERE `correo` = :correo LIMIT 1");
        $stmt->execute([':correo' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            Audit::log('LOGIN_FAILED', 'usuarios', '0', null, ['correo' => $email], 'Intento con correo no registrado');
            return false;
        }

        $userId = (int)$user['id_usuario'];
        $attemptKey = 'login_attempts_' . $userId;

        // Validar estado actual de la cuenta
        if ($user['estado'] === 'BLOQUEADO') {
            Session::setFlash('error', 'Su cuenta se encuentra bloqueada por seguridad. Contacte a la Coordinación o Administrador.');
            Audit::log('LOGIN_BLOCKED', 'usuarios', (string)$userId, null, null, 'Intento de acceso a cuenta bloqueada');
            return false;
        }

        if ($user['estado'] === 'INACTIVO') {
            Session::setFlash('error', 'Su cuenta de usuario está inactiva en el sistema.');
            Audit::log('LOGIN_INACTIVE', 'usuarios', (string)$userId, null, null, 'Intento de acceso a cuenta inactiva');
            return false;
        }

        // Verificación criptográfica de contraseña
        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)Session::get($attemptKey, 0) + 1;
            Session::set($attemptKey, $attempts);

            $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;

            if ($attempts >= $maxAttempts) {
                // Bloquear cuenta en la base de datos
                $blockStmt = $db->prepare("UPDATE `usuarios` SET `estado` = 'BLOQUEADO' WHERE `id_usuario` = :id");
                $blockStmt->execute([':id' => $userId]);

                Audit::log(
                    'SECURITY_LOCKOUT',
                    'usuarios',
                    (string)$userId,
                    ['estado' => $user['estado']],
                    ['estado' => 'BLOQUEADO'],
                    "Bloqueo automático tras {$attempts} intentos fallidos de autenticación"
                );

                Session::setFlash('error', 'Su cuenta ha sido bloqueada por exceder el número máximo de intentos fallidos.');
            } else {
                $restantes = $maxAttempts - $attempts;
                Audit::log('LOGIN_FAILED', 'usuarios', (string)$userId, null, ['intento' => $attempts], 'Contraseña inválida');
                Session::setFlash('error', "Credenciales incorrectas. Le quedan {$restantes} intento(s) antes del bloqueo.");
            }

            return false;
        }

        // Contraseña válida: Limpiar intentos fallidos
        Session::delete($attemptKey);

        // Actualizar último acceso
        $accessStmt = $db->prepare("UPDATE `usuarios` SET `ultimo_acceso` = NOW() WHERE `id_usuario` = :id");
        $accessStmt->execute([':id' => $userId]);

        // Regenerar sesión para prevenir fijación de sesión
        Session::regenerate(true);

        // Guardar información en sesión
        Session::setUser($user);

        // Registrar auditoría de inicio de sesión exitoso
        Audit::log('LOGIN_SUCCESS', 'usuarios', (string)$userId, null, [
            'rol' => $user['rol'],
            'ultimo_acceso' => date('Y-m-d H:i:s')
        ], 'Inicio de sesión correcto');

        return true;
    }

    /**
     * Cierra la sesión activa del usuario
     */
    public static function logout(): void {
        $userId = self::id();
        if ($userId !== null) {
            Audit::log('LOGOUT', 'usuarios', (string)$userId, null, null, 'Cierre voluntario de sesión');
        }
        Session::destroy();
    }

    /**
     * Verifica si el usuario actual está autenticado
     */
    public static function check(): bool {
        return Session::isLoggedIn();
    }

    /**
     * Retorna los datos del usuario autenticado
     */
    public static function user(): ?array {
        return Session::getUser();
    }

    /**
     * Retorna el id del usuario autenticado
     */
    public static function id(): ?int {
        return Session::getUserId();
    }

    /**
     * Retorna el rol del usuario autenticado
     */
    public static function role(): ?string {
        return Session::getUserRole();
    }

    /**
     * Valida si el usuario actual posee uno de los roles permitidos
     * El rol ADMIN tiene acceso concedido a todas las funciones
     */
    public static function can(string|array $roles): bool {
        $currentRole = self::role();
        if ($currentRole === null) {
            return false;
        }

        if ($currentRole === 'ADMIN') {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($currentRole, $roles, true);
    }

    /**
     * Exige autenticación; de lo contrario redirige al login
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            Session::setFlash('warning', 'Debe iniciar sesión para ingresar a esta sección.');
            // PARCHE C-02: URL absoluta con parámetro 'module' correcto del router.
            // header() debe llamarse antes de cualquier output; el front controller
            // usa ob_start() para garantizar esto.
            header('Location: index.php?module=auth&action=login');
            exit;
        }
    }

    /**
     * Exige que el usuario cuente con uno de los roles indicados; si no, retorna error 403
     */
    public static function requireRole(string|array $roles): void {
        self::requireAuth();

        if (!self::can($roles)) {
            $allowed = is_array($roles) ? implode(', ', $roles) : $roles;
            Audit::log(
                'ACCESS_DENIED',
                'seguridad',
                (string)(self::id() ?? 0),
                null,
                ['requeridos' => $allowed, 'usuario_rol' => self::role()],
                'Intento de acceso a recurso sin el rol autorizado'
            );

            // PARCHE M-03: Delegar al view de error 403 en lugar de HTML inline.
            // Mantiene coherencia con el patrón MVC y el diseño del sistema.
            http_response_code(403);
            $title = 'Acceso Denegado — 403';
            if (defined('BASE_PATH') && file_exists(BASE_PATH . '/views/errors/403.php')) {
                include BASE_PATH . '/views/errors/403.php';
            } else {
                echo '<h1>403 - Acceso Denegado</h1><p>No tiene permisos para este recurso.</p>';
                echo '<a href="index.php">&larr; Volver</a>';
            }
            exit;
        }
    }
}
