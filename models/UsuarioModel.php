<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: UsuarioModel.php (en models/)
 * Propósito: DAO para la tabla usuarios, autenticación, roles y perfiles
 * =====================================================================
 */

class UsuarioModel extends BaseModel {
    protected string $table = 'usuarios';
    protected string $pk = 'id_usuario';

    /**
     * Busca un usuario por su correo electrónico institucional o personal
     */
    public function findByEmail(string $email): ?array {
        $email = trim(strtolower($email));
        return $this->findOne(['correo' => $email]);
    }

    /**
     * Incrementa el contador de intentos fallidos en sesión y bloquea si excede el umbral
     */
    public function incrementFailedAttempts(int $id): void {
        Session::start();
        $key = 'login_attempts_' . $id;
        $attempts = (int)Session::get($key, 0) + 1;
        Session::set($key, $attempts);

        $max = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
        if ($attempts >= $max) {
            $this->update($id, ['estado' => 'BLOQUEADO']);
            Audit::log(
                'ACCOUNT_BLOCKED',
                $this->table,
                (string)$id,
                ['estado' => 'ACTIVO'],
                ['estado' => 'BLOQUEADO'],
                "Cuenta bloqueada automáticamente tras {$attempts} intentos fallidos"
            );
        }
    }

    /**
     * Resetea el contador de intentos fallidos de autenticación
     */
    public function resetFailedAttempts(int $id): void {
        Session::start();
        Session::delete('login_attempts_' . $id);
    }

    /**
     * Actualiza la fecha y hora del último acceso exitoso al sistema
     */
    public function updateLastAccess(int $id): void {
        try {
            $sql = "UPDATE `{$this->table}` SET `ultimo_acceso` = NOW() WHERE `{$this->pk}` = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error en UsuarioModel::updateLastAccess({$id}): " . $e->getMessage());
        }
    }

    /**
     * Actualiza de manera segura el hash de la contraseña
     */
    public function updatePasswordHash(int $id, string $hash): bool {
        return $this->update($id, ['password_hash' => $hash]);
    }

    /**
     * Desactiva lógicamente a un usuario sin eliminar su histórico
     */
    public function softDelete(int $id): bool {
        return $this->update($id, ['estado' => 'INACTIVO']);
    }

    /**
     * Obtiene todos los usuarios asociados a un rol específico
     */
    public function getAllWithRole(string $rol): array {
        try {
            $sql = "SELECT `id_usuario`, `rol`, `correo`, `nombres`, `apellidos`,
                           `tipo_documento`, `documento`, `telefono`, `estado`,
                           `ultimo_acceso`, `creado_en`
                    FROM `{$this->table}`
                    WHERE `rol` = :rol
                    ORDER BY `apellidos` ASC, `nombres` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':rol' => $rol]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en UsuarioModel::getAllWithRole('{$rol}'): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los estudiantes activos en el sistema
     */
    public function getEstudiantes(): array {
        try {
            $sql = "SELECT `id_usuario`, `correo`, `nombres`, `apellidos`,
                           `tipo_documento`, `documento`, `fecha_nacimiento`,
                           `telefono`, `autorizacion_datos`, `estado`, `creado_en`
                    FROM `{$this->table}`
                    WHERE `rol` = 'ESTUDIANTE' AND `estado` = 'ACTIVO'
                    ORDER BY `apellidos` ASC, `nombres` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en UsuarioModel::getEstudiantes(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna conteo de usuarios agrupados por rol para tableros de control
     */
    public function countByRole(): array {
        try {
            $sql = "SELECT `rol`, COUNT(*) AS `total` FROM `{$this->table}` GROUP BY `rol`";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $counts = [];
            foreach ($results as $row) {
                $counts[$row['rol']] = (int)$row['total'];
            }
            return $counts;
        } catch (PDOException $e) {
            error_log("Error en UsuarioModel::countByRole(): " . $e->getMessage());
            return [];
        }
    }
}
