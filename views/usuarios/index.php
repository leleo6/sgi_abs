<?php
include BASE_PATH . '/views/layouts/header.php';

$usuarios = $usuarios ?? [];
$filterRol = $_GET['filter_rol'] ?? '';
$filterEstado = $_GET['filter_estado'] ?? '';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-people text-primary me-2"></i> Gestión de Usuarios
    </h3>
    <p class="text-muted small mb-0">Control centralizado de cuentas de acceso, roles institucionales y perfiles.</p>
  </div>
  <div>
    <a href="<?= url('usuarios', 'nuevo') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nuevo Usuario</span>
    </a>
  </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
      <input type="hidden" name="module" value="usuarios">
      <input type="hidden" name="action" value="index">
      
      <div class="col-12 col-sm-5 col-md-4">
        <div class="input-group input-group-sm">
          <label class="input-group-text bg-light text-secondary" for="filter_rol"><i class="bi bi-person-badge me-1"></i> Rol</label>
          <select name="filter_rol" id="filter_rol" class="form-select" onchange="this.form.submit()">
            <option value="">Todos los roles</option>
            <option value="ADMIN" <?= $filterRol === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
            <option value="REGISTRO_ACADEMICO" <?= $filterRol === 'REGISTRO_ACADEMICO' ? 'selected' : '' ?>>REGISTRO ACADÉMICO</option>
            <option value="COORDINACION" <?= $filterRol === 'COORDINACION' ? 'selected' : '' ?>>COORDINACIÓN</option>
            <option value="DOCENTE" <?= $filterRol === 'DOCENTE' ? 'selected' : '' ?>>DOCENTE</option>
            <option value="RESP_PRACTICAS" <?= $filterRol === 'RESP_PRACTICAS' ? 'selected' : '' ?>>RESP. PRÁCTICAS</option>
            <option value="ESTUDIANTE" <?= $filterRol === 'ESTUDIANTE' ? 'selected' : '' ?>>ESTUDIANTE</option>
          </select>
        </div>
      </div>

      <div class="col-12 col-sm-5 col-md-4">
        <div class="input-group input-group-sm">
          <label class="input-group-text bg-light text-secondary" for="filter_estado"><i class="bi bi-toggle2-on me-1"></i> Estado</label>
          <select name="filter_estado" id="filter_estado" class="form-select" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="ACTIVO" <?= $filterEstado === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
            <option value="BLOQUEADO" <?= $filterEstado === 'BLOQUEADO' ? 'selected' : '' ?>>BLOQUEADO</option>
            <option value="INACTIVO" <?= $filterEstado === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO</option>
          </select>
        </div>
      </div>

      <div class="col-12 col-sm-2 col-md-4 text-sm-end">
        <?php if ($filterRol || $filterEstado): ?>
          <a href="<?= url('usuarios') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Limpiar Filtros
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Users Table Card -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>Nombre y Documento</th>
            <th>Correo Electrónico</th>
            <th>Rol Institucional</th>
            <th>Estado</th>
            <th>Último Acceso</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm me-2 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold border" style="width: 34px; height: 34px; font-size: 0.75rem;">
                      <?= strtoupper(substr($u['nombres'] ?? 'U', 0, 1) . substr($u['apellidos'] ?? '', 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark">
                        <?= htmlspecialchars(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <?php if (!empty($u['documento'])): ?>
                        <div class="smaller text-muted">
                          <?= htmlspecialchars($u['tipo_documento'] ?? 'CC', ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($u['documento'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="small text-muted font-monospace"><?= htmlspecialchars($u['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                    <?= htmlspecialchars($u['rol'] ?? 'ESTUDIANTE', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <?= render_badge($u['estado'] ?? 'ACTIVO') ?>
                </td>
                <td>
                  <span class="smaller text-muted">
                    <?= !empty($u['ultimo_acceso']) ? htmlspecialchars($u['ultimo_acceso'], ENT_QUOTES, 'UTF-8') : 'Nunca' ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('usuarios', 'editar', ['id' => $u['id_usuario']]) ?>" class="btn btn-outline-primary" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if (($u['id_usuario'] ?? 0) != ($currentUser['id_usuario'] ?? 0)): ?>
                      <a href="<?= url('usuarios', 'eliminar', ['id' => $u['id_usuario']]) ?>" 
                         class="btn btn-outline-danger" 
                         title="Eliminar usuario" 
                         data-confirm="¿Desea desactivar o eliminar la cuenta de <?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos'], ENT_QUOTES, 'UTF-8') ?>?">
                        <i class="bi bi-trash"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
