<?php
declare(strict_types=1);

class MateriaController extends Controller
{
    private MateriaModel  $model;
    private ProgramaModel $programaModel;

    public function __construct()
    {
        $this->model         = new MateriaModel();
        $this->programaModel = new ProgramaModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $filtroPrograma = (int)$this->getQuery('id_programa', 0);
        $materias = $filtroPrograma
            ? $this->model->findByPrograma($filtroPrograma)
            : $this->model->findAll([], 'id_programa ASC, periodo_bloque ASC, nombre ASC');
        $programas = $this->programaModel->findAll([], 'nombre ASC');

        $this->render('materias/index', [
            'title'          => 'Materias',
            'materias'       => $materias,
            'programas'      => $programas,
            'filtroPrograma' => $filtroPrograma,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION]);
        $errores = [];
        $materia = null;
        $programas = $this->programaModel->findAll([], 'nombre ASC');

        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = $this->recogerDatos();
            $errores = $this->validar($data);
            if (empty($errores)) {
                try {
                    $id = $this->model->create($data);
                    Audit::log('CREACION_MATERIA', 'materias', (string)$id, null, $data);
                    Session::setFlash('success', 'Materia creada exitosamente.');
                    $this->redirectTo('materias', 'index', ['id_programa' => $data['id_programa']]);
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $materia = $data;
        }

        $idProg = (int)$this->getQuery('id_programa', $materia['id_programa'] ?? 0);
        $materiasRequisito = $idProg ? $this->model->findByPrograma($idProg) : [];

        $this->render('materias/form', [
            'title'            => 'Nueva Materia',
            'materia'          => $materia,
            'programas'        => $programas,
            'materiasRequisito'=> $materiasRequisito,
            'errores'          => $errores,
        ]);
    }

    public function editar(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_COORDINACION]);
        $id      = (int)$this->getQuery('id', 0);
        $materia = $this->model->findById($id);
        if (!$materia) { Session::setFlash('error', 'Materia no encontrada.'); $this->redirectTo('materias'); }

        $errores   = [];
        $programas = $this->programaModel->findAll([], 'nombre ASC');

        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = $this->recogerDatos();
            $errores = $this->validar($data, $id);
            if (empty($errores)) {
                try {
                    $anterior = $materia;
                    $this->model->update($id, $data);
                    Audit::log('ACTUALIZACION_MATERIA', 'materias', (string)$id, $anterior, $data);
                    Session::setFlash('success', 'Materia actualizada.');
                    $this->redirectTo('materias', 'index', ['id_programa' => $data['id_programa']]);
                } catch (Throwable $e) {
                    $errores[] = 'Error al actualizar: ' . $e->getMessage();
                }
            }
            $materia = array_merge($materia, $data);
        }

        $materiasRequisito = $this->model->findByPrograma((int)$materia['id_programa']);

        $this->render('materias/form', [
            'title'            => 'Editar Materia',
            'materia'          => $materia,
            'programas'        => $programas,
            'materiasRequisito'=> $materiasRequisito,
            'errores'          => $errores,
        ]);
    }

    private function recogerDatos(): array
    {
        return [
            'id_programa'           => (int)$this->getPost('id_programa', 0),
            'id_materia_requisito'  => $this->getPost('id_materia_requisito') ? (int)$this->getPost('id_materia_requisito') : null,
            'codigo'                => trim($this->getPost('codigo', '')),
            'nombre'                => trim($this->getPost('nombre', '')),
            'periodo_bloque'        => (int)$this->getPost('periodo_bloque', 1),
            'tipo'                  => $this->getPost('tipo', 'OBLIGATORIA'),
            'intensidad'            => $this->getPost('intensidad') ? (float)$this->getPost('intensidad') : null,
        ];
    }

    private function validar(array $data, int $editId = 0): array
    {
        $errores = [];
        if ($data['id_programa'] <= 0) { $errores[] = 'Debe seleccionar un programa.'; }
        if (empty($data['codigo']))    { $errores[] = 'El código de la materia es requerido.'; }
        if (empty($data['nombre']))    { $errores[] = 'El nombre es requerido.'; }
        if ($data['periodo_bloque'] < 1) { $errores[] = 'El período/bloque debe ser al menos 1.'; }
        // Validar que el prerrequisito no sea la misma materia
        if ($editId > 0 && $data['id_materia_requisito'] === $editId) {
            $errores[] = 'Una materia no puede ser prerrequisito de sí misma.';
        }
        return $errores;
    }
}
