<?php
declare(strict_types=1);

class ProgramaController extends Controller
{
    private ProgramaModel $model;

    public function __construct() { $this->model = new ProgramaModel(); }

    public function index(): void
    {
        $this->requireAuth();
        $programas = $this->model->findAll([], 'nombre ASC');
        $this->render('programas/index', [
            'title'     => 'Programas Académicos',
            'programas' => $programas,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole(ROL_ADMIN);
        $errores  = [];
        $programa = null;

        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = $this->recogerDatos();
            $errores = $this->validar($data);
            if (empty($errores)) {
                try {
                    $id = $this->model->create($data);
                    Audit::log('CREACION_PROGRAMA', 'programas', (string)$id, null, $data);
                    Session::setFlash('success', 'Programa creado exitosamente.');
                    $this->redirectTo('programas');
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $programa = $data;
        }

        $this->render('programas/form', [
            'title'    => 'Nuevo Programa',
            'programa' => $programa,
            'errores'  => $errores,
        ]);
    }

    public function editar(): void
    {
        $this->requireRole(ROL_ADMIN);
        $id       = (int)$this->getQuery('id', 0);
        $programa = $this->model->findById($id);
        if (!$programa) { Session::setFlash('error', 'Programa no encontrado.'); $this->redirectTo('programas'); }

        $errores = [];
        if ($this->isPost()) {
            $this->validateCsrf();
            $data    = $this->recogerDatos();
            $errores = $this->validar($data, $id);
            if (empty($errores)) {
                try {
                    $anterior = $programa;
                    $this->model->update($id, $data);
                    Audit::log('ACTUALIZACION_PROGRAMA', 'programas', (string)$id, $anterior, $data);
                    Session::setFlash('success', 'Programa actualizado.');
                    $this->redirectTo('programas');
                } catch (Throwable $e) {
                    $errores[] = 'Error al actualizar: ' . $e->getMessage();
                }
            }
            $programa = array_merge($programa, $data);
        }

        $this->render('programas/form', [
            'title'    => 'Editar Programa',
            'programa' => $programa,
            'errores'  => $errores,
        ]);
    }

    public function inactivar(): void
    {
        $this->requireRole(ROL_ADMIN);
        if (!$this->isPost()) { $this->redirectTo('programas'); }
        $this->validateCsrf();
        $id = (int)$this->getPost('id', 0);
        $p  = $this->model->findById($id);
        if (!$p) { Session::setFlash('error', 'Programa no encontrado.'); $this->redirectTo('programas'); }
        $this->model->softDelete($id);
        Audit::log('CIERRE_PROGRAMA', 'programas', (string)$id, $p, ['estado' => 'CERRADA']);
        Session::setFlash('success', 'Programa cerrado correctamente.');
        $this->redirectTo('programas');
    }

    private function recogerDatos(): array
    {
        return [
            'codigo'        => trim($this->getPost('codigo', '')),
            'version'       => trim($this->getPost('version', '')),
            'nombre'        => trim($this->getPost('nombre', '')),
            'regimen'       => $this->getPost('regimen', 'SENA'),
            'unidad_medida' => $this->getPost('unidad_medida', 'MATERIAS'),
            'total_periodos'=> (int)$this->getPost('total_periodos', 1),
            'nota_minima'   => (float)$this->getPost('nota_minima', 3.00),
            'vigente_desde' => $this->getPost('vigente_desde', ''),
            'vigente_hasta' => $this->getPost('vigente_hasta') ?: null,
            'estado'        => $this->getPost('estado', 'BORRADOR'),
        ];
    }

    private function validar(array $data, int $editId = 0): array
    {
        $errores = [];
        if (empty($data['codigo'])) { $errores[] = 'El código es requerido.'; }
        if (empty($data['version'])) { $errores[] = 'La versión es requerida.'; }
        if (empty($data['nombre'])) { $errores[] = 'El nombre es requerido.'; }
        if ($data['total_periodos'] < 1) { $errores[] = 'El total de periodos debe ser al menos 1.'; }
        if ($data['nota_minima'] < 0 || $data['nota_minima'] > 5) { $errores[] = 'La nota mínima debe estar entre 0 y 5.'; }
        if (empty($data['vigente_desde'])) { $errores[] = 'La fecha de inicio de vigencia es requerida.'; }
        return $errores;
    }
}
