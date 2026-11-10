<?php
declare(strict_types=1);

class ProcesoGradoController extends Controller
{
    private ProcesoGradoModel $model;
    private MatriculaModel    $matriculaModel;
    private EvidenciaModel    $evidenciaModel;

    public function __construct()
    {
        $this->model          = new ProcesoGradoModel();
        $this->matriculaModel = new MatriculaModel();
        $this->evidenciaModel = new EvidenciaModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filtros = [];

        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if ($matricula) {
                $proceso = $this->model->getProcesoActivo((int)$matricula['id_matricula']);
                if ($proceso) {
                    $this->redirectTo('procesos', 'detalle', ['id' => $proceso['id_proceso']]);
                }
            }
            Session::setFlash('info', 'No tiene un proceso de grado activo.');
            $this->redirectTo('dashboard');
        }

        $filtroEstado = $this->getQuery('estado', '');
        if ($filtroEstado) { $filtros['estado'] = $filtroEstado; }
        $procesos = $this->model->findAllWithDetails($filtros);

        $this->render('procesos/index', [
            'title'    => 'Procesos de Grado',
            'procesos' => $procesos,
            'filtros'  => $filtros,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_PRACTICAS, ROL_ADMIN]);
        $errores    = [];
        $proceso    = null;
        $matriculas = $this->matriculaModel->findAllWithDetails(['estado' => 'ACTIVA']);

        if ($this->isPost()) {
            $this->validateCsrf();
            $data = $this->recogerDatos();
            $errores = $this->validar($data);

            if (empty($errores)) {
                // ══ MOTOR DE ELEGIBILIDAD (Regla de "Estudiante Limpio") ══
                $idMatricula = (int)$data['id_matricula'];
                if (!$this->matriculaModel->esEstudianteLimpio($idMatricula)) {
                    $pendientes = $this->matriculaModel->getMateriasPendientes($idMatricula);
                    $matricula  = $this->matriculaModel->findById($idMatricula);
                    // Registrar intento bloqueado en auditoría
                    Audit::log(
                        'BLOQUEO_PRACTICA',
                        'procesos_grado',
                        'N/A',
                        null,
                        ['id_matricula' => $idMatricula, 'motivo' => 'CON_DEUDA_ACADEMICA'],
                        'Intento de crear proceso con deuda académica'
                    );
                    $this->render('procesos/form', [
                        'title'              => 'Nuevo Proceso de Grado',
                        'proceso'            => $data,
                        'matriculas'         => $matriculas,
                        'errores'            => ['El estudiante tiene DEUDA ACADÉMICA. No puede iniciar proceso de grado.'],
                        'materiasPendientes' => $pendientes,
                        'bloqueado'          => true,
                    ]);
                    return;
                }

                try {
                    $id = $this->model->create($data);
                    Audit::log('CREACION_PROCESO', 'procesos_grado', (string)$id, null, $data);
                    Session::setFlash('success', 'Proceso de grado registrado exitosamente.');
                    $this->redirectTo('procesos', 'detalle', ['id' => $id]);
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $proceso = $data;
        }

        $this->render('procesos/form', [
            'title'              => 'Nuevo Proceso de Grado',
            'proceso'            => $proceso,
            'matriculas'         => $matriculas,
            'errores'            => $errores,
            'materiasPendientes' => [],
            'bloqueado'          => false,
        ]);
    }

    public function editar(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_PRACTICAS, ROL_ADMIN]);
        $id      = (int)$this->getQuery('id', 0);
        $proceso = $this->model->findById($id);
        if (!$proceso) { Session::setFlash('error', 'Proceso no encontrado.'); $this->redirectTo('procesos'); }

        $errores    = [];
        $matriculas = $this->matriculaModel->findAllWithDetails();

        if ($this->isPost()) {
            $this->validateCsrf();
            $data = $this->recogerDatosSeguimiento();
            try {
                $anterior = $proceso;
                $this->model->update($id, $data);
                Audit::log('ACTUALIZACION_PROCESO', 'procesos_grado', (string)$id, $anterior, $data);
                Session::setFlash('success', 'Proceso actualizado.');
                $this->redirectTo('procesos', 'detalle', ['id' => $id]);
            } catch (Throwable $e) {
                $errores[] = 'Error: ' . $e->getMessage();
            }
            $proceso = array_merge($proceso, $data);
        }

