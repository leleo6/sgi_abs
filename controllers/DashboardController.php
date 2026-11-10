<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $rol  = Auth::role();
        $user = Auth::user();
        $stats                = [];
        $alertas              = [];
        $actividadReciente    = [];
        $ultimosProcesos      = [];
        $evidenciasPendientes = [];
        $materiasPendientes   = [];
        $procesoActivo        = null;
        $estadoAcademico      = 'LIMPIO';
        $progresoMalla        = 0;

        try {
            switch ($rol) {
                case ROL_ADMIN:
                    $usuarioModel   = new UsuarioModel();
                    $programaModel  = new ProgramaModel();
                    $matriculaModel = new MatriculaModel();
                    $procesoModel   = new ProcesoGradoModel();
                    $auditoriaModel = new AuditoriaModel();
                    $evidenciaModel = new EvidenciaModel();

                    $totalUsuarios   = count($usuarioModel->findAll());
                    $totalProgramas  = count($programaModel->findAll());
                    $totalMatriculas = count($matriculaModel->findAll());
                    $procesosActivos = count($procesoModel->findAll(['estado' => 'EN_EJECUCION']));

                    $stats = [
                        'usuarios'         => $totalUsuarios,
                        'total_usuarios'   => $totalUsuarios,
                        'programas'        => $totalProgramas,
                        'total_programas'  => $totalProgramas,
                        'matriculas'       => $totalMatriculas,
                        'total_matriculas' => $totalMatriculas,
                        'procesos_activos' => $procesosActivos,
                        'por_rol'          => $usuarioModel->countByRole(),
                    ];

                    $ultimosProcesos      = $procesoModel->findAllWithDetails();
                    $evidenciasPendientes = $evidenciaModel->findPendientes();
                    $actividadReciente    = $auditoriaModel->findPaginated([], 5, 0);
                    $alertas              = $actividadReciente;
                    break;

                case ROL_ESTUDIANTE:
                    $matriculaModel = new MatriculaModel();
                    $procesoModel   = new ProcesoGradoModel();
                    $auditoriaModel = new AuditoriaModel();
                    $materiaModel   = new MateriaModel();
                    $resultadoModel = new ResultadoModel();

                    $matricula = $matriculaModel->findByUsuario((int)$user['id_usuario']);
                    if ($matricula) {
                        $idMatricula = (int)$matricula['id_matricula'];
                        $estadoAcademicoData = $matriculaModel->getEstadoAcademico($idMatricula);
                        $estadoAcademico = is_array($estadoAcademicoData) 
                            ? ($estadoAcademicoData['estado_academico'] ?? 'LIMPIO') 
                            : (string)$estadoAcademicoData;

                        $materiasPendientes = $matriculaModel->getMateriasPendientes($idMatricula);
                        $procesoActivo      = $procesoModel->getProcesoActivo($idMatricula);
                        $resumen            = $resultadoModel->getResumenByMatricula($idMatricula);

                        $totalMaterias = count($materiaModel->findByPrograma((int)$matricula['id_programa']));
                        $aprobadas     = (int)($resumen['aprobadas'] ?? 0);
                        $progresoMalla = $totalMaterias > 0 
                            ? (int)min(100, round(($aprobadas / $totalMaterias) * 100)) 
                            : 0;

                        $stats = [
                            'matricula'        => $matricula,
                            'estado_academico' => $estadoAcademico,
                            'resumen'          => $resumen,
                            'progreso_pct'     => $progresoMalla,
                            'aprobadas'        => $aprobadas,
                            'total_materias'   => $totalMaterias,
                            'pendientes'       => count($materiasPendientes),
                        ];
                    }
                    $actividadReciente = $auditoriaModel->findPaginated(['id_usuario' => (int)$user['id_usuario']], 5, 0);
                    $alertas           = $actividadReciente;
                    break;

                case ROL_COORDINACION:
                case ROL_PRACTICAS:
                    $procesoModel   = new ProcesoGradoModel();
                    $evidenciaModel = new EvidenciaModel();
                    $auditoriaModel = new AuditoriaModel();
                    $matriculaModel = new MatriculaModel();

                    $procesosActivos    = count($procesoModel->findAll(['estado' => 'EN_EJECUCION']));
                    $procesosElegibles  = count($procesoModel->findAll(['estado' => 'ELEGIBLE']));
                    $evidenciasPend     = count($evidenciaModel->findPendientes());
                    $matriculasActivas  = count($matriculaModel->findAll(['estado' => 'ACTIVA']));

                    $stats = [
                        'procesos_activos'      => $procesosActivos,
                        'procesos_elegibles'    => $procesosElegibles,
                        'evidencias_pendientes' => $evidenciasPend,
                        'matriculas_activas'    => $matriculasActivas,
                    ];
                    $ultimosProcesos      = $procesoModel->findAllWithDetails();
                    $evidenciasPendientes = $evidenciaModel->findPendientes();
                    $actividadReciente    = $auditoriaModel->findPaginated([], 5, 0);
                    $alertas              = $actividadReciente;
                    break;

                case ROL_REGISTRO:
                    $usuarioModel   = new UsuarioModel();
                    $matriculaModel = new MatriculaModel();
                    $programaModel  = new ProgramaModel();
                    $resultadoModel = new ResultadoModel();
                    $auditoriaModel = new AuditoriaModel();

                    $stats = [
                        'estudiantes'    => count($usuarioModel->findAll(['rol' => ROL_ESTUDIANTE])),
                        'matriculas'     => count($matriculaModel->findAll()),
                        'programas'      => count($programaModel->findAll()),
                        'calificaciones' => count($resultadoModel->findAll()),
                    ];
                    $actividadReciente = $auditoriaModel->findPaginated([], 5, 0);
                    $alertas           = $actividadReciente;
                    break;

                case ROL_DOCENTE:
                    $resultadoModel = new ResultadoModel();
                    $auditoriaModel = new AuditoriaModel();
                    $materiaModel   = new MateriaModel();

                    $misResultados = $resultadoModel->findAll([], 'registrado_en DESC');
                    $stats = [
                        'calificaciones_total' => count($misResultados),
                        'materias_total'       => count($materiaModel->findAll()),
                        'resultados_recientes' => $misResultados,
                    ];
                    $actividadReciente = $auditoriaModel->findPaginated(['id_usuario' => (int)$user['id_usuario']], 5, 0);
                    $alertas           = $actividadReciente;
                    break;
            }
        } catch (Throwable $e) {
            error_log('[Dashboard] ' . $e->getMessage());
        }

        $this->render('dashboard/index', [
            'title'                => 'Panel de Control',
            'stats'                => $stats,
            'alertas'              => $alertas,
            'actividadReciente'    => $actividadReciente,
            'ultimosProcesos'      => $ultimosProcesos,
            'evidenciasPendientes' => $evidenciasPendientes,
            'materiasPendientes'   => $materiasPendientes,
            'procesoActivo'        => $procesoActivo,
            'estadoAcademico'      => $estadoAcademico,
            'progresoMalla'        => $progresoMalla,
        ]);
    }
}
