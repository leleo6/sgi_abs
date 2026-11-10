<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: EvidenciaModel.php (en models/)
 * Propósito: DAO para la tabla evidencias, entrega y revisión de documentos
 * =====================================================================
 */

class EvidenciaModel extends BaseModel {
    protected string $table = 'evidencias';
    protected string $pk = 'id_evidencia';

    /**
     * Retorna todas las evidencias asociadas a un proceso de grado
     */
    public function findByProceso(int $idProceso): array {
        try {
            $sql = "SELECT e.*, 
                           sub.nombres AS subido_por_nombres, 
                           sub.apellidos AS subido_por_apellidos,
                           rev.nombres AS revisor_nombres, 
                           rev.apellidos AS revisor_apellidos
                    FROM `{$this->table}` e
                    LEFT JOIN `usuarios` sub ON sub.id_usuario = e.subido_por
                    LEFT JOIN `usuarios` rev ON rev.id_usuario = e.id_revisor
                    WHERE e.id_proceso = :id_proceso
                    ORDER BY e.tipo_evidencia ASC, e.version_entrega DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_proceso' => $idProceso]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en EvidenciaModel::findByProceso({$idProceso}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lista evidencias con detalles de estudiante, proceso y estado
     */
    public function findAllWithDetails(array $filters = []): array {
        try {
            $clauses = [];
            $params = [];

            if (!empty($filters['estado'])) {
                $clauses[] = "e.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
            if (!empty($filters['id_proceso'])) {
                $clauses[] = "e.id_proceso = :id_proceso";
                $params[':id_proceso'] = (int)$filters['id_proceso'];
            }
            if (!empty($filters['tipo_evidencia'])) {
                $clauses[] = "e.tipo_evidencia = :tipo_evidencia";
                $params[':tipo_evidencia'] = $filters['tipo_evidencia'];
            }

            $whereSql = !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '';

            $sql = "SELECT e.*, 
                           pg.modalidad, pg.estado AS proceso_estado,
                           u.nombres AS estudiante_nombres, u.apellidos AS estudiante_apellidos,
                           u.correo AS estudiante_correo,
                           sub.nombres AS subido_por_nombres, sub.apellidos AS subido_por_apellidos,
                           rev.nombres AS revisor_nombres, rev.apellidos AS revisor_apellidos
                    FROM `{$this->table}` e
                    INNER JOIN `procesos_grado` pg ON pg.id_proceso = e.id_proceso
                    INNER JOIN `matriculas` mt ON mt.id_matricula = pg.id_matricula
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    LEFT JOIN `usuarios` sub ON sub.id_usuario = e.subido_por
                    LEFT JOIN `usuarios` rev ON rev.id_usuario = e.id_revisor
                    {$whereSql}
                    ORDER BY e.id_evidencia DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en EvidenciaModel::findAllWithDetails(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene entregables pendientes de revisión por docentes o responsables de prácticas
     */
    public function findPendientes(): array {
        try {
            $sql = "SELECT e.*, 
                           pg.modalidad,
                           u.nombres AS estudiante_nombres, u.apellidos AS estudiante_apellidos
                    FROM `{$this->table}` e
                    INNER JOIN `procesos_grado` pg ON pg.id_proceso = e.id_proceso
                    INNER JOIN `matriculas` mt ON mt.id_matricula = pg.id_matricula
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    WHERE e.estado IN ('PENDIENTE', 'ENTREGADA', 'EN_REVISION')
                    ORDER BY e.fecha_entrega ASC, e.id_evidencia ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en EvidenciaModel::findPendientes(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aprueba formalmente una evidencia entregada
     */
    public function aprobar(int $id, int $revisorId, string $observaciones = ''): bool {
        return $this->update($id, [
            'estado'         => 'APROBADA',
            'id_revisor'     => $revisorId,
            'fecha_revision' => date('Y-m-d H:i:s'),
            'observaciones'  => substr(trim($observaciones), 0, 500)
        ]);
    }

    /**
     * Rechaza una evidencia solicitando subsanación
     */
    public function rechazar(int $id, int $revisorId, string $observaciones): bool {
        return $this->update($id, [
            'estado'         => 'RECHAZADA',
            'id_revisor'     => $revisorId,
            'fecha_revision' => date('Y-m-d H:i:s'),
            'observaciones'  => substr(trim($observaciones), 0, 500)
        ]);
    }

    /**
     * Verifica si todas las evidencias cargadas en un proceso están aprobadas
     */
    public function todasAprobadasByProceso(int $idProceso): bool {
        try {
            $sql = "SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN `estado` = 'APROBADA' THEN 1 ELSE 0 END) AS aprobadas
                    FROM `{$this->table}`
                    WHERE `id_proceso` = :id_proceso";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_proceso' => $idProceso]);
            $row = $stmt->fetch();

            if (!$row) {
                return false;
            }

            $total = (int)$row['total'];
            $aprobadas = (int)$row['aprobadas'];

            return ($total > 0 && $total === $aprobadas);
        } catch (PDOException $e) {
            error_log("Error en EvidenciaModel::todasAprobadasByProceso({$idProceso}): " . $e->getMessage());
            return false;
        }
    }
}
