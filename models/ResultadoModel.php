<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: ResultadoModel.php (en models/)
 * Propósito: DAO para la tabla resultados, lógica de calificaciones,
 *            habilitaciones, homologaciones y anulación auditada
 * =====================================================================
 */

class ResultadoModel extends BaseModel {
    protected string $table = 'resultados';
    protected string $pk = 'id_resultado';

    /**
     * Calcula el estado académico de un resultado basado en las reglas del negocio:
     * - HOMOLOGACION: 'HOMOLOGADO'
     * - HABILITACION: >= notaMinima ? 'APROBADO_HABILITACION' : 'REPROBADO_HABILITACION'
     * - ORDINARIA: >= notaMinima ? 'APROBADO' : (>= 2.50 ? 'HABILITACION_PROGRAMADA' : 'REPROBADO')
     */
    public function calcularEstado(float $calificacion, string $tipoEvaluacion, float $notaMinima = 3.00): string {
        $tipo = strtoupper(trim($tipoEvaluacion));

        if ($tipo === 'HOMOLOGACION') {
            return 'HOMOLOGADO';
        }

        if ($tipo === 'HABILITACION') {
            return $calificacion >= $notaMinima ? 'APROBADO_HABILITACION' : 'REPROBADO_HABILITACION';
        }

        // ORDINARIA por defecto
        if ($calificacion >= $notaMinima) {
            return 'APROBADO';
        }
        if ($calificacion >= 2.50) {
            return 'HABILITACION_PROGRAMADA';
        }
        return 'REPROBADO';
    }

    /**
     * Sobrescribe create() para autocalcular estado_resultado e intento previo al INSERT
     */
    public function create(array $data): int|false {
        // Asignar registrado_por si no viene especificado
        if (empty($data['registrado_por'])) {
            $data['registrado_por'] = Session::getUserId() ?? 1;
        }

        // Obtener el siguiente número de intento si no se definió
        if (empty($data['numero_intento']) && !empty($data['id_matricula']) && !empty($data['id_materia'])) {
            $data['numero_intento'] = $this->getProximoIntento((int)$data['id_matricula'], (int)$data['id_materia']);
        }

        // Determinar nota mínima del programa asociado a la matrícula
        $notaMinima = defined('NOTA_MINIMA_APROBACION') ? NOTA_MINIMA_APROBACION : 3.00;
        if (!empty($data['id_matricula'])) {
            try {
                $sqlP = "SELECT p.nota_minima 
                         FROM `matriculas` mt 
                         INNER JOIN `programas` p ON p.id_programa = mt.id_programa 
                         WHERE mt.id_matricula = :id_matricula LIMIT 1";
                $stmtP = $this->db->prepare($sqlP);
                $stmtP->execute([':id_matricula' => (int)$data['id_matricula']]);
                $notaDb = $stmtP->fetchColumn();
                if ($notaDb !== false) {
                    $notaMinima = (float)$notaDb;
                }
            } catch (PDOException $e) {
                error_log("Error obteniendo nota_minima: " . $e->getMessage());
            }
        }

        // PARCHE A-03: Validar y clamp de calificación en rango [0.00, 5.00]
        if (isset($data['calificacion']) && $data['calificacion'] !== null && $data['calificacion'] !== '') {
            $nota = (float)$data['calificacion'];
            if ($nota < 0.00 || $nota > 5.00) {
                throw new InvalidArgumentException(
                    "Calificación fuera de rango válido (0.00–5.00): {$nota}"
                );
            }
            $data['calificacion'] = round($nota, 2); // normalizar a 2 decimales
            $tipo = $data['tipo_evaluacion'] ?? 'ORDINARIA';
            $data['estado_resultado'] = $this->calcularEstado($data['calificacion'], (string)$tipo, $notaMinima);
        } elseif (empty($data['estado_resultado'])) {
            $data['estado_resultado'] = 'EN_CURSO';
        }

        if (!isset($data['vigente'])) {
            $data['vigente'] = 1;
        }

        if (!isset($data['version_fila'])) {
            $data['version_fila'] = 1;
        }

        return parent::create($data);
    }