        $this->render('procesos/form', [
            'title'              => 'Editar Proceso',
            'proceso'            => $proceso,
            'matriculas'         => $matriculas,
            'errores'            => $errores,
            'materiasPendientes' => [],
            'bloqueado'          => false,
        ]);
    }

    public function detalle(): void
    {
        $this->requireAuth();
        $id = (int)$this->getQuery('id', 0);

        // ESTUDIANTE solo puede ver su propio proceso
        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if (!$matricula) { $this->redirectTo('dashboard'); }
            $propio = $this->model->getProcesoActivo((int)$matricula['id_matricula']);
            if (!$propio || (int)$propio['id_proceso'] !== $id) {
                http_response_code(403); $title = 'Acceso denegado';
                include BASE_PATH . '/views/errors/403.php'; exit;
            }
        }

        $proceso   = $this->model->findById($id);
        if (!$proceso) { Session::setFlash('error', 'Proceso no encontrado.'); $this->redirectTo('procesos'); }
        $evidencias= $this->evidenciaModel->findByProceso($id);
        $chequeo   = $this->model->puedeEmitirPazSalvo($id);

        $this->render('procesos/detalle', [
            'title'     => 'Detalle del Proceso de Grado',
            'proceso'   => $proceso,
            'evidencias'=> $evidencias,
            'chequeo'   => $chequeo,
        ]);
    }

    public function transicion(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_PRACTICAS, ROL_ADMIN]);
        if (!$this->isPost()) { $this->redirectTo('procesos'); }
        $this->validateCsrf();

        $id          = (int)$this->getPost('id', 0);
        $nuevoEstado = $this->getPost('nuevo_estado', '');
        $motivo      = trim($this->getPost('motivo', ''));

        $proceso = $this->model->findById($id);
        if (!$proceso) { Session::setFlash('error', 'Proceso no encontrado.'); $this->redirectTo('procesos'); }

        $ok = $this->model->transicionarEstado($id, $nuevoEstado, (int)Auth::id(), $motivo);
        if ($ok) {
            Audit::log('CAMBIO_ESTADO_PROCESO', 'procesos_grado', (string)$id,
                ['estado' => $proceso['estado']], ['estado' => $nuevoEstado], $motivo);
            Session::setFlash('success', 'Estado actualizado a: ' . $nuevoEstado);
        } else {
            Session::setFlash('error', 'Transición de estado no permitida.');
        }
        $this->redirectTo('procesos', 'detalle', ['id' => $id]);
    }

    public function pazSalvo(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_ADMIN]);
        if (!$this->isPost()) { $this->redirectTo('procesos'); }
        $this->validateCsrf();

        $id = (int)$this->getPost('id', 0);
        $chequeo = $this->model->puedeEmitirPazSalvo($id);

        if (!$chequeo['puede']) {
            Session::setFlash('error', 'No se puede emitir el Paz y Salvo. Faltan: ' . implode(', ', $chequeo['faltantes']));
            $this->redirectTo('procesos', 'detalle', ['id' => $id]);
        }

        try {
            $codigo = $this->model->emitirPazSalvo($id, (int)Auth::id());
            Audit::log('EMISION_PAZ_SALVO', 'procesos_grado', (string)$id, null,
                ['codigo_verificacion' => $codigo, 'emitido_por' => Auth::id()]);
            Session::setFlash('success', 'Paz y Salvo emitido. Código: ' . $codigo);
        } catch (Throwable $e) {
            Session::setFlash('error', 'Error al emitir: ' . $e->getMessage());
        }
        $this->redirectTo('procesos', 'detalle', ['id' => $id]);
    }

    public function revocarPazSalvo(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_ADMIN]);
        if (!$this->isPost()) { $this->redirectTo('procesos'); }
        $this->validateCsrf();

        $id     = (int)$this->getPost('id', 0);
        $motivo = trim($this->getPost('motivo', ''));
        if (empty($motivo)) {
            Session::setFlash('error', 'El motivo de revocatoria es obligatorio.');
            $this->redirectTo('procesos', 'detalle', ['id' => $id]);
        }

        $proceso = $this->model->findById($id);
        $ok = $this->model->revocarPazSalvo($id, $motivo, (int)Auth::id());
        if ($ok) {
            Audit::log('REVOCATORIA_PAZ_SALVO', 'procesos_grado', (string)$id,
                $proceso, ['paz_salvo_estado' => 'REVOCADO'], $motivo);
            Session::setFlash('success', 'Paz y Salvo revocado.');
        } else {
            Session::setFlash('error', 'No se pudo revocar el Paz y Salvo.');
        }
        $this->redirectTo('procesos', 'detalle', ['id' => $id]);
    }

    /** Verificación pública del Paz y Salvo (sin autenticación) */
    public function verificar(): void
    {
        $codigo  = trim($this->getQuery('codigo', ''));
        $proceso = null;
        if ($codigo) {
            $proceso = $this->model->verificarPazSalvo($codigo);
        }
        // Vista standalone sin layout de autenticación
        $title = 'Verificación de Paz y Salvo';
        include BASE_PATH . '/views/procesos/verificar.php';
    }

    private function recogerDatos(): array
    {
        return [
            'id_matricula'       => (int)$this->getPost('id_matricula', 0),
            'modalidad'          => $this->getPost('modalidad', 'PRACTICA'),
            'entidad_nit'        => trim($this->getPost('entidad_nit', '')) ?: null,
            'entidad_razon_social'=> trim($this->getPost('entidad_razon_social', '')) ?: null,
            'convenio_numero'    => trim($this->getPost('convenio_numero', '')) ?: null,
            'convenio_inicio'    => $this->getPost('convenio_inicio') ?: null,
            'convenio_fin'       => $this->getPost('convenio_fin') ?: null,
            'titulo_proyecto'    => trim($this->getPost('titulo_proyecto', '')) ?: null,
            'supervisor_nombre'  => trim($this->getPost('supervisor_nombre', '')) ?: null,
            'supervisor_correo'  => trim($this->getPost('supervisor_correo', '')) ?: null,
            'fecha_inicio'       => $this->getPost('fecha_inicio') ?: null,
            'fecha_fin'          => $this->getPost('fecha_fin') ?: null,
            'estado'             => 'ELEGIBLE',
        ];
    }

    private function recogerDatosSeguimiento(): array
    {
        return [
            'entidad_nit'         => trim($this->getPost('entidad_nit', '')) ?: null,
            'entidad_razon_social'=> trim($this->getPost('entidad_razon_social', '')) ?: null,
            'convenio_numero'     => trim($this->getPost('convenio_numero', '')) ?: null,
            'convenio_inicio'     => $this->getPost('convenio_inicio') ?: null,
            'convenio_fin'        => $this->getPost('convenio_fin') ?: null,
            'titulo_proyecto'     => trim($this->getPost('titulo_proyecto', '')) ?: null,
            'supervisor_nombre'   => trim($this->getPost('supervisor_nombre', '')) ?: null,
            'supervisor_correo'   => trim($this->getPost('supervisor_correo', '')) ?: null,
            'fecha_inicio'        => $this->getPost('fecha_inicio') ?: null,
            'fecha_fin'           => $this->getPost('fecha_fin') ?: null,
            'horas_cumplidas'     => (float)$this->getPost('horas_cumplidas', 0),
        ];
    }

    private function validar(array $data): array
    {
        $errores = [];
        if ($data['id_matricula'] <= 0) { $errores[] = 'Debe seleccionar una matrícula.'; }
        if (empty($data['modalidad']))   { $errores[] = 'La modalidad es requerida.'; }
        if (in_array($data['modalidad'], ['PRACTICA', 'PASANTIA'])) {
            if (empty($data['entidad_nit']))         { $errores[] = 'El NIT de la entidad es requerido para esta modalidad.'; }
            if (empty($data['entidad_razon_social'])) { $errores[] = 'La razón social de la entidad es requerida.'; }
        }
        if ($data['modalidad'] === 'PROYECTO' && empty($data['titulo_proyecto'])) {
            $errores[] = 'El título del proyecto es requerido.';
        }
        return $errores;
    }
}
