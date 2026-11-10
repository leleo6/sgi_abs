<?php
declare(strict_types=1);

class ReporteController extends Controller
{
    public function index(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION, ROL_PRACTICAS, ROL_REGISTRO]);

        $matriculaModel = new MatriculaModel();
        $procesoModel   = new ProcesoGradoModel();
        $resultadoModel = new ResultadoModel();
        $usuarioModel   = new UsuarioModel();

        $stats = [
            'total_estudiantes'   => count($usuarioModel->getAllWithRole(ROL_ESTUDIANTE)),
            'total_matriculas'    => count($matriculaModel->findAll()),
            'procesos_activos'    => count($procesoModel->findAll(['estado' => 'EN_EJECUCION'])),
            'paz_salvos_emitidos' => count($procesoModel->findAll(['paz_salvo_estado' => 'EMITIDO'])),
        ];

        $this->render('reportes/index', [
            'title' => 'Reportes',
            'stats' => $stats,
        ]);
    }

    public function estudiantesLimpios(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION, ROL_PRACTICAS, ROL_REGISTRO]);

        $db       = Database::getInstance();
        $filtroP  = (int)$this->getQuery('id_programa', 0);
        $exportar = $this->getQuery('export', '') === 'csv';

        $sql = 'SELECT ea.id_matricula, ea.id_usuario, ea.materias_pendientes,
                       ea.habilitaciones_en_curso, ea.estado_academico,
                       u.nombres, u.apellidos, u.correo, u.documento,
                       p.nombre AS nombre_programa, p.codigo AS codigo_programa,
                       m.periodo_ingreso
                FROM v_estado_academico ea
                JOIN matriculas m ON m.id_matricula = ea.id_matricula
                JOIN usuarios u   ON u.id_usuario   = ea.id_usuario
                JOIN programas p  ON p.id_programa  = m.id_programa';
        $params = [];
        if ($filtroP) {
            $sql .= ' WHERE m.id_programa = ?';
            $params[] = $filtroP;
        }
        $sql .= ' ORDER BY ea.estado_academico ASC, u.apellidos ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        if ($exportar) {
            $this->exportarCSV($datos, [
                'Matrícula','Nombres','Apellidos','Correo','Documento',
                'Programa','Período','Estado Académico','Materias Pendientes',
            ], function(array $row): array {
                return [
                    $row['id_matricula'], $row['nombres'], $row['apellidos'],
                    $row['correo'], $row['documento'], $row['nombre_programa'],
                    $row['periodo_ingreso'], $row['estado_academico'],
                    $row['materias_pendientes'],
                ];
            }, 'estudiantes_estado_academico');
        }

        $programas = (new ProgramaModel())->findAll([], 'nombre ASC');
        $this->render('reportes/index', [
            'title'         => 'Reporte: Estado Académico',
            'stats'         => [],
            'datos'         => $datos,
            'programas'     => $programas,
            'filtroPrograma'=> $filtroP,
            'tipoReporte'   => 'estudiantesLimpios',
        ]);
    }

    public function materiasPendientes(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION, ROL_REGISTRO]);

        $db      = Database::getInstance();
        $filtroP = (int)$this->getQuery('id_programa', 0);
        $exportar= $this->getQuery('export', '') === 'csv';

        $sql = 'SELECT mp.id_matricula, mp.codigo_materia, mp.nombre_materia,
                       mp.periodo_bloque, u.nombres, u.apellidos,
                       p.nombre AS nombre_programa
                FROM v_materias_pendientes mp
                JOIN matriculas m ON m.id_matricula = mp.id_matricula
                JOIN usuarios u   ON u.id_usuario   = m.id_usuario
                JOIN programas p  ON p.id_programa  = m.id_programa';
        $params = [];
        if ($filtroP) { $sql .= ' WHERE m.id_programa = ?'; $params[] = $filtroP; }
        $sql .= ' ORDER BY u.apellidos ASC, mp.periodo_bloque ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        if ($exportar) {
            $this->exportarCSV($datos, [
                'Matrícula','Estudiante','Código Materia','Nombre Materia','Período/Bloque','Programa',
            ], function(array $row): array {
                return [
                    $row['id_matricula'],
                    $row['apellidos'] . ', ' . $row['nombres'],
                    $row['codigo_materia'], $row['nombre_materia'],
                    $row['periodo_bloque'], $row['nombre_programa'],
                ];
            }, 'materias_pendientes');
        }

        $programas = (new ProgramaModel())->findAll([], 'nombre ASC');
        $this->render('reportes/index', [
            'title'         => 'Reporte: Materias Pendientes',
            'stats'         => [],
            'datos'         => $datos,
            'programas'     => $programas,
            'filtroPrograma'=> $filtroP,
            'tipoReporte'   => 'materiasPendientes',
        ]);
    }

    public function procesosActivos(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION, ROL_PRACTICAS]);

        $procesoModel = new ProcesoGradoModel();
        $exportar     = $this->getQuery('export', '') === 'csv';
        $datos        = $procesoModel->findAllWithDetails(['estado' => 'EN_EJECUCION']);

        if ($exportar) {
            $this->exportarCSV($datos, [
                'ID','Estudiante','Modalidad','Entidad','Inicio','Fin','Horas','Estado',
            ], function(array $row): array {
                return [
                    $row['id_proceso'],
                    ($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? ''),
                    $row['modalidad'], $row['entidad_razon_social'] ?? '-',
                    $row['fecha_inicio'] ?? '-', $row['fecha_fin'] ?? '-',
                    $row['horas_cumplidas'], $row['estado'],
                ];
            }, 'procesos_activos');
        }

        $this->render('reportes/index', [
            'title'       => 'Reporte: Procesos Activos',
            'stats'       => [],
            'datos'       => $datos,
            'programas'   => [],
            'tipoReporte' => 'procesosActivos',
        ]);
    }

    // ── Helper exportación CSV ──────────────────────────────────────────────
    private function exportarCSV(array $datos, array $cabeceras, callable $mapper, string $nombre): never
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        $out = fopen('php://output', 'wb');
        // BOM para Excel
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $cabeceras, ';');
        foreach ($datos as $row) {
            fputcsv($out, $mapper($row), ';');
        }
        fclose($out);
        exit;
    }
}
