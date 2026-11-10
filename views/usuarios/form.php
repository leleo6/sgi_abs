<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($usuario['id_usuario']);
$formTitle = $isEdit ? 'Editar Usuario #' . $usuario['id_usuario'] : 'Nuevo Usuario';
$formAction = $isEdit ? url('usuarios', 'actualizar', ['id' => $usuario['id_usuario']]) : url('usuarios', 'guardar');
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">
    
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-person-gear text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Complete la información requerida según el rol de la persona.</p>
      </div>
      <a href="<?= url('usuarios') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al Listado
      </a>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="usuarioForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id_usuario'] ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
          
          <!-- SECCIÓN 1: DATOS BÁSICOS -->
          <div class="form-section-title">
            <i class="bi bi-card-heading text-primary"></i> 1. Datos Básicos y Rol
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
              <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
              <input 
                type="email" 
                class="form-control" 
                id="correo" 
                name="correo" 
                value="<?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
              <div class="form-text">Debe ser único en el sistema (ej. usuario@abs.edu.co).</div>
            </div>

            <div class="col-12 col-md-3">
              <label for="rol_select" class="form-label">Rol Institucional <span class="text-danger">*</span></label>
              <select class="form-select" id="rol_select" name="rol" required>
                <?php
                $roles = ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION', 'DOCENTE', 'RESP_PRACTICAS', 'ESTUDIANTE'];
                $selectedRol = $usuario['rol'] ?? 'ESTUDIANTE';
                foreach ($roles as $r):
                ?>
                  <option value="<?= $r ?>" <?= $selectedRol === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-3">
              <label for="estado" class="form-label">Estado de la Cuenta <span class="text-danger">*</span></label>
              <select class="form-select" id="estado" name="estado" required>
                <?php
                $estados = ['ACTIVO', 'BLOQUEADO', 'INACTIVO'];
                $selectedEstado = $usuario['estado'] ?? 'ACTIVO';
                foreach ($estados as $est):
                ?>
                  <option value="<?= $est ?>" <?= $selectedEstado === $est ? 'selected' : '' ?>><?= $est ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- SECCIÓN 2: SEGURIDAD Y ACCESO -->
          <div class="form-section-title">
            <i class="bi bi-shield-lock text-primary"></i> 2. Clave de Acceso
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="password" class="form-label">
                <?= $isEdit ? 'Nueva Contraseña (opcional)' : 'Contraseña de Acceso <span class="text-danger">*</span>' ?>
              </label>
              <div class="input-group">
                <input 
                  type="password" 
                  class="form-control" 
                  id="password" 
                  name="password" 
                  <?= $isEdit ? '' : 'required' ?>
                  placeholder="<?= $isEdit ? 'Dejar en blanco para conservar actual' : 'Mínimo 8 caracteres' ?>"
                >
                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="form-text">Recomendado: mayúsculas, minúsculas, números y símbolos.</div>
            </div>

            <div class="col-12 col-md-6">
              <label for="password_confirm" class="form-label">
                Confirmar Contraseña <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?>
              </label>
              <div class="input-group">
                <input 
                  type="password" 
                  class="form-control" 
                  id="password_confirm" 
                  name="password_confirm" 
                  <?= $isEdit ? '' : 'required' ?>
                  placeholder="Reescriba la contraseña"
                >
                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password_confirm">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- SECCIÓN 3: DATOS DEL ESTUDIANTE (DINÁMICO SEGÚN ROL) -->
          <div id="estudiante_fields" style="display: <?= ($selectedRol === 'ESTUDIANTE') ? 'block' : 'none' ?>;">
            <div class="form-section-title">
              <i class="bi bi-mortarboard text-primary"></i> 3. Información Específica del Estudiante (Requerida por Ley)
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-3">
                <label for="tipo_documento" class="form-label">Tipo Documento <span class="text-danger">*</span></label>
                <select class="form-select" id="tipo_documento" name="tipo_documento" data-required-estudiante="true">
                  <option value="">Seleccione...</option>
                  <?php
                  $tiposDoc = ['CC', 'TI', 'CE', 'PASAPORTE', 'OTRO'];
                  $selDoc = $usuario['tipo_documento'] ?? 'CC';
                  foreach ($tiposDoc as $td):
                  ?>
                    <option value="<?= $td ?>" <?= $selDoc === $td ? 'selected' : '' ?>><?= $td ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-3">
                <label for="documento" class="form-label">Número Documento <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="documento" 
                  name="documento" 
                  value="<?= htmlspecialchars($usuario['documento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                  data-required-estudiante="true"
                >
              </div>

              <div class="col-12 col-md-3">
                <label for="fecha_nacimiento" class="form-label">Fecha Nacimiento <span class="text-danger">*</span></label>
                <input 
                  type="date" 
                  class="form-control" 
                  id="fecha_nacimiento" 
                  name="fecha_nacimiento" 
                  value="<?= htmlspecialchars($usuario['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                  data-required-estudiante="true"
                >
              </div>

              <div class="col-12 col-md-3">
                <label for="telefono" class="form-label">Teléfono Móvil</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="telefono" 
                  name="telefono" 
                  value="<?= htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
              </div>

              <div class="col-12">
                <div class="form-check form-switch mt-2">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    role="switch" 
                    id="autorizacion_datos" 
                    name="autorizacion_datos" 
                    value="1" 
                    <?= !empty($usuario['autorizacion_datos']) ? 'checked' : '' ?>
                  >
                  <label class="form-check-label small fw-medium" for="autorizacion_datos">
                    Autorización expresa para el tratamiento de datos personales (Habeas Data)
                  </label>
                  <div class="form-text text-muted smaller">
                    Registra la fecha y hora de consentimiento en cumplimiento con la Ley 1581 de 2012.
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('usuarios') ?>" class="btn btn-outline-secondary">
            Cancelar
          </a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Guardar Cambios' : 'Crear Usuario' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
