<?php
include BASE_PATH . '/views/layouts/header.php';

$usuario = $currentUser ?? [];
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-8">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-person-circle text-primary me-2"></i> Mi Perfil de Usuario
        </h3>
        <p class="text-muted small mb-0">Gestione sus datos de contacto y credenciales de acceso al sistema SGI-ABS.</p>
      </div>
      <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
      </a>
    </div>

    <form method="POST" action="<?= url('usuarios', 'actualizar_perfil') ?>" id="perfilForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <!-- User Card Header -->
          <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
            <div class="user-avatar fs-3 me-3 flex-shrink-0" style="width: 58px; height: 58px;">
              <?= strtoupper(substr($usuario['nombres'] ?? 'U', 0, 1) . substr($usuario['apellidos'] ?? '', 0, 1)) ?>
            </div>
            <div>
              <h5 class="fw-bold text-dark mb-1">
                <?= htmlspecialchars(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </h5>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary"><?= htmlspecialchars($usuario['rol'] ?? 'ESTUDIANTE', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-muted smaller">&bull; <?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-person text-primary"></i> 1. Datos Personales
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                id="nombres" 
                name="nombres" 
                value="<?= htmlspecialchars($usuario['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-6">
              <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                id="apellidos" 
                name="apellidos" 
                value="<?= htmlspecialchars($usuario['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-6">
              <label for="correo" class="form-label">Correo Institucional</label>
              <input 
                type="email" 
                class="form-control bg-light" 
                id="correo" 
                name="correo" 
                value="<?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                readonly
              >
              <div class="form-text">Para cambios en su correo institucional, acérquese a Registro Académico.</div>
            </div>

            <div class="col-12 col-md-6">
              <label for="telefono" class="form-label">Teléfono Móvil de Contacto</label>
              <input 
                type="text" 
                class="form-control" 
                id="telefono" 
                name="telefono" 
                value="<?= htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-key text-primary"></i> 2. Actualizar Contraseña de Acceso
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12 col-md-6">
              <label for="password_actual" class="form-label">Contraseña Actual</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password_actual" name="password_actual" placeholder="Requerida para confirmar cambios">
                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password_actual">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label for="nuevo_password" class="form-label">Nueva Contraseña</label>
              <div class="input-group">
                <input type="password" class="form-control" id="nuevo_password" name="nuevo_password" placeholder="Mínimo 8 caracteres">
                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="nuevo_password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span>Actualizar Mi Perfil</span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
