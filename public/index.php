<?php
declare(strict_types=1);

ob_start(); // Buffer de salida: garantiza que header() siempre funcione

// ── Carga de configuración ──────────────────────────────────────────────────
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// ── Autoload manual ─────────────────────────────────────────────────────────
foreach (glob(BASE_PATH . '/core/*.php')        as $f) { require_once $f; }
foreach (glob(BASE_PATH . '/models/*.php')      as $f) { require_once $f; }
foreach (glob(BASE_PATH . '/controllers/*.php') as $f) { require_once $f; }

// ── Sesión segura ───────────────────────────────────────────────────────────
Session::start();

// ── Router ──────────────────────────────────────────────────────────────────
$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'index';

// Sanitizar: solo letras, números y guion bajo
$module = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$module);
$action = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$action);

// Si no hay módulo o es la raíz, redirigir según estado de sesión
if ($module === '') {
    if (Auth::check()) {
        header('Location: index.php?module=dashboard&action=index');
    } else {
        header('Location: index.php?module=auth&action=login');
    }
    ob_end_flush();
    exit;
}

$routes = [
    'auth'      => 'AuthController',
    'dashboard' => 'DashboardController',
    'usuarios'  => 'UsuarioController',
    'programas' => 'ProgramaController',
    'materias'  => 'MateriaController',
    'matriculas'=> 'MatriculaController',
    'resultados'=> 'ResultadoController',
    'procesos'  => 'ProcesoGradoController',
    'evidencias'=> 'EvidenciaController',
    'reportes'  => 'ReporteController',
    'auditoria' => 'AuditoriaController',
];

if (!isset($routes[$module])) {
    ob_end_clean();
    http_response_code(404);
    $title = 'Página no encontrada';
    include BASE_PATH . '/views/errors/404.php';
    exit;
}

$controllerClass = $routes[$module];
if (!class_exists($controllerClass)) {
    ob_end_clean();
    http_response_code(500);
    echo '<h1>Error interno: controlador no disponible.</h1>';
    exit;
}

$controller = new $controllerClass();
if (!method_exists($controller, $action)) {
    ob_end_clean();
    http_response_code(404);
    $title = 'Acción no encontrada';
    include BASE_PATH . '/views/errors/404.php';
    exit;
}

try {
    $controller->$action();
    ob_end_flush();
} catch (Throwable $e) {
    error_log('[SGI-ABS] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    ob_end_clean();
    http_response_code(500);
    // No exponer stack trace al usuario
    include BASE_PATH . '/views/errors/404.php'; // reutilizar vista de error genérica
}
