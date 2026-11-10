<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: MatriculaModel.php (en models/)
 * Propósito: DAO para la tabla matriculas, elegibilidad académica y vistas
 * =====================================================================
 */

class MatriculaModel extends BaseModel {
    protected string $table = 'matriculas';
    protected string $pk = 'id_matricula';

    /**
     * Busca la matrícula activa más reciente de un usuario
     */
    public function findByUsuario(int $idUsuario): ?array {
        try {
            $sql = "SELECT mt.*, 
                           u.nombres, u.apellidos, u.correo, u.tipo_documento, u.documento,
                           p.codigo AS programa_codigo, p.version AS programa_version, 
                           p.nombre AS programa_nombre, p.regimen, p.unidad_medida, 
                           p.total_periodos, p.nota_minima
                    FROM `{$this->table}` mt
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    INNER JOIN `programas` p ON p.id_programa = mt.id_programa
                    WHERE mt.id_usuario = :id_usuario
                    ORDER BY mt.id_matricula DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en MatriculaModel::findByUsuario({$idUsuario}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene una matrícula con detalles completos del estudiante y programa académico
     */
    public function findWithDetails(int $id): ?array {
        try {
            $sql = "SELECT mt.*, 
                           u.nombres, u.apellidos, u.correo, u.tipo_documento, u.documento, u.telefono,
                           p.codigo AS programa_codigo, p.version AS programa_version, 
                           p.nombre AS programa_nombre, p.regimen, p.unidad_medida, 
                           p.total_periodos, p.nota_minima
                    FROM `{$this->table}` mt
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    INNER JOIN `programas` p ON p.id_programa = mt.id_programa
                    WHERE mt.id_matricula = :id
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en MatriculaModel::findWithDetails({$id}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lista todas las matrículas con datos descriptivos y filtros opcionales
     */
    public function findAllWithDetails(array $filters = []): array {
        try {
            $clauses = [];
            $params = [];

            if (!empty($filters['estado'])) {
                $clauses[] = "mt.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
            if (!empty($filters['id_programa'])) {
                $clauses[] = "mt.id_programa = :id_programa";
                $params[':id_programa'] = (int)$filters['id_programa'];
            }
            if (!empty($filters['periodo_ingreso'])) {
                $clauses[] = "mt.periodo_ingreso = :periodo_ingreso";
                $params[':periodo_ingreso'] = $filters['periodo_ingreso'];
            }

            $whereSql = !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '';

            $sql = "SELECT mt.*, 
                           u.nombres, u.apellidos, u.correo, u.tipo_documento, u.documento,
                           p.codigo AS programa_codigo, p.version AS programa_version, 
                           p.nombre AS programa_nombre
                    FROM `{$this->table}` mt
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    INNER JOIN `programas` p ON p.id_programa = mt.id_programa
                    {$whereSql}
                    ORDER BY mt.id_matricula DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en MatriculaModel::findAllWithDetails(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el estado académico calculado desde la vista v_estado_academico
     */
    public function getEstadoAcademico(int $idMatricula): array {
        try {
            $sql = "SELECT * FROM `v_estado_academico` WHERE `id_matricula` = :id_matricula LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_matricula' => $idMatricula]);
            $res = $stmt->fetch();

            if ($res) {
                return $res;
            }

            return [
                'id_matricula'             => $idMatricula,
                'id_usuario'               => null,
                'materias_pendientes'      => 0,
                'habilitaciones_en_curso'  => 0,
                'estado_academico'         => 'CON_DEUDA_ACADEMICA'
            ];
        } catch (PDOException $e) {
            error_log("Error en MatriculaModel::getEstadoAcademico({$idMatricula}): " . $e->getMessage());
            return [
                'id_matricula'             => $idMatricula,
                'id_usuario'               => null,
                'materias_pendientes'      => 0,
                'habilitaciones_en_curso'  => 0,
                'estado_academico'         => 'CON_DEUDA_ACADEMICA'
            ];
        }
    }

    /**
     * Retorna el listado de materias pendientes para una matrícula desde v_materias_pendientes
     */
    public function getMateriasPendientes(int $idMatricula): array {
        try {
            $sql = "SELECT * FROM `v_materias_pendientes` 
                    WHERE `id_matricula` = :id_matricula 
                    ORDER BY `periodo_bloque` ASC, `codigo_materia` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_matricula' => $idMatricula]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en MatriculaModel::getMateriasPendientes({$idMatricula}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica de forma booleana si un estudiante se encuentra 'LIMPIO' académicamente
     */
    public function esEstudianteLimpio(int $idMatricula): bool {
        $estado = $this->getEstadoAcademico($idMatricula);
        return isset($estado['estado_academico']) && $estado['estado_academico'] === 'LIMPIO';
    }
}