    /**
     * Obtiene todas las calificaciones de una matrícula con información de la materia
     */
    public function findByMatricula(int $idMatricula): array {
        try {
            $sql = "SELECT r.*, 
                           m.codigo AS materia_codigo, 
                           m.nombre AS materia_nombre, 
                           m.periodo_bloque, 
                           m.tipo AS materia_tipo, 
                           m.intensidad,
                           u.nombres AS docente_nombres, 
                           u.apellidos AS docente_apellidos
                    FROM `{$this->table}` r
                    INNER JOIN `materias` m ON m.id_materia = r.id_materia
                    LEFT JOIN `usuarios` u ON u.id_usuario = r.registrado_por
                    WHERE r.id_matricula = :id_matricula
                    ORDER BY m.periodo_bloque ASC, m.codigo ASC, r.numero_intento ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_matricula' => $idMatricula]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::findByMatricula({$idMatricula}): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna el historial de intentos y habilitaciones de una materia para un estudiante
     */
    public function findByMatriculaYMateria(int $idMatricula, int $idMateria): array {
        try {
            $sql = "SELECT r.*, 
                           u.nombres AS docente_nombres, 
                           u.apellidos AS docente_apellidos
                    FROM `{$this->table}` r
                    LEFT JOIN `usuarios` u ON u.id_usuario = r.registrado_por
                    WHERE r.id_matricula = :id_matricula AND r.id_materia = :id_materia
                    ORDER BY r.numero_intento ASC, r.registrado_en ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_matricula' => $idMatricula,
                ':id_materia'   => $idMateria
            ]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::findByMatriculaYMateria(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Anula un resultado académico de forma justificada y trazable
     * (Establece vigente = NULL y almacena motivo_anulacion para no violar índice único)
     */
    public function anularResultado(int $id, string $motivo, int $usuarioId): bool {
        try {
            $oldData = $this->findById($id);
            if ($oldData === null) {
                return false;
            }

            $sql = "UPDATE `{$this->table}` 
                    SET `vigente` = NULL, 
                        `motivo_anulacion` = :motivo, 
                        `version_fila` = `version_fila` + 1 
                    WHERE `{$this->pk}` = :id";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':motivo' => substr(trim($motivo), 0, 255),
                ':id'     => $id
            ]);

            if ($success) {
                Audit::log(
                    'RESULT_ANULADO',
                    $this->table,
                    (string)$id,
                    $oldData,
                    ['vigente' => null, 'motivo_anulacion' => $motivo, 'anulado_por' => $usuarioId],
                    "Anulación justificada de calificación: {$motivo}"
                );
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::anularResultado({$id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula el número del siguiente intento de evaluación para la materia
     */
    public function getProximoIntento(int $idMatricula, int $idMateria): int {
        try {
            $sql = "SELECT COALESCE(MAX(`numero_intento`), 0) + 1 
                    FROM `{$this->table}` 
                    WHERE `id_matricula` = :id_matricula AND `id_materia` = :id_materia";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_matricula' => $idMatricula,
                ':id_materia'   => $idMateria
            ]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::getProximoIntento(): " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Verifica si una materia específica ya ha sido aprobada o homologada de forma vigente
     */
    public function tieneAprobada(int $idMatricula, int $idMateria): bool {
        try {
            // PARCHE A-01: vigente IS NULL = registro ANULADO, NO debe contar como aprobado.
            // Solo vigente = 1 son registros activos y válidos.
            $sql = "SELECT COUNT(*) 
                    FROM `{$this->table}` 
                    WHERE `id_matricula` = :id_matricula 
                      AND `id_materia` = :id_materia 
                      AND `vigente` = 1
                      AND `estado_resultado` IN ('APROBADO', 'APROBADO_HABILITACION', 'HOMOLOGADO')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_matricula' => $idMatricula,
                ':id_materia'   => $idMateria
            ]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::tieneAprobada(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resumen estadístico de rendimiento para una matrícula
     */
    public function getResumenByMatricula(int $idMatricula): array {
        try {
            // PARCHE A-02: Excluir registros anulados (vigente IS NULL) del resumen.
            // Solo contabilizar registros activos (vigente = 1).
            $sql = "SELECT 
                        COUNT(*) AS total_registros,
                        SUM(CASE WHEN estado_resultado IN ('APROBADO', 'APROBADO_HABILITACION', 'HOMOLOGADO') THEN 1 ELSE 0 END) AS aprobadas,
                        SUM(CASE WHEN estado_resultado IN ('REPROBADO', 'REPROBADO_HABILITACION') THEN 1 ELSE 0 END) AS reprobadas,
                        SUM(CASE WHEN estado_resultado = 'HABILITACION_PROGRAMADA' THEN 1 ELSE 0 END) AS habilitaciones,
                        SUM(CASE WHEN estado_resultado = 'EN_CURSO' THEN 1 ELSE 0 END) AS en_curso,
                        ROUND(AVG(CASE WHEN calificacion IS NOT NULL THEN calificacion END), 2) AS promedio_acumulado
                    FROM `{$this->table}`
                    WHERE `id_matricula` = :id_matricula AND `vigente` = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_matricula' => $idMatricula]);
            $res = $stmt->fetch();

            return $res ?: [
                'total_registros'    => 0,
                'aprobadas'          => 0,
                'reprobadas'         => 0,
                'habilitaciones'     => 0,
                'en_curso'           => 0,
                'promedio_acumulado' => 0.00
            ];
        } catch (PDOException $e) {
            error_log("Error en ResultadoModel::getResumenByMatricula(): " . $e->getMessage());
            return [
                'total_registros'    => 0,
                'aprobadas'          => 0,
                'reprobadas'         => 0,
                'habilitaciones'     => 0,
                'en_curso'           => 0,
                'promedio_acumulado' => 0.00
            ];
        }
    }
}
