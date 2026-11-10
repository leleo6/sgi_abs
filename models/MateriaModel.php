<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: MateriaModel.php (en models/)
 * Propósito: DAO para la tabla materias y gestión de malla curricular
 * =====================================================================
 */

class MateriaModel extends BaseModel {
    protected string $table = 'materias';
    protected string $pk = 'id_materia';

    /**
     * Retorna todas las materias de un programa académico ordenadas por periodo
     */
    public function findByPrograma(int $idPrograma): array {
        try {
            $sql = "SELECT m.*, 
                           req.codigo AS requisito_codigo, 
                           req.nombre AS requisito_nombre
                    FROM `{$this->table}` m
                    LEFT JOIN `{$this->table}` req ON req.id_materia = m.id_materia_requisito
                    WHERE m.id_programa = :id_programa
                    ORDER BY m.periodo_bloque ASC, m.codigo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_programa' => $idPrograma]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en MateriaModel::findByPrograma({$idPrograma}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna únicamente las materias obligatorias de un programa específico
     */
    public function findObligatoriasByPrograma(int $idPrograma): array {
        try {
            $sql = "SELECT m.*, 
                           req.codigo AS requisito_codigo, 
                           req.nombre AS requisito_nombre
                    FROM `{$this->table}` m
                    LEFT JOIN `{$this->table}` req ON req.id_materia = m.id_materia_requisito
                    WHERE m.id_programa = :id_programa AND m.tipo = 'OBLIGATORIA'
                    ORDER BY m.periodo_bloque ASC, m.codigo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_programa' => $idPrograma]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en MateriaModel::findObligatoriasByPrograma({$idPrograma}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una materia con los datos de su programa y su prerrequisito
     */
    public function findWithRequisito(int $id): ?array {
        try {
            $sql = "SELECT m.*, 
                           p.nombre AS programa_nombre, 
                           p.codigo AS programa_codigo, 
                           p.version AS programa_version,
                           req.codigo AS requisito_codigo, 
                           req.nombre AS requisito_nombre
                    FROM `{$this->table}` m
                    INNER JOIN `programas` p ON p.id_programa = m.id_programa
                    LEFT JOIN `{$this->table}` req ON req.id_materia = m.id_materia_requisito
                    WHERE m.id_materia = :id
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en MateriaModel::findWithRequisito({$id}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Manejo de baja de materias:
     * NOTA ARQUITECTURAL: La tabla normalizada `materias` no contiene columna de 'estado'
     * ni 'activo' para preservar la integridad de mallas curriculares históricas y FKs.
     * Si no existen calificaciones registradas ni dependencias de prerrequisito, permite
     * eliminación auditada; de existir vínculos, preserva el registro por consistencia.
     */
    public function softDelete(int $id): bool {
        try {
            // Verificar si hay resultados vinculados
            $checkSql = "SELECT COUNT(*) FROM `resultados` WHERE `id_materia` = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $id]);
            if ((int)$checkStmt->fetchColumn() > 0) {
                error_log("Materia ID {$id} no puede eliminarse por tener notas/resultados asociados.");
                return false;
            }

            // Verificar si es prerrequisito de otra materia
            $reqSql = "SELECT COUNT(*) FROM `materias` WHERE `id_materia_requisito` = :id";
            $reqStmt = $this->db->prepare($reqSql);
            $reqStmt->execute([':id' => $id]);
            if ((int)$reqStmt->fetchColumn() > 0) {
                error_log("Materia ID {$id} es prerrequisito activo de otras asignaturas.");
                return false;
            }

            return $this->delete($id);
        } catch (PDOException $e) {
            error_log("Error en MateriaModel::softDelete({$id}): " . $e->getMessage());
            return false;
        }
    }
}
