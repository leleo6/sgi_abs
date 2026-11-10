<?php
declare(strict_types=1);

class AuditoriaController extends Controller
{
    private AuditoriaModel $model;

    public function __construct()
    {
        $this->model = new AuditoriaModel();
    }

    public function index(): void
    {
        $this->requireRole(ROL_ADMIN);

        $pagina  = max(1, (int)$this->getQuery('pagina', 1));
        $limite  = 50;
        $offset  = ($pagina - 1) * $limite;

        $filtros = [
            'id_usuario' => $this->getQuery('id_usuario', ''),
            'entidad'    => $this->getQuery('entidad', ''),
            'accion'     => $this->getQuery('accion', ''),
            'desde'      => $this->getQuery('desde', ''),
            'hasta'      => $this->getQuery('hasta', ''),
        ];

        $registros = $this->model->findPaginated($filtros, $limite, $offset);
        $total     = $this->model->countAll($filtros);
        $totalPag  = (int)ceil($total / $limite);

        // Listas para los dropdowns de filtros
        $db = Database::getInstance();
        $entidades = $db->query('SELECT DISTINCT entidad FROM auditoria ORDER BY entidad')->fetchAll(PDO::FETCH_COLUMN);
        $acciones  = $db->query('SELECT DISTINCT accion  FROM auditoria ORDER BY accion')->fetchAll(PDO::FETCH_COLUMN);
        $usuarios  = (new UsuarioModel())->findAll([], 'apellidos ASC');

        $this->render('auditoria/index', [
            'title'     => 'Auditoría del Sistema',
            'registros' => $registros,
            'total'     => $total,
            'pagina'    => $pagina,
            'totalPag'  => $totalPag,
            'filtros'   => $filtros,
            'entidades' => $entidades,
            'acciones'  => $acciones,
            'usuarios'  => $usuarios,
        ]);
    }
}
