<?php
declare(strict_types=1);

class MatriculaController extends Controller
{
    private MatriculaModel  $model;
    private ProgramaModel   $programaModel;
    private UsuarioModel    $usuarioModel;
    private ResultadoModel  $resultadoModel;
    private ProcesoGradoModel $procesoModel;

    public function __construct()
    {
        $this->model          = new MatriculaModel();
        $this->programaModel  = new ProgramaModel();
        $this->usuarioModel   = new UsuarioModel();
        $this->resultadoModel = new ResultadoModel();
        $this->procesoModel   = new ProcesoGradoModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->model->findByUsuario((int)Auth::id());
            if ($matricula) {
                $this->redirectTo('matriculas', 'detalle', ['id' => $matricula['id_matricula']]);
            }
            Session::setFlash('info', 'No tiene una matrícula activa actualmente.');
            $this->redirectTo('dashboard');
        }
        $matriculas = $this->model->findAllWithDetails();
        $this->render('matriculas/index', [
            'title'      => 'Matrículas',
            'matriculas' => $matriculas,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_REGISTRO, ROL_COORDINACION]);
        $errores    = [];
        $matricula  = null;
        $estudiantes= $this->usuarioModel->getEstudiantes();
        $programas  = $this->programaModel->findAllPublicados();

        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = $this->recogerDatos();
            $errores = $this->validar($data);
            if (empty($errores)) {
                try {
                    $id = $this->model->create($data);
                    Audit::log('CREACION_MATRICULA', 'matriculas', (string)$id, null, $data);
                    Session::setFlash('success', 'Matrícula registrada exitosamente.');
                    $this->redirectTo('matriculas', 'detalle', ['id' => $id]);
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $matricula = $data;
        }

        $this->render('matriculas/form', [
            'title'       => 'Nueva Matrícula',
            'matricula'   => $matricula,
            'estudiantes' => $estudiantes,
            'programas'   => $programas,
            'errores'     => $errores,
        ]);
    }

    public function editar(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_REGISTRO, ROL_COORDINACION]);
        $id        = (int)$this->getQuery('id', 0);
        $matricula = $this->model->findWithDetails($id);
        if (!$matricula) { Session::setFlash('error', 'Matrícula no encontrada.'); $this->redirectTo('matriculas'); }

        $errores    = [];
        $programas  = $this->programaModel->findAllPublicados();
        $estudiantes= $this->usuarioModel->getEstudiantes();

        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = ['estado' => $this->getPost('estado', 'ACTIVA')];
            try {
                $anterior = $matricula;
                $this->model->update($id, $data);
                Audit::log('CAMBIO_ESTADO_MATRICULA', 'matriculas', (string)$id, $anterior, $data);
                Session::setFlash('success', 'Estado de matrícula actualizado.');
                $this->redirectTo('matriculas', 'detalle', ['id' => $id]);
            } catch (Throwable $e) {
                $errores[] = 'Error: ' . $e->getMessage();
            }
            $matricula = array_merge($matricula, $data);
        }

        $this->render('matriculas/form', [
            'title'       => 'Editar Matrícula',
            'matricula'   => $matricula,
            'estudiantes' => $estudiantes,
            'programas'   => $programas,
            'errores'     => $errores,
        ]);
    }

    public function detalle(): void
    {
        $this->requireAuth();
        $id = (int)$this->getQuery('id', 0);

        // ESTUDIANTE solo puede ver su propia matrícula
        if (Auth::role() === ROL_ESTUDIANTE) {
            $propia = $this->model->findByUsuario((int)Auth::id());
            if (!$propia || (int)$propia['id_matricula'] !== $id) {
                http_response_code(403);
                $title = 'Acceso denegado';
                include BASE_PATH . '/views/errors/403.php';
                exit;
            }
        }

        $matricula = $this->model->findWithDetails($id);
        if (!$matricula) { Session::setFlash('error', 'Matrícula no encontrada.'); $this->redirectTo('matriculas'); }

        $estadoAcademico    = $this->model->getEstadoAcademico($id);
        $materiasPendientes = $this->model->getMateriasPendientes($id);
        $resultados         = $this->resultadoModel->findByMatricula($id);
        $proceso            = $this->procesoModel->getProcesoActivo($id);

        $this->render('matriculas/detalle', [
            'title'              => 'Detalle de Matrícula',
            'matricula'          => $matricula,
            'estadoAcademico'    => $estadoAcademico,
            'materiasPendientes' => $materiasPendientes,
            'resultados'         => $resultados,
            'proceso'            => $proceso,
        ]);
    }

    private function recogerDatos(): array
    {
        return [
            'id_usuario'      => (int)$this->getPost('id_usuario', 0),
            'id_programa'     => (int)$this->getPost('id_programa', 0),
            'periodo_ingreso' => trim($this->getPost('periodo_ingreso', '')),
            'fecha_ingreso'   => $this->getPost('fecha_ingreso', ''),
            'estado'          => $this->getPost('estado', 'ACTIVA'),
        ];
    }

    private function validar(array $data): array
    {
        $errores = [];
        if ($data['id_usuario'] <= 0) { $errores[] = 'Debe seleccionar un estudiante.'; }
        if ($data['id_programa'] <= 0){ $errores[] = 'Debe seleccionar un programa.'; }
        if (empty($data['periodo_ingreso'])) { $errores[] = 'El período de ingreso es requerido.'; }
        if (empty($data['fecha_ingreso']))   { $errores[] = 'La fecha de ingreso es requerida.'; }
        // Verificar que el estudiante no tenga ya una matrícula activa en ese programa
        if ($data['id_usuario'] > 0 && $data['id_programa'] > 0) {
            $existente = $this->model->findOne([
                'id_usuario'  => $data['id_usuario'],
                'id_programa' => $data['id_programa'],
            ]);
            if ($existente) { $errores[] = 'El estudiante ya tiene matrícula en ese programa.'; }
        }
        return $errores;
    }
}
