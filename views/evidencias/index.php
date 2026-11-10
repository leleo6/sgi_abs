<?php
include BASE_PATH . '/views/layouts/header.php';

$evidencias = $evidencias ?? [];
$filterEstado = $_GET['filter_estado'] ?? '';
$canReview = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION', 'RESP_PRACTICAS']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-folder2-open text-primary me-2"></i> Portafolio de Evidencias y Bitácoras
    </h3>
    <p class="text-muted small mb-0">Repositorio central de documentos de práctica, bitácoras periódicas y soportes institucionales.</p>
  </div>
  <div>
    <a href="<?= url('evidencias', 'subir') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-cloud-arrow-up-fill"></i>
      <span>Subir Evidencia</span>
    </a>
  </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
      <input type="hidden" name="module" value="evidencias">
      <input type="hidden" name="action" value="index">
      
      <div class="col-12 col-sm-6 col-md-4">
        <div class="input-group input-group-sm">
          <label class="input-group-text bg-light text-secondary" for="filter_estado">
            <i class="bi bi-funnel me-1"></i> Estado
          </label>
          <select name="filter_estado" id="filter_estado" class="form-select" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="PENDIENTE" <?= $filterEstado === 'PENDIENTE' ? 'selected' : '' ?>>PENDIENTE</option>
            <option value="ENTREGADA" <?= $filterEstado === 'ENTREGADA' ? 'selected' : '' ?>>ENTREGADA</option>
            <option value="EN_REVISION" <?= $filterEstado === 'EN_REVISION' ? 'selected' : '' ?>>EN REVISIÓN</option>
            <option value="APROBADA" <?= $filterEstado === 'APROBADA' ? 'selected' : '' ?>>APROBADA</option>
            <option value="RECHAZADA" <?= $filterEstado === 'RECHAZADA' ? 'selected' : '' ?>>RECHAZADA</option>
          </select>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-8 text-sm-end">
        <?php if ($filterEstado): ?>
          <a href="<?= url('evidencias') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Limpiar Filtro
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>Proceso / Estudiante</th>
            <th>Tipo de Evidencia</th>
            <th>Versión</th>
            <th>Archivo Digital</th>
            <th>Fecha Entrega</th>
            <th>Estado</th>
            <th>Revisor</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($evidencias)): ?>
            <?php foreach ($evidencias as $e): ?>
              <tr>
                <td>
                  <div class="fw-semibold text-dark">
                    <?= htmlspecialchars(($e['estudiante_nombres'] ?? '') . ' ' . ($e['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div class="smaller text-muted">
                    <a href="<?= url('procesos', 'detalle', ['id' => $e['id_proceso']]) ?>" class="text-decoration-none">
                      Expediente #<?= (int)$e['id_proceso'] ?> &bull; <?= htmlspecialchars($e['modalidad'] ?? 'PRACTICA', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  </div>
                </td>
                <td>
                  <span class="fw-medium text-dark"><?= htmlspecialchars($e['tipo_evidencia'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                  <?php if (!empty($e['observaciones'])): ?>
                    <div class="smaller text-muted text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($e['observaciones'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($e['observaciones'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary font-monospace">
                    v<?= (int)($e['version_entrega'] ?? 1) ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($e['nombre_archivo'])): ?>
                    <div class="d-flex align-items-center">
                      <i class="bi bi-file-earmark-pdf text-danger fs-5 me-2"></i>
                      <div class="text-truncate" style="max-width: 140px;">
                        <a href="<?= url('evidencias', 'descargar', ['id' => $e['id_evidencia']]) ?>" class="smaller text-decoration-none fw-medium text-truncate d-block" title="<?= htmlspecialchars($e['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>">
                          <?= htmlspecialchars($e['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <span class="smaller text-muted">
                          <?= !empty($e['tamano_bytes']) ? number_format($e['tamano_bytes'] / 1024, 1) . ' KB' : '' ?>
                        </span>
                      </div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted smaller">Pendiente subida</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="smaller text-muted">
                    <?= !empty($e['fecha_entrega']) ? htmlspecialchars($e['fecha_entrega'], ENT_QUOTES, 'UTF-8') : '—' ?>
                  </span>
                </td>
                <td>
                  <?= render_badge($e['estado'] ?? 'PENDIENTE') ?>
                </td>
                <td>
                  <span class="smaller text-muted">
                    <?= !empty($e['revisor_nombre']) ? htmlspecialchars($e['revisor_nombre'], ENT_QUOTES, 'UTF-8') : 'Sin revisar' ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <?php if (!empty($e['nombre_archivo'])): ?>
                      <a href="<?= url('evidencias', 'descargar', ['id' => $e['id_evidencia']]) ?>" class="btn btn-outline-secondary" title="Descargar">
                        <i class="bi bi-download"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canReview): ?>
                      <a href="<?= url('evidencias', 'revisar', ['id' => $e['id_evidencia'], 'nuevo_estado' => 'APROBADA']) ?>" class="btn btn-outline-success" title="Aprobar Evidencia">
                        <i class="bi bi-check-lg"></i>
                      </a>
                      <a href="<?= url('evidencias', 'revisar', ['id' => $e['id_evidencia'], 'nuevo_estado' => 'RECHAZADA']) ?>" class="btn btn-outline-danger" title="Rechazar">
                        <i class="bi bi-x-lg"></i>
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
