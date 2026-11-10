<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: AuditoriaModel.php (en models/)
 * Propósito: DAO para consulta de la bitácora inmutable de auditoría
 * NOTA DE SEGURIDAD: Estructura append-only. Operaciones UPDATE y DELETE
 *                    están expresamente bloqueadas por arquitectura.
 * =====================================================================
 */

class AuditoriaModel extends BaseModel {
    protected string $table = 'auditoria';
    protected string $pk = 'id_auditoria';

    /**
     * Bloquear explícitamente actualizaciones en la bitácora
     */
    public function update(int $id, array $data): bool {
        throw new BadMethodCallException('La tabla de auditoría es estrictamente inmutable (append-only). Modificaciones prohibidas.');
    }

    /**
     * Bloquear explícitamente eliminaciones en la bitácora
     */
    public function delete(int $id): bool {
        throw new BadMethodCallException('La tabla de auditoría es estrictamente inmutable (append-only). Eliminaciones prohibidas.');
    }

    /**
     * Override compatible con BaseModel: delega a findPaginated() sin paginación.
     * Para paginación real usar findPaginated().
     */
    public function findAll(array $where = [], string $orderBy = ''): array {
        return $this->findPaginated($where);
    }

    /**
     * Obtiene registros de auditoría paginados con datos del usuario actor.
     * Usar este método en lugar de findAll() cuando se requiera paginación.
     */
    public function findPaginated(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $clauses = [];
            $params = [];

            if (!empty($filters['accion'])) {
                $clauses[] = "a.accion = :accion";
                $params[':accion'] = strtoupper(trim($filters['accion']));
            }
            if (!empty($filters['entidad'])) {
                $clauses[] = "a.entidad = :entidad";
                $params[':entidad'] = strtolower(trim($filters['entidad']));
            }
            if (!empty($filters['id_registro'])) {
                $clauses[] = "a.id_registro = :id_registro";
                $params[':id_registro'] = (string)$filters['id_registro'];
            }
            if (!empty($filters['id_usuario'])) {
                $clauses[] = "a.id_usuario = :id_usuario";
                $params[':id_usuario'] = (int)$filters['id_usuario'];
            }
            if (!empty($filters['fecha_desde'])) {
                $clauses[] = "a.fecha_evento >= :fecha_desde";
                $params[':fecha_desde'] = $filters['fecha_desde'] . ' 00:00:00';
            }
            if (!empty($filters['fecha_hasta'])) {
                $clauses[] = "a.fecha_evento <= :fecha_hasta";
                $params[':fecha_hasta'] = $filters['fecha_hasta'] . ' 23:59:59';
            }

            $whereSql = !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '';

            $sql = "SELECT a.*, 
                           u.correo AS usuario_correo, 
                           u.nombres AS usuario_nombres, 
                           u.apellidos AS usuario_apellidos, 
                           u.rol AS usuario_rol
                    FROM `{$this->table}` a
                    LEFT JOIN `usuarios` u ON u.id_usuario = a.id_usuario
                    {$whereSql}
                    ORDER BY a.fecha_evento DESC, a.id_auditoria DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);

            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::findAll(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de eventos registrados según los filtros aplicados
     */
    public function countAll(array $filters = []): int {
        try {
            $clauses = [];
            $params = [];

            if (!empty($filters['accion'])) {
                $clauses[] = "a.accion = :accion";
                $params[':accion'] = strtoupper(trim($filters['accion']));
            }
            if (!empty($filters['entidad'])) {
                $clauses[] = "a.entidad = :entidad";
                $params[':entidad'] = strtolower(trim($filters['entidad']));
            }
            if (!empty($filters['id_registro'])) {
                $clauses[] = "a.id_registro = :id_registro";
                $params[':id_registro'] = (string)$filters['id_registro'];
            }
            if (!empty($filters['id_usuario'])) {
                $clauses[] = "a.id_usuario = :id_usuario";
                $params[':id_usuario'] = (int)$filters['id_usuario'];
            }

            $whereSql = !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '';

            $sql = "SELECT COUNT(*) FROM `{$this->table}` a {$whereSql}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::countAll(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el historial completo de auditoría para una entidad y registro específico
     */
    public function findByEntidad(string $entidad, string $idRegistro): array {
        try {
            $sql = "SELECT a.*, 
                           u.correo AS usuario_correo, 
                           u.nombres AS usuario_nombres, 
                           u.apellidos AS usuario_apellidos,
                           u.rol AS usuario_rol
                    FROM `{$this->table}` a
                    LEFT JOIN `usuarios` u ON u.id_usuario = a.id_usuario
                    WHERE a.entidad = :entidad AND a.id_registro = :id_registro
                    ORDER BY a.fecha_evento DESC, a.id_auditoria DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':entidad'     => strtolower(trim($entidad)),
                ':id_registro' => (string)$idRegistro
            ]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en AuditoriaModel::findByEntidad('{$entidad}', '{$idRegistro}'): " . $e->getMessage());
            return [];
        }
    }
}
