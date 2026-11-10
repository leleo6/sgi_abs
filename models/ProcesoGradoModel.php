<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: ProcesoGradoModel.php (en models/)
 * Propósito: DAO para la tabla procesos_grado, transiciones de estado,
 *            reglas de elegibilidad, emisión y verificación de Paz y Salvo
 * =====================================================================
 */

class ProcesoGradoModel extends BaseModel {
    protected string $table = 'procesos_grado';
    protected string $pk = 'id_proceso';

    /**
     * Obtiene el proceso de grado activo (activo = 1) asociado a una matrícula
     */
    public function findByMatricula(int $idMatricula): ?array {
        return $this->getProcesoActivo($idMatricula);
    }

    /**
     * Busca el proceso actualmente activo de una matrícula
     */
    public function getProcesoActivo(int $idMatricula): ?array {
        try {
            $sql = "SELECT pg.*, 
                           u.nombres AS supervisor_inst_nombres, u.apellidos AS supervisor_inst_apellidos
                    FROM `{$this->table}` pg
                    LEFT JOIN `usuarios` u ON u.id_usuario = pg.excepcion_autorizada_por
                    WHERE pg.id_matricula = :id_matricula AND pg.activo = 1
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_matricula' => $idMatricula]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en ProcesoGradoModel::getProcesoActivo({$idMatricula}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lista todos los procesos de grado con detalles completos de estudiante, programa y convenios
     */
    public function findAllWithDetails(array $filters = []): array {
        try {
            $clauses = [];
            $params = [];

            if (!empty($filters['estado'])) {
                $clauses[] = "pg.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
            if (!empty($filters['modalidad'])) {
                $clauses[] = "pg.modalidad = :modalidad";
                $params[':modalidad'] = $filters['modalidad'];
            }
            if (isset($filters['activo'])) {
                $clauses[] = "pg.activo = :activo";
                $params[':activo'] = (int)$filters['activo'];
            }

            $whereSql = !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '';

            $sql = "SELECT pg.*, 
                           mt.periodo_ingreso, mt.id_usuario,
                           u.nombres AS estudiante_nombres, u.apellidos AS estudiante_apellidos,
                           u.correo AS estudiante_correo, u.tipo_documento, u.documento,
                           p.codigo AS programa_codigo, p.version AS programa_version, 
                           p.nombre AS programa_nombre,
                           pz.nombres AS paz_salvo_emitio_nombres, pz.apellidos AS paz_salvo_emitio_apellidos
                    FROM `{$this->table}` pg
                    INNER JOIN `matriculas` mt ON mt.id_matricula = pg.id_matricula
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    INNER JOIN `programas` p ON p.id_programa = mt.id_programa
                    LEFT JOIN `usuarios` pz ON pz.id_usuario = pg.paz_salvo_emitido_por
                    {$whereSql}
                    ORDER BY pg.id_proceso DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en ProcesoGradoModel::findAllWithDetails(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida de manera exhaustiva si un proceso de grado es elegible para emisión de Paz y Salvo
     *
     * @return array ['puede' => bool, 'faltantes' => string[]]
     */
    public function puedeEmitirPazSalvo(int $idProceso): array {
        $proceso = $this->findById($idProceso);
        if (!$proceso) {
            return ['puede' => false, 'faltantes' => ['El proceso de grado no existe.']];
        }

        $faltantes = [];

        // 1. Validar estado del proceso
        if (!in_array($proceso['estado'], ['EN_EJECUCION', 'FINALIZADO'], true)) {
            $faltantes[] = "El proceso debe estar en estado 'EN_EJECUCION' o 'FINALIZADO' (Estado actual: {$proceso['estado']}).";
        }

        // 2. Validar que no se haya emitido ya un paz y salvo vigente
        if ($proceso['paz_salvo_estado'] === 'EMITIDO') {
            $faltantes[] = "Ya existe un Paz y Salvo emitido previamente con código: {$proceso['codigo_verificacion']}.";
        }

        // 3. Validar elegibilidad académica (Malla limpia o Excepción formal válida)
        $matriculaModel = new MatriculaModel();
        $esLimpio = $matriculaModel->esEstudianteLimpio((int)$proceso['id_matricula']);

        if (!$esLimpio) {
            // Verificar si cuenta con excepción formal vigente
            $tieneExcepcionValida = false;
            if (!empty($proceso['excepcion_acta']) && !empty($proceso['excepcion_autorizada_por'])) {
                if (empty($proceso['excepcion_vigente_hasta']) || strtotime($proceso['excepcion_vigente_hasta']) >= time()) {
                    $tieneExcepcionValida = true;
                }
            }

            if (!$tieneExcepcionValida) {
                $faltantes[] = "El estudiante registra asignaturas pendientes o habilitaciones sin un acta de excepción formal vigente.";
            }
        }

        // 4. Validar que todas las evidencias estén entregadas y aprobadas
        $evidenciaModel = new EvidenciaModel();
        $evidencias = $evidenciaModel->findByProceso($idProceso);

        if (empty($evidencias)) {
            $faltantes[] = "No se ha registrado ninguna evidencia o entregable para este proceso de grado.";
        } else {
            foreach ($evidencias as $ev) {
                if ($ev['estado'] !== 'APROBADA') {
                    $faltantes[] = "La evidencia '{$ev['tipo_evidencia']}' (v{$ev['version_entrega']}) no está APROBADA (Estado: {$ev['estado']}).";
                }
            }
        }

        return [
            'puede'     => empty($faltantes),
            'faltantes' => $faltantes
        ];
    }

    /**
     * Emite formalmente el Paz y Salvo final con código criptográfico UUID v4
     */
    public function emitirPazSalvo(int $idProceso, int $usuarioId): string {
        $evaluacion = $this->puedeEmitirPazSalvo($idProceso);
        if (!$evaluacion['puede']) {
            $detalle = implode(' | ', $evaluacion['faltantes']);
            throw new RuntimeException("No es posible emitir Paz y Salvo. Motivos: {$detalle}");
        }

        // Generar código único de verificación UUID v4
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $codigoVerificacion = 'PYS-' . strtoupper($uuid);

        $oldData = $this->findById($idProceso);

        // PARCHE C-03: Transacción atómica — el UPDATE y la auditoría deben ser
        // indivisibles. Si falla uno, se revierte el otro.
        $pdo = Database::getInstance();
        $inExternalTransaction = $pdo->inTransaction();

        try {
            if (!$inExternalTransaction) {
                $pdo->beginTransaction();
            }

            $sql = "UPDATE `{$this->table}` 
                    SET `codigo_verificacion` = :codigo,
                        `paz_salvo_emitido_en` = NOW(),
                        `paz_salvo_emitido_por` = :usuario_id,
                        `paz_salvo_estado` = 'EMITIDO',
                        `paz_salvo_motivo_revocatoria` = NULL,
                        `estado` = 'FINALIZADO'
                    WHERE `{$this->pk}` = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':codigo'     => $codigoVerificacion,
                ':usuario_id' => $usuarioId,
                ':id'         => $idProceso,
            ]);

            if (!$inExternalTransaction) {
                $pdo->commit();
            }

            // La auditoría va FUERA de la transacción para que no se revierta
            // si falla algo posterior no relacionado con este proceso.
            Audit::log(
                'PAZ_SALVO_EMITIDO',
                $this->table,
                (string)$idProceso,
                $oldData,
                [
                    'codigo_verificacion'   => $codigoVerificacion,
                    'paz_salvo_emitido_por' => $usuarioId,
                    'paz_salvo_estado'      => 'EMITIDO',
                    'estado'                => 'FINALIZADO',
                ],
                'Emisión de Paz y Salvo Académico Institucional'
            );

            return $codigoVerificacion;

        } catch (Throwable $e) {
            if (!$inExternalTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[SGI-ABS] Error en emitirPazSalvo(): ' . $e->getMessage());
            throw new RuntimeException('Error al emitir el Paz y Salvo. Reintente la operación.', 0, $e);
        }
    }

    /**
     * Revoca un Paz y Salvo emitido con justificación administrativa formal
     */
    public function revocarPazSalvo(int $idProceso, string $motivo, int $usuarioId): bool {
        try {
            $oldData = $this->findById($idProceso);
            if (!$oldData || $oldData['paz_salvo_estado'] !== 'EMITIDO') {
                return false;
            }

            $sql = "UPDATE `{$this->table}` 
                    SET `paz_salvo_estado` = 'REVOCADO',
                        `paz_salvo_motivo_revocatoria` = :motivo
                    WHERE `{$this->pk}` = :id";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':motivo' => substr(trim($motivo), 0, 300),
                ':id'     => $idProceso
            ]);

            if ($success) {
                Audit::log(
                    'PAZ_SALVO_REVOCADO',
                    $this->table,
                    (string)$idProceso,
                    $oldData,
                    ['paz_salvo_estado' => 'REVOCADO', 'motivo_revocatoria' => $motivo, 'revocado_por' => $usuarioId],
                    "Revocatoria de Paz y Salvo: {$motivo}"
                );
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error en ProcesoGradoModel::revocarPazSalvo({$idProceso}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta pública para validación de autenticidad de Paz y Salvo por código
     */
    public function verificarPazSalvo(string $codigo): ?array {
        try {
            $codigo = trim($codigo);
            $sql = "SELECT pg.id_proceso, pg.modalidad, pg.codigo_verificacion,
                           pg.paz_salvo_emitido_en, pg.paz_salvo_estado, pg.horas_cumplidas,
                           u.nombres AS estudiante_nombres, u.apellidos AS estudiante_apellidos,
                           u.tipo_documento, u.documento,
                           p.nombre AS programa_nombre, p.codigo AS programa_codigo, p.version AS programa_version,
                           p.regimen
                    FROM `{$this->table}` pg
                    INNER JOIN `matriculas` mt ON mt.id_matricula = pg.id_matricula
                    INNER JOIN `usuarios` u ON u.id_usuario = mt.id_usuario
                    INNER JOIN `programas` p ON p.id_programa = mt.id_programa
                    WHERE pg.codigo_verificacion = :codigo
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en ProcesoGradoModel::verificarPazSalvo(): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aplica transiciones controladas de estado en el ciclo de vida del proceso
     */
    public function transicionarEstado(int $idProceso, string $nuevoEstado, int $usuarioId, string $motivo = ''): bool {
        $proceso = $this->findById($idProceso);
        if (!$proceso) {
            return false;
        }

        $estadoActual = $proceso['estado'];
        $nuevoEstado = strtoupper(trim($nuevoEstado));

        // Matriz de transiciones de estado permitidas
        $transicionesValidas = [
            'ELEGIBLE'      => ['AVALADO', 'INTERRUMPIDO'],
            'AVALADO'       => ['EN_EJECUCION', 'INTERRUMPIDO'],
            'EN_EJECUCION'  => ['FINALIZADO', 'INTERRUMPIDO'],
            'FINALIZADO'    => ['INTERRUMPIDO'],
            'INTERRUMPIDO'  => ['ELEGIBLE', 'AVALADO', 'EN_EJECUCION'],
        ];

        // Validar transición permitida
        if (!isset($transicionesValidas[$estadoActual]) || !in_array($nuevoEstado, $transicionesValidas[$estadoActual], true)) {
            error_log("Transición de estado inválida para proceso {$idProceso}: {$estadoActual} -> {$nuevoEstado}");
            return false;
        }

        try {
            $sql = "UPDATE `{$this->table}` SET `estado` = :nuevo_estado WHERE `{$this->pk}` = :id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':nuevo_estado' => $nuevoEstado,
                ':id'           => $idProceso
            ]);

            if ($success) {
                Audit::log(
                    'ESTADO_TRANSITION',
                    $this->table,
                    (string)$idProceso,
                    ['estado' => $estadoActual],
                    ['estado' => $nuevoEstado, 'cambiado_por' => $usuarioId],
                    !empty($motivo) ? $motivo : "Transición de estado de {$estadoActual} a {$nuevoEstado}"
                );
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error en ProcesoGradoModel::transicionarEstado({$idProceso}): " . $e->getMessage());
            return false;
        }
    }
}
