<?php
declare(strict_types=1);

abstract class Controller
{
    // ── Renderizado de vistas ───────────────────────────────────────────────
    protected function render(string $view, array $data = []): void
    {
        $data['currentUser'] = Auth::user();
        $data['flash']       = Session::getFlash();
        $data['csrfToken']   = $this->csrfToken();
        extract($data, EXTR_SKIP);
        $viewPath = BASE_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }
        include $viewPath;
    }

    // ── Redirecciones ───────────────────────────────────────────────────────
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    protected function redirectTo(string $module, string $action = 'index', array $params = []): never
    {
        $q = http_build_query(array_merge(['module' => $module, 'action' => $action], $params));
        $this->redirect('index.php?' . $q);
    }

    // ── Respuesta JSON ──────────────────────────────────────────────────────
    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Autenticación y roles ───────────────────────────────────────────────
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Session::setFlash('error', 'Debe iniciar sesión para continuar.');
            $this->redirectTo('auth', 'login');
        }
    }

    protected function requireRole(string|array $roles): void
    {
        $this->requireAuth();
        if (!Auth::can($roles)) {
            http_response_code(403);
            $title = 'Acceso denegado';
            include BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }

    // ── Helpers de entrada ──────────────────────────────────────────────────
    protected function getPost(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function getQuery(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // ── CSRF ────────────────────────────────────────────────────────────────
    protected function csrfToken(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    protected function validateCsrf(): void
    {
        $token = $this->getPost('csrf_token', '');
        if (!hash_equals(Session::get('csrf_token', ''), $token)) {
            Session::setFlash('error', 'Token de seguridad inválido. Reintente la operación.');
            http_response_code(419);
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
        }
        // Regenerar token tras uso
        Session::set('csrf_token', bin2hex(random_bytes(32)));
    }

    // ── Sanitización ────────────────────────────────────────────────────────
    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
