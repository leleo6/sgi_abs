<?php
include BASE_PATH . '/views/layouts/header.php';

$auditorias = $auditorias ?? [];
$usuarios = $usuarios ?? [];

$filterUsuario = $_GET['id_usuario'] ?? '';
$filterEntidad = $_GET['entidad'] ?? '';
$filterAccion = $_GET['accion'] ?? '';
$filterFechaInicio = $_GET['fecha_inicio'] ?? '';
$filterFechaFin = $_GET['fecha_fin'] ?? '';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-shield-check text-primary me-2"></i> Bitácora de Auditoría del Sistema
    </h3>
    <p class="text-muted small mb-0">Trazabilidad inmutable de eventos, modificaciones y transiciones de estado (Append-Only).</p>
  </div>
  <div>
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
      <i class="bi bi-lock-fill me-1"></i> Acceso Restringido: Administrador
    </span>
  </div>
</div>

<!-- Advanced Search Filters Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
      <input type="hidden" name="module" value="auditoria">
      <input type="hidden" name="action" value="index">

      <div class="col-12 col-sm-6 col-lg-3">
        <label for="filter_usuario" class="form-label smaller text-secondary mb-1">Usuario</label>
        <select name="id_usuario" id="filter_usuario" class="form-select form-select-sm">
          <option value="">Todos los usuarios</option>
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= $u['id_usuario'] ?>" <?= ($filterUsuario == $u['id_usuario']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos'] . ' (' . $u['rol'] . ')', ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-lg-2">
        <label for="filter_entidad" class="form-label smaller text-secondary mb-1">Entidad (Tabla)</label>
        <select name="entidad" id="filter_entidad" class="form-select form-select-sm">
          <option value="">Todas las tablas</option>
          <?php
          $entidades = ['usuarios', 'programas', 'materias', 'matriculas', 'resultados', 'procesos_grado', 'evidencias'];
          foreach ($entidades as $ent):
          ?>
            <option value="<?= $ent ?>" <?= ($filterEntidad === $ent) ? 'selected' : '' ?>><?= $ent ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-lg-2">
        <label for="filter_accion" class="form-label smaller text-secondary mb-1">Acción</label>
        <select name="accion" id="filter_accion" class="form-select form-select-sm">
          <option value="">Todas las acciones</option>
          <?php
          $acciones = ['INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'CAMBIO_ESTADO', 'EMISION_PAZ_SALVO', 'REVOCACION'];
          foreach ($acciones as $acc):
          ?>
            <option value="<?= $acc ?>" <?= ($filterAccion === $acc) ? 'selected' : '' ?>><?= $acc ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-lg-2">
        <label for="filter_fecha_ini" class="form-label smaller text-secondary mb-1">Fecha Desde</label>
        <input type="date" name="fecha_inicio" id="filter_fecha_ini" class="form-control form-control-sm" value="<?= htmlspecialchars($filterFechaInicio, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="col-12 col-sm-6 col-lg-2">
        <label for="filter_fecha_fin" class="form-label smaller text-secondary mb-1">Fecha Hasta</label>
        <input type="date" name="fecha_fin" id="filter_fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($filterFechaFin, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="col-12 col-sm-6 col-lg-1 d-flex gap-1">
        <button type="submit" class="btn btn-primary btn-sm w-100" title="Filtrar">
          <i class="bi bi-search"></i>
        </button>
        <?php if ($filterUsuario || $filterEntidad || $filterAccion || $filterFechaInicio || $filterFechaFin): ?>
          <a href="<?= url('auditoria') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
            <i class="bi bi-x-lg"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Table Card -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>Fecha y Hora</th>
            <th>Usuario Responsable</th>
            <th>Acción</th>
            <th>Entidad Afectada</th>
            <th>ID Registro</th>
            <th>Motivo</th>
            <th>IP Origen</th>
            <th class="text-end">Detalle JSON</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($auditorias)): ?>
            <?php foreach ($auditorias as $a): ?>
              <tr>
                <td>
                  <span class="smaller font-monospace text-dark">
                    <?= htmlspecialchars($a['fecha_evento'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <div class="fw-semibold text-dark small">
                    <?= htmlspecialchars($a['usuario_nombre'] ?? ($a['correo'] ?? 'Sistema Automatizado'), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <?php if (!empty($a['usuario_rol'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary py-0 px-1 font-monospace" style="font-size: 0.65rem;">
                      <?= htmlspecialchars($a['usuario_rol'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $badgeAccion = match($a['accion'] ?? '') {
                    'INSERT' => 'badge bg-success',
                    'UPDATE' => 'badge bg-primary',
                    'DELETE', 'REVOCACION' => 'badge bg-danger',
                    'EMISION_PAZ_SALVO' => 'badge bg-purple',
                    'CAMBIO_ESTADO' => 'badge bg-warning text-dark',
                    default => 'badge bg-secondary'
                  };
                  ?>
                  <span class="<?= $badgeAccion ?> font-monospace"><?= htmlspecialchars($a['accion'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <code class="small"><?= htmlspecialchars($a['entidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
                </td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace">#<?= htmlspecialchars($a['id_registro'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <span class="smaller text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($a['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($a['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="smaller font-monospace text-muted"><?= htmlspecialchars($a['ip_origen'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td class="text-end">
                  <button 
                    type="button" 
                    class="btn btn-outline-info btn-sm py-1 px-2 btn-ver-detalle"
                    data-bs-toggle="modal" 
                    data-bs-target="#detalleAuditoriaModal"
                    data-id="<?= (int)$a['id_auditoria'] ?>"
                    data-accion="<?= htmlspecialchars($a['accion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-entidad="<?= htmlspecialchars($a['entidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-registro="<?= htmlspecialchars($a['id_registro'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-anterior='<?= htmlspecialchars(json_encode(is_string($a['valor_anterior'] ?? null) ? json_decode($a['valor_anterior'], true) : ($a['valor_anterior'] ?? null)), ENT_QUOTES, 'UTF-8') ?>'
                    data-nuevo='<?= htmlspecialchars(json_encode(is_string($a['valor_nuevo'] ?? null) ? json_decode($a['valor_nuevo'], true) : ($a['valor_nuevo'] ?? null)), ENT_QUOTES, 'UTF-8') ?>'
                  >
                    <i class="bi bi-code-slash me-1"></i> Ver JSON
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Detalle Auditoria JSON -->
<div class="modal fade" id="detalleAuditoriaModal" tabindex="-1" aria-labelledby="detalleAuditoriaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fs-6 fw-bold" id="detalleAuditoriaModalLabel">
          <i class="bi bi-shield-shaded text-primary me-2"></i> Inspección de Payload JSON de Auditoría
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="mb-3 d-flex gap-2">
          <span class="badge bg-primary" id="modalAccionBadge">ACCION</span>
          <span class="badge bg-secondary font-monospace" id="modalEntidadBadge">entidad</span>
          <span class="badge bg-light text-dark border font-monospace" id="modalRegistroBadge">#0</span>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label smaller fw-semibold text-secondary">
              <i class="bi bi-clock-history me-1"></i> Estado Anterior (valor_anterior)
            </label>
            <pre class="json-viewer" id="jsonAnterior">{ ... }</pre>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label smaller fw-semibold text-secondary">
              <i class="bi bi-arrow-repeat me-1"></i> Estado Nuevo (valor_nuevo)
            </label>
            <pre class="json-viewer" id="jsonNuevo">{ ... }</pre>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-white border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('detalleAuditoriaModal');
    if (modalEl) {
      modalEl.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const accion = button.getAttribute('data-accion');
        const entidad = button.getAttribute('data-entidad');
        const registro = button.getAttribute('data-registro');
        const anteriorRaw = button.getAttribute('data-anterior');
        const nuevoRaw = button.getAttribute('data-nuevo');

        document.getElementById('modalAccionBadge').textContent = accion;
        document.getElementById('modalEntidadBadge').textContent = entidad;
        document.getElementById('modalRegistroBadge').textContent = '#' + registro;

        try {
          const objAnt = JSON.parse(anteriorRaw);
          document.getElementById('jsonAnterior').textContent = objAnt ? JSON.stringify(objAnt, null, 2) : '(Sin datos previos / Creación inicial)';
        } catch(e) {
          document.getElementById('jsonAnterior').textContent = anteriorRaw || '(Sin datos previos)';
        }

        try {
          const objNue = JSON.parse(nuevoRaw);
          document.getElementById('jsonNuevo').textContent = objNue ? JSON.stringify(objNue, null, 2) : '(Sin modificaciones)';
        } catch(e) {
          document.getElementById('jsonNuevo').textContent = nuevoRaw || '(Sin modificaciones)';
        }
      });
    }
  });
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
