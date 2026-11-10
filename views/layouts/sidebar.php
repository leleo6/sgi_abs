<?php
$role = $currentUser['rol'] ?? 'ESTUDIANTE';
$currentModule = $_GET['module'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';

function is_active(string $module, ?string $action = null): string {
    global $currentModule, $currentAction;
    if ($action !== null) {
        return ($currentModule === $module && $currentAction === $action) ? 'active' : '';
    }
    return ($currentModule === $module) ? 'active' : '';
}
?>
<aside id="sidebar-wrapper" class="sidebar d-flex flex-column flex-shrink-0">
  <!-- Brand Header -->
  <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-secondary border-opacity-25">
    <a href="<?= url('dashboard') ?>" class="d-flex align-items-center text-white text-decoration-none brand-link">
      <div class="brand-icon me-2 d-flex align-items-center justify-content-center">
        <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
      </div>
      <div>
        <span class="fs-5 fw-bold tracking-tight text-white">SGI-ABS</span>
        <div class="brand-subtitle text-muted smaller">Gestión Académica</div>
      </div>
    </a>
    <button class="btn btn-sm btn-link text-white-50 d-lg-none" id="sidebarCloseBtn" type="button" aria-label="Close Sidebar">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- User Quick Info Card -->
  <div class="sidebar-user px-3 py-3 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center">
      <div class="sidebar-avatar me-2 flex-shrink-0">
        <?= strtoupper(substr($currentUser['nombres'] ?? 'U', 0, 1) . substr($currentUser['apellidos'] ?? '', 0, 1)) ?>
      </div>
      <div class="overflow-hidden">
        <div class="text-white small fw-semibold text-truncate">
          <?= htmlspecialchars(($currentUser['nombres'] ?? '') . ' ' . ($currentUser['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="text-white-50 smaller d-flex align-items-center gap-1">
          <span class="user-role-badge"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Menu -->
  <div class="sidebar-nav flex-grow-1 overflow-y-auto py-2">
    <ul class="nav nav-pills flex-column px-2 gap-1 mb-auto">
      
      <!-- General Section -->
      <li class="nav-heading text-uppercase text-white-50 smaller px-3 pt-2 pb-1">General</li>
      
      <li class="nav-item">
        <a href="<?= url('dashboard') ?>" class="nav-link text-white-50 <?= is_active('dashboard') ?>">
          <i class="bi bi-speedometer2 me-2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <?php if ($role === 'ESTUDIANTE'): ?>
        <!-- Menú exclusivo para ESTUDIANTE -->
        <li class="nav-heading text-uppercase text-white-50 smaller px-3 pt-3 pb-1">Mi Portal</li>
        
        <li class="nav-item">
          <a href="<?= url('matriculas', 'detalle', ['id' => $currentUser['id_matricula'] ?? '']) ?>" class="nav-link text-white-50 <?= is_active('matriculas') ?>">
            <i class="bi bi-person-badge me-2"></i>
            <span>Mi Estado Académico</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('resultados') ?>" class="nav-link text-white-50 <?= is_active('resultados') ?>">
            <i class="bi bi-clipboard-data me-2"></i>
            <span>Mis Notas y Malla</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('procesos') ?>" class="nav-link text-white-50 <?= is_active('procesos') ?>">
            <i class="bi bi-award me-2"></i>
            <span>Mi Proceso de Grado</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('evidencias') ?>" class="nav-link text-white-50 <?= is_active('evidencias') ?>">
            <i class="bi bi-folder2-open me-2"></i>
            <span>Mis Evidencias</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('usuarios', 'perfil') ?>" class="nav-link text-white-50 <?= is_active('usuarios', 'perfil') ?>">
            <i class="bi bi-person-circle me-2"></i>
            <span>Mi Perfil</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if (in_array($role, ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION'])): ?>
        <!-- Gestión Académica -->
        <li class="nav-heading text-uppercase text-white-50 smaller px-3 pt-3 pb-1">Académico</li>
        
        <li class="nav-item">
          <a href="<?= url('programas') ?>" class="nav-link text-white-50 <?= is_active('programas') ?>">
            <i class="bi bi-mortarboard me-2"></i>
            <span>Programas y Mallas</span>
          </a>
        </li>

        <?php if ($role === 'ADMIN'): ?>
        <li class="nav-item">
          <a href="<?= url('materias') ?>" class="nav-link text-white-50 <?= is_active('materias') ?>">
            <i class="bi bi-book me-2"></i>
            <span>Materias / Módulos</span>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
          <a href="<?= url('matriculas') ?>" class="nav-link text-white-50 <?= is_active('matriculas') ?>">
            <i class="bi bi-person-lines-fill me-2"></i>
            <span>Matrículas</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if (in_array($role, ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION', 'DOCENTE'])): ?>
        <li class="nav-item">
          <a href="<?= url('resultados') ?>" class="nav-link text-white-50 <?= is_active('resultados') ?>">
            <i class="bi bi-clipboard-data me-2"></i>
            <span>Notas y Evaluaciones</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if (in_array($role, ['ADMIN', 'COORDINACION', 'RESP_PRACTICAS'])): ?>
        <!-- Prácticas y Grados -->
        <li class="nav-heading text-uppercase text-white-50 smaller px-3 pt-3 pb-1">Prácticas y Grado</li>
        
        <li class="nav-item">
          <a href="<?= url('procesos') ?>" class="nav-link text-white-50 <?= is_active('procesos') ?>">
            <i class="bi bi-award me-2"></i>
            <span>Procesos de Grado</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('evidencias') ?>" class="nav-link text-white-50 <?= is_active('evidencias') ?>">
            <i class="bi bi-folder2-open me-2"></i>
            <span>Evidencias y Bitácoras</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if (in_array($role, ['ADMIN', 'COORDINACION'])): ?>
        <!-- Reportes -->
        <li class="nav-item">
          <a href="<?= url('reportes') ?>" class="nav-link text-white-50 <?= is_active('reportes') ?>">
            <i class="bi bi-bar-chart me-2"></i>
            <span>Reportes e Informes</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if ($role === 'ADMIN'): ?>
        <!-- Administración del Sistema -->
        <li class="nav-heading text-uppercase text-white-50 smaller px-3 pt-3 pb-1">Administración</li>
        
        <li class="nav-item">
          <a href="<?= url('usuarios') ?>" class="nav-link text-white-50 <?= is_active('usuarios') ?>">
            <i class="bi bi-people me-2"></i>
            <span>Usuarios y Roles</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= url('auditoria') ?>" class="nav-link text-white-50 <?= is_active('auditoria') ?>">
            <i class="bi bi-shield-check me-2"></i>
            <span>Bitácora Auditoría</span>
          </a>
        </li>
      <?php endif; ?>

    </ul>
  </div>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer p-3 border-top border-secondary border-opacity-10 mt-auto">
    <a href="<?= url('auth', 'logout') ?>" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-box-arrow-left"></i>
      <span>Cerrar Sesión</span>
    </a>
  </div>
</aside>
