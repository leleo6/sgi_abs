<?php
declare(strict_types=1);

class UsuarioController extends Controller
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function index(): void
    {
        $this->requireRole(ROL_ADMIN);
        $filtroRol    = $this->getQuery('rol', '');
        $filtroEstado = $this->getQuery('estado', '');
        $where = [];
        if ($filtroRol)    { $where['rol']    = $filtroRol; }
        if ($filtroEstado) { $where['estado'] = $filtroEstado; }
        $usuarios = $this->model->findAll($where, 'apellidos ASC, nombres ASC');
        $this->render('usuarios/index', [
            'title'            => 'Gestión de Usuarios',
            'usuarios'         => $usuarios,
            'rolesDisponibles' => $this->getRoles(),
            'filtroRol'        => $filtroRol,
            'filtroEstado'     => $filtroEstado,
        ]);
    }

    public function crear(): void
    {
        $this->requireRole(ROL_ADMIN);
        $errores = [];
        $usuario = null;

        if ($this->isPost()) {
            $this->validateCsrf();
            $data = $this->recogerDatos();
            $errores = $this->validar($data);

            if (empty($errores)) {
                $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                unset($data['password'], $data['password_confirm']);
                try {
                    $id = $this->model->create($data);
                    Audit::log('CREACION_USUARIO', 'usuarios', (string)$id, null, $data);
                    Session::setFlash('success', 'Usuario creado exitosamente.');
                    $this->redirectTo('usuarios');
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
            $usuario = $data;
        }

        $this->render('usuarios/form', [
            'title'            => 'Nuevo Usuario',
            'usuario'          => $usuario,
            'rolesDisponibles' => $this->getRoles(),
            'errores'          => $errores,
        ]);
    }

    public function editar(): void
    {
        $this->requireRole(ROL_ADMIN);
        $id = (int)$this->getQuery('id', 0);
        $usuario = $this->model->findById($id);
        if (!$usuario) {
            Session::setFlash('error', 'Usuario no encontrado.');
            $this->redirectTo('usuarios');
        }

        $errores = [];
        if ($this->isPost()) {
            $this->validateCsrf();
            $data = $this->recogerDatos();
            $errores = $this->validar($data, $id);

            if (empty($errores)) {
                $anterior = $usuario;
                // Solo cambiar password si se proporcionó
                if (!empty($data['password'])) {
                    $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                } else {
                    unset($data['password_hash']);
                }
                unset($data['password'], $data['password_confirm']);

                try {
                    $this->model->update($id, $data);
                    Audit::log('ACTUALIZACION_USUARIO', 'usuarios', (string)$id, $anterior, $data);
                    Session::setFlash('success', 'Usuario actualizado correctamente.');
                    $this->redirectTo('usuarios');
                } catch (Throwable $e) {
                    $errores[] = 'Error al actualizar: ' . $e->getMessage();
                }
            }
            $usuario = array_merge($usuario, $data);
        }

        $this->render('usuarios/form', [
            'title'            => 'Editar Usuario',
            'usuario'          => $usuario,
            'rolesDisponibles' => $this->getRoles(),
            'errores'          => $errores,
        ]);
    }

    public function eliminar(): void
    {
        $this->requireRole(ROL_ADMIN);
        if (!$this->isPost()) { $this->redirectTo('usuarios'); }
        $this->validateCsrf();
        $id = (int)$this->getPost('id', 0);
        $usuario = $this->model->findById($id);
        if (!$usuario) {
            Session::setFlash('error', 'Usuario no encontrado.');
            $this->redirectTo('usuarios');
        }
        // No permitir eliminar el propio usuario
        if ($id === Auth::id()) {
            Session::setFlash('error', 'No puede inactivar su propio usuario.');
            $this->redirectTo('usuarios');
        }
        $this->model->softDelete($id);
        Audit::log('BAJA_USUARIO', 'usuarios', (string)$id, $usuario, ['estado' => 'INACTIVO']);
        Session::setFlash('success', 'Usuario inactivado correctamente.');
        $this->redirectTo('usuarios');
    }

    // ── Helpers privados ────────────────────────────────────────────────────
    private function recogerDatos(): array
    {
        return [
            'rol'               => $this->getPost('rol', 'ESTUDIANTE'),
            'correo'            => trim($this->getPost('correo', '')),
            'nombres'           => trim($this->getPost('nombres', '')),
            'apellidos'         => trim($this->getPost('apellidos', '')),
            'tipo_documento'    => $this->getPost('tipo_documento') ?: null,
            'documento'         => trim($this->getPost('documento', '')) ?: null,
            'fecha_nacimiento'  => $this->getPost('fecha_nacimiento') ?: null,
            'telefono'          => trim($this->getPost('telefono', '')) ?: null,
            'autorizacion_datos'=> $this->getPost('autorizacion_datos') ? 1 : 0,
            'estado'            => $this->getPost('estado', 'ACTIVO'),
            'password'          => $this->getPost('password', ''),
            'password_confirm'  => $this->getPost('password_confirm', ''),
        ];
    }

    private function validar(array $data, int $editId = 0): array
    {
        $errores = [];
        if (empty($data['nombres']))   { $errores[] = 'El nombre es requerido.'; }
        if (empty($data['apellidos'])) { $errores[] = 'Los apellidos son requeridos.'; }
        if (empty($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo electrónico inválido.';
        } else {
            $existente = $this->model->findByEmail($data['correo']);
            if ($existente && (int)$existente['id_usuario'] !== $editId) {
                $errores[] = 'El correo ya está registrado en el sistema.';
            }
        }
        if ($editId === 0 && strlen($data['password']) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $errores[] = 'Las contraseñas no coinciden.';
        }
        if ($data['rol'] === ROL_ESTUDIANTE) {
            if (empty($data['tipo_documento'])) { $errores[] = 'El tipo de documento es requerido para estudiantes.'; }
            if (empty($data['documento']))      { $errores[] = 'El número de documento es requerido.'; }
            if (empty($data['fecha_nacimiento'])){ $errores[] = 'La fecha de nacimiento es requerida.'; }
        }
        return $errores;
    }

    private function getRoles(): array
    {
        return ['ADMIN','REGISTRO_ACADEMICO','COORDINACION','DOCENTE','RESP_PRACTICAS','ESTUDIANTE'];
    }
}
