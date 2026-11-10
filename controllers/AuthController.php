<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirectTo('dashboard');
        }
        $this->redirectTo('auth', 'login');
    }

    public function login(): void
    {
        // Ya autenticado → ir al panel
        if (Auth::check()) {
            $this->redirectTo('dashboard');
        }

        $errores = [];

        if ($this->isPost()) {
            // El campo del formulario HTML se llama 'correo'
            $email    = trim($this->getPost('correo', ''));
            $password = $this->getPost('password', '');

            if (empty($email))    { $errores[] = 'El correo electrónico es requerido.'; }
            if (empty($password)) { $errores[] = 'La contraseña es requerida.'; }

            if (empty($errores)) {
                if (Auth::attempt($email, $password)) {
                    $user = Auth::user();
                    Session::setFlash('success', '¡Bienvenido/a, ' . htmlspecialchars($user['nombres'], ENT_QUOTES, 'UTF-8') . '!');
                    $this->redirectTo('dashboard');
                } else {
                    // El mensaje de error ya fue seteado por Auth::attempt() vía setFlash
                    // Si no vino ninguno, agregar uno genérico
                    $errores[] = 'Credenciales incorrectas o cuenta bloqueada/inactiva.';
                }
            }
        }

        // Renderizar con render() para inyectar $csrfToken y $flash correctamente
        $this->render('auth/login', [
            'title'   => 'Iniciar Sesión — SGI-ABS',
            'errores' => $errores,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        Session::setFlash('success', 'Ha cerrado sesión correctamente.');
        $this->redirectTo('auth', 'login');
    }
}
