<?php
declare(strict_types=1);

class EvidenciaController extends Controller
{
    private EvidenciaModel    $model;
    private ProcesoGradoModel $procesoModel;
    private MatriculaModel    $matriculaModel;

    public function __construct()
    {
        $this->model          = new EvidenciaModel();
        $this->procesoModel   = new ProcesoGradoModel();
        $this->matriculaModel = new MatriculaModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filtros = [];

        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if (!$matricula) { $this->redirectTo('dashboard'); }
            $proceso = $this->procesoModel->getProcesoActivo((int)$matricula['id_matricula']);
            if (!$proceso) { Session::setFlash('info', 'No tiene proceso activo.'); $this->redirectTo('dashboard'); }
            $evidencias = $this->model->findByProceso((int)$proceso['id_proceso']);
        } else {
            $filtroEstado = $this->getQuery('estado', '');
            $filtroProc   = (int)$this->getQuery('id_proceso', 0);
            if ($filtroEstado) { $filtros['e.estado'] = $filtroEstado; }
            if ($filtroProc)   { $filtros['e.id_proceso'] = $filtroProc; }
            $evidencias = $this->model->findAllWithDetails($filtros);
        }

        $this->render('evidencias/index', [
            'title'      => 'Evidencias',
            'evidencias' => $evidencias,
            'filtros'    => $filtros,
        ]);
    }

    public function subir(): void
    {
        $this->requireAuth();
        $errores       = [];
        $evidencia     = null;
        $tiposEvidencia= ['INFORME_FINAL','BITACORA','CARTA_CUMPLIMIENTO','CEDULA','CARNET','APE','EFEP','ARL','OTRO'];

        // ESTUDIANTE solo puede subir evidencias de su propio proceso
        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if (!$matricula) { $this->redirectTo('dashboard'); }
            $proceso = $this->procesoModel->getProcesoActivo((int)$matricula['id_matricula']);
            if (!$proceso) { Session::setFlash('error', 'No tiene proceso de grado activo.'); $this->redirectTo('dashboard'); }
            $procesos = [$proceso];
        } else {
            $procesos = $this->procesoModel->findAllWithDetails(['estado' => 'EN_EJECUCION']);
        }

        if ($this->isPost()) {
            $this->validateCsrf();
            $data = [
                'id_proceso'      => (int)$this->getPost('id_proceso', 0),
                'tipo_evidencia'  => trim($this->getPost('tipo_evidencia', '')),
                'observaciones'   => trim($this->getPost('observaciones', '')) ?: null,
            ];
            $errores = $this->validar($data);

            // Verificar IDOR para ESTUDIANTE
            if (Auth::role() === ROL_ESTUDIANTE && empty($errores)) {
                $proc = $this->procesoModel->findById($data['id_proceso']);
                $mat  = $this->matriculaModel->findByUsuario((int)Auth::id());
                if (!$proc || !$mat || (int)$proc['id_matricula'] !== (int)$mat['id_matricula']) {
                    http_response_code(403); $title = 'Acceso denegado';
                    include BASE_PATH . '/views/errors/403.php'; exit;
                }
            }

            if (empty($errores) && isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = FileUpload::upload($_FILES['archivo'], 'evidencias');
                if (!$upload['ok']) {
                    $errores[] = 'Error en la carga del archivo: ' . $upload['error'];
                } else {
                    $data['nombre_archivo']      = $upload['name'];
                    $data['ruta_almacenamiento'] = $upload['path'];
                    $data['mime_type']           = $upload['mime'];
                    $data['tamano_bytes']        = $upload['size'];
                    $data['hash_sha256']         = $upload['hash'];
                    $data['subido_por']          = Auth::id();
                    $data['fecha_entrega']       = date('Y-m-d H:i:s');
                    $data['estado']              = 'ENTREGADA';

                    // Calcular version_entrega
                    $existentes = $this->model->findAll([
                        'id_proceso'     => $data['id_proceso'],
                        'tipo_evidencia' => $data['tipo_evidencia'],
                    ]);
                    $data['version_entrega'] = count($existentes) + 1;

                    try {
                        $id = $this->model->create($data);
                        Audit::log('SUBIDA_EVIDENCIA', 'evidencias', (string)$id, null, [
                            'tipo_evidencia' => $data['tipo_evidencia'],
                            'hash_sha256'    => $data['hash_sha256'],
                        ]);
                        Session::setFlash('success', 'Evidencia subida correctamente.');
                        $this->redirectTo('evidencias');
                    } catch (Throwable $e) {
                        $errores[] = 'Error al registrar: ' . $e->getMessage();
                    }
                }
            } elseif (empty($errores)) {
                // Sin archivo pero datos básicos OK: crear placeholder PENDIENTE
                $data['estado']          = 'PENDIENTE';
                $data['version_entrega'] = 1;
                try {
                    $id = $this->model->create($data);
                    Session::setFlash('success', 'Evidencia registrada como pendiente.');
                    $this->redirectTo('evidencias');
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
            $evidencia = $data;
        }

        $this->render('evidencias/subir', [
            'title'         => 'Subir Evidencia',
            'evidencia'     => $evidencia,
            'procesos'      => $procesos,
            'tiposEvidencia'=> $tiposEvidencia,
            'errores'       => $errores,
        ]);
    }

    public function revisar(): void
    {
        $this->requireRole([ROL_COORDINACION, ROL_PRACTICAS, ROL_REGISTRO, ROL_ADMIN]);
        $id        = (int)$this->getQuery('id', 0);
        $evidencia = $this->model->findById($id);
        if (!$evidencia) { Session::setFlash('error', 'Evidencia no encontrada.'); $this->redirectTo('evidencias'); }

        if ($this->isPost()) {
            $this->validateCsrf();
            $accion       = $this->getPost('accion', '');
            $observaciones= trim($this->getPost('observaciones', ''));

            if ($accion === 'aprobar') {
                $this->model->aprobar($id, (int)Auth::id(), $observaciones);
                Audit::log('APROBACION_EVIDENCIA', 'evidencias', (string)$id,
                    ['estado' => $evidencia['estado']], ['estado' => 'APROBADA']);
                Session::setFlash('success', 'Evidencia aprobada.');
            } elseif ($accion === 'rechazar') {
                if (empty($observaciones)) {
                    Session::setFlash('error', 'Las observaciones son obligatorias al rechazar.');
                    $this->redirectTo('evidencias', 'revisar', ['id' => $id]);
                }
                $this->model->rechazar($id, (int)Auth::id(), $observaciones);
                Audit::log('RECHAZO_EVIDENCIA', 'evidencias', (string)$id,
                    ['estado' => $evidencia['estado']], ['estado' => 'RECHAZADA'], $observaciones);
                Session::setFlash('success', 'Evidencia rechazada.');
            }
            $this->redirectTo('evidencias');
        }

        $this->render('evidencias/subir', [
            'title'         => 'Revisar Evidencia',
            'evidencia'     => $evidencia,
            'procesos'      => [],
            'tiposEvidencia'=> [],
            'errores'       => [],
            'modo_revision' => true,
        ]);
    }

    public function descargar(): void
    {
        $this->requireAuth();
        $id = (int)$this->getQuery('id', 0);
        $evidencia = $this->model->findById($id);
        if (!$evidencia || empty($evidencia['ruta_almacenamiento'])) {
            Session::setFlash('error', 'Archivo no disponible.'); $this->redirectTo('evidencias');
        }

        // IDOR: ESTUDIANTE solo puede descargar sus propias evidencias
        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if (!$matricula) { http_response_code(403); exit; }
            $proceso = $this->procesoModel->findById((int)$evidencia['id_proceso']);
            if (!$proceso || (int)$proceso['id_matricula'] !== (int)$matricula['id_matricula']) {
                http_response_code(403); $title = 'Acceso denegado';
                include BASE_PATH . '/views/errors/403.php'; exit;
            }
        }

        FileUpload::download($evidencia['ruta_almacenamiento'], $evidencia['nombre_archivo'] ?? 'evidencia');
    }

    private function validar(array $data): array
    {
        $errores = [];
        if ($data['id_proceso'] <= 0)    { $errores[] = 'Debe seleccionar un proceso de grado.'; }
        if (empty($data['tipo_evidencia'])) { $errores[] = 'El tipo de evidencia es requerido.'; }
        return $errores;
    }
}
