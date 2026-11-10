<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: ProgramaModel.php (en models/)
 * Propósito: DAO para la tabla programas (programas y versiones de malla)
 * =====================================================================
 */

class ProgramaModel extends BaseModel {
    protected string $table = 'programas';
    protected string $pk = 'id_programa';

    /**
     * Retorna todos los programas que se encuentren con estado PUBLICADA
     */
    public function findAllPublicados(): array {
        return $this->findAll(['estado' => 'PUBLICADA'], 'nombre ASC, version DESC');
    }

    /**
     * Obtiene el programa junto con la lista completa de materias asociadas a su malla
     */
    public function findWithMaterias(int $id): ?array {
        $programa = $this->findById($id);
        if ($programa === null) {
            return null;
        }

        try {
            $sql = "SELECT m.*, 
                           req.codigo AS requisito_codigo, 
                           req.nombre AS requisito_nombre
                    FROM `materias` m
                    LEFT JOIN `materias` req ON req.id_materia = m.id_materia_requisito
                    WHERE m.id_programa = :id_programa
                    ORDER BY m.periodo_bloque ASC, m.codigo ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_programa' => $id]);
            $programa['materias'] = $stmt->fetchAll();

            return $programa;
        } catch (PDOException $e) {
            error_log("Error en ProgramaModel::findWithMaterias({$id}): " . $e->getMessage());
            $programa['materias'] = [];
            return $programa;
        }
    }

    /**
     * Cierre lógico del programa académico
     */
    public function softDelete(int $id): bool {
        return $this->update($id, ['estado' => 'CERRADA']);
    }
}
