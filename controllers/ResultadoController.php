<?php
declare(strict_types=1);

class ResultadoController extends Controller
{
    private ResultadoModel  $model;
    private MatriculaModel  $matriculaModel;
    private MateriaModel    $materiaModel;

    public function __construct()
    {
        $this->model          = new ResultadoModel();
        $this->matriculaModel = new MatriculaModel();
        $this->materiaModel   = new MateriaModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filtros = [];

        if (Auth::role() === ROL_ESTUDIANTE) {
            $matricula = $this->matriculaModel->findByUsuario((int)Auth::id());
            if ($matricula) {
                $resultados = $this->model->findByMatricula((int)$matricula['id_matricula']);
            } else {
                $resultados = [];
            }
            $matriculas = [];
        } else {
            $idMatricula = (int)$this->getQuery('id_matricula', 0);
            $resultados  = $idMatricula
                ? $this->model->findByMatricula($idMatricula)
                : $this->model->findAll([], 'registrado_en DESC');
            $matriculas = $this->matriculaModel->findAllWithDetails();
            $filtros = ['id_matricula' => $idMatricula];
        }

        $this->render('resultados/index', [
            'title'      => 'Calificaciones',
            'resultados' => $resultados,
            'matriculas' => $matriculas,
            'filtros'    => $filtros,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole([ROL_DOCENTE, ROL_REGISTRO, ROL_COORDINACION, ROL_ADMIN]);
        $errores    = [];
        $resultado  = null;
        $matriculas = $this->matriculaModel->findAllWithDetails();
        $materias   = [];

        if ($this->isPost()) {
            $this->validateCsrf();
            $data = $this->recogerDatos();
            $errores = $this->validar($data);

            if (empty($errores)) {
                try {
                    // Calcular estado automáticamente
                    $programa = null;
                    $mat = $this->matriculaModel->findById((int)$data['id_matricula']);
                    if ($mat) {
                        $prog = (new ProgramaModel())->findById((int)$mat['id_programa']);
                        $notaMinima = $prog ? (float)$prog['nota_minima'] : NOTA_MINIMA_APROBACION;
                    } else {
                        $notaMinima = NOTA_MINIMA_APROBACION;
                    }

                    $data['estado_resultado'] = $this->model->calcularEstado(
                        (float)$data['calificacion'],
                        $data['tipo_evaluacion'],
                        $notaMinima
                    );
                    $data['registrado_por'] = Auth::id();
                    $data['numero_intento'] = $this->model->getProximoIntento(
                        (int)$data['id_matricula'],
                        (int)$data['id_materia']
                    );

                    $id = $this->model->create($data);
                    Audit::log('REGISTRO_NOTA', 'resultados', (string)$id, null, [
                        'calificacion'    => $data['calificacion'],
                        'estado_resultado'=> $data['estado_resultado'],
                        'tipo_evaluacion' => $data['tipo_evaluacion'],
                    ]);
                    Session::setFlash('success', 'Calificación registrada. Estado: ' . $data['estado_resultado']);
                    $this->redirectTo('resultados', 'index', ['id_matricula' => $data['id_matricula']]);
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $resultado = $data;
            // Cargar materias para el programa de la matrícula seleccionada
            if (!empty($data['id_matricula'])) {
                $mat = $this->matriculaModel->findById((int)$data['id_matricula']);
                if ($mat) { $materias = $this->materiaModel->findByPrograma((int)$mat['id_programa']); }
            }
        }

        $this->render('resultados/form', [
            'title'      => 'Registrar Calificación',
            'resultado'  => $resultado,
            'matriculas' => $matriculas,
            'materias'   => $materias,
            'errores'    => $errores,
        ]);
    }

    public function anular(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION]);
        if (!$this->isPost()) { $this->redirectTo('resultados'); }
        $this->validateCsrf();

        $id     = (int)$this->getPost('id', 0);
        $motivo = trim($this->getPost('motivo', ''));

        if (empty($motivo)) {
            Session::setFlash('error', 'El motivo de anulación es obligatorio.');
            $this->redirectTo('resultados');
        }

        $resultado = $this->model->findById($id);
        if (!$resultado) { Session::setFlash('error', 'Calificación no encontrada.'); $this->redirectTo('resultados'); }

        $ok = $this->model->anularResultado($id, $motivo, (int)Auth::id());
        if ($ok) {
            Audit::log('ANULACION_NOTA', 'resultados', (string)$id, $resultado, ['vigente' => null, 'motivo_anulacion' => $motivo], $motivo);
            Session::setFlash('success', 'Calificación anulada correctamente.');
        } else {
            Session::setFlash('error', 'No se pudo anular la calificación.');
        }
        $this->redirectTo('resultados');
    }

    private function recogerDatos(): array
    {
        return [
            'id_matricula'      => (int)$this->getPost('id_matricula', 0),
            'id_materia'        => (int)$this->getPost('id_materia', 0),
            'periodo'           => trim($this->getPost('periodo', '')),
            'tipo_evaluacion'   => $this->getPost('tipo_evaluacion', 'ORDINARIA'),
            'calificacion'      => $this->getPost('calificacion', ''),
            'soporte_documental'=> trim($this->getPost('soporte_documental', '')) ?: null,
        ];
    }

    private function validar(array $data): array
    {
        $errores = [];
        if ($data['id_matricula'] <= 0) { $errores[] = 'Debe seleccionar una matrícula.'; }
        if ($data['id_materia'] <= 0)   { $errores[] = 'Debe seleccionar una materia.'; }
        if (empty($data['periodo']))     { $errores[] = 'El período es requerido (ej: 2026-1).'; }
        if (!is_numeric($data['calificacion']) || $data['calificacion'] < 0 || $data['calificacion'] > 5) {
            $errores[] = 'La calificación debe ser un número entre 0.00 y 5.00.';
        }
        if ($data['tipo_evaluacion'] === 'HOMOLOGACION' && empty($data['soporte_documental'])) {
            $errores[] = 'El soporte documental es obligatorio para homologaciones.';
        }
        return $errores;
    }
}
