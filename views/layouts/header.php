<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

if (!function_exists('url')) {
    function url(string $module, string $action = 'index', array $params = []): string {
        $p = array_merge(['module' => $module, 'action' => $action], $params);
        return 'index.php?' . http_build_query($p);
    }
}

if (!function_exists('render_badge')) {
    function render_badge(?string $status): string {
        if ($status === null || $status === '') {
            return '<span class="badge bg-secondary">N/A</span>';
        }
        $s = strtoupper(trim($status));
        $class = match ($s) {
            'ACTIVO', 'APROBADO', 'APROBADA', 'LIMPIO', 'PUBLICADA', 'AVALADO', 'EMITIDO', 'ACTIVA' => 'badge bg-success',
            'EN_CURSO', 'HABILITACION_PROGRAMADA', 'BORRADOR', 'EN_EJECUCION', 'SUSPENDIDA', 'EN_REVISION' => 'badge bg-warning text-dark',
            'REPROBADO', 'REPROBADO_HABILITACION', 'BLOQUEADO', 'INTERRUMPIDO', 'RETIRADA', 'RECHAZADA', 'REVOCADO', 'CON_DEUDA_ACADEMICA' => 'badge bg-danger',
            'INACTIVO', 'CERRADA' => 'badge bg-secondary',
            'PENDIENTE', 'ENTREGADA' => 'badge bg-info text-dark',
            'ELEGIBLE' => 'badge bg-primary',
            'HOMOLOGADO', 'FINALIZADO', 'APROBADO_HABILITACION' => 'badge bg-purple',
            default => 'badge bg-secondary'
        };
        $label = str_replace('_', ' ', $s);
        return '<span class="' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

$currentModule = $_GET['module'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= htmlspecialchars($title ?? 'SGI-ABS', ENT_QUOTES, 'UTF-8') ?> — Sistema de Gestión Académica</title>
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- DataTables 1.13.7 -->
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <!-- App Custom CSS -->
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="<?= isset($currentUser) && $currentUser ? 'has-sidebar' : 'bg-auth' ?>">
<?php if (isset($currentUser) && $currentUser): ?>
<div class="d-flex" id="wrapper">
  <!-- Sidebar -->
  <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>
  
  <!-- Page Content Wrapper -->
  <div id="page-content-wrapper" class="flex-grow-1 d-flex flex-column min-vh-100">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom px-3 px-md-4 py-2 sticky-top app-header">
      <div class="container-fluid px-0">
        <button class="btn btn-outline-secondary btn-sm me-3 d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
          <i class="bi bi-list fs-5"></i>
        </button>
        
        <div class="d-none d-sm-flex align-items-center me-auto">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
              <?php if ($currentModule !== 'dashboard'): ?>
                <li class="breadcrumb-item text-capitalize"><a href="<?= url($currentModule) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($currentModule, ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php if ($currentAction !== 'index'): ?>
                  <li class="breadcrumb-item active text-capitalize" aria-current="page"><?= htmlspecialchars($currentAction, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endif; ?>
              <?php endif; ?>
            </ol>
          </nav>
        </div>

        <div class="d-flex align-items-center ms-auto gap-3">
          <!-- Institutional pill -->
          <div class="d-none d-md-flex align-items-center text-muted small me-2">
            <span class="badge bg-light text-secondary border px-2 py-1 me-2">
              <i class="bi bi-mortarboard me-1 text-primary"></i> ABS Educación
            </span>
          </div>

          <!-- User dropdown -->
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle user-menu-btn" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="user-avatar me-2">
                <?= strtoupper(substr($currentUser['nombres'] ?? 'U', 0, 1) . substr($currentUser['apellidos'] ?? '', 0, 1)) ?>
              </div>
              <div class="d-none d-md-block text-start lh-sm me-1">
                <div class="fw-semibold text-dark small user-name">
                  <?= htmlspecialchars(($currentUser['nombres'] ?? '') . ' ' . ($currentUser['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="text-muted smaller">
                  <span class="badge bg-secondary-subtle text-secondary py-0 px-1 font-monospace" style="font-size:0.7rem;">
                    <?= htmlspecialchars($currentUser['rol'] ?? 'USUARIO', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </div>
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 user-dropdown-menu" aria-labelledby="userDropdown">
              <li class="px-3 py-2 border-bottom d-md-none">
                <div class="fw-semibold text-dark"><?= htmlspecialchars(($currentUser['nombres'] ?? '') . ' ' . ($currentUser['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted small"><?= htmlspecialchars($currentUser['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <span class="badge bg-primary mt-1"><?= htmlspecialchars($currentUser['rol'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
              </li>
              <li>
                <a class="dropdown-item py-2" href="<?= url('usuarios', 'perfil') ?>">
                  <i class="bi bi-person me-2 text-primary"></i> Mi Perfil
                </a>
              </li>
              <?php if (($currentUser['rol'] ?? '') === 'ADMIN'): ?>
              <li>
                <a class="dropdown-item py-2" href="<?= url('auditoria') ?>">
                  <i class="bi bi-shield-check me-2 text-warning"></i> Bitácora de Auditoría
                </a>
              </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <a class="dropdown-item py-2 text-danger" href="<?= url('auth', 'logout') ?>">
                  <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-grow-1 py-4">
      <div class="container-fluid px-3 px-md-4">
        <!-- Flash Alert Messages -->
        <?php if (isset($flash) && $flash): ?>
          <?php 
            $alertType = match($flash['type'] ?? 'info') {
                'success' => 'success',
                'error' => 'danger',
                'warning' => 'warning',
                'info' => 'info',
                default => 'secondary'
            };
            $alertIcon = match($flash['type'] ?? 'info') {
                'success' => 'check-circle-fill',
                'error' => 'exclamation-octagon-fill',
                'warning' => 'exclamation-triangle-fill',
                default => 'info-circle-fill'
            };
          ?>
          <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-<?= $alertIcon ?> fs-5 me-2 flex-shrink-0"></i>
            <div class="flex-grow-1">
              <?= htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
        <?php endif; ?>
<?php else: ?>
  <div class="container">
<?php endif; ?>
