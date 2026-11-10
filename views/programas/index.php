<?php
include BASE_PATH . '/views/layouts/header.php';

$programas = $programas ?? [];
$canManage = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-mortarboard text-primary me-2"></i> Programas Académicos y Mallas
    </h3>
    <p class="text-muted small mb-0">Configuración de programas curriculares, regímenes, versiones y vigencias.</p>
  </div>
  <?php if ($canManage): ?>
  <div>
    <a href="<?= url('programas', 'nuevo') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-plus-lg"></i>
      <span>Nuevo Programa</span>
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>Código</th>
            <th>Versión Malla</th>
            <th>Nombre del Programa</th>
            <th>Régimen</th>
            <th>Periodos / Unidad</th>
            <th>Nota Mínima</th>
            <th>Vigencia</th>
            <th>Estado</th>
            <?php if ($canManage): ?><th class="text-end">Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($programas)): ?>
            <?php foreach ($programas as $p): ?>
              <tr>
                <td>
                  <span class="badge bg-light text-dark font-monospace border">
                    <?= htmlspecialchars($p['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary font-monospace">
                    v<?= htmlspecialchars($p['version'] ?? '1.0', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td class="fw-semibold text-dark">
                  <?= htmlspecialchars($p['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                  <span class="badge bg-info-subtle text-info-emphasis border">
                    <?= htmlspecialchars($p['regimen'] ?? 'SENA', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="small text-muted">
                    <?= (int)($p['total_periodos'] ?? 0) ?> periodos 
                    <span class="smaller text-secondary">(<?= htmlspecialchars($p['unidad_medida'] ?? 'MATERIAS', ENT_QUOTES, 'UTF-8') ?>)</span>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-primary border fw-bold">
                    <?= number_format((float)($p['nota_minima'] ?? 3.0), 2) ?>
                  </span>
                </td>
                <td>
                  <div class="smaller text-muted">
                    <span>Desde: <?= htmlspecialchars($p['vigente_desde'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($p['vigente_hasta'])): ?>
                      <br><span>Hasta: <?= htmlspecialchars($p['vigente_hasta'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <?= render_badge($p['estado'] ?? 'BORRADOR') ?>
                </td>
                <?php if ($canManage): ?>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('materias', 'index', ['id_programa' => $p['id_programa']]) ?>" class="btn btn-outline-info" title="Ver Materias">
                      <i class="bi bi-book"></i>
                    </a>
                    <a href="<?= url('programas', 'editar', ['id' => $p['id_programa']]) ?>" class="btn btn-outline-primary" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if (($currentUser['rol'] ?? '') === 'ADMIN'): ?>
                    <a href="<?= url('programas', 'eliminar', ['id' => $p['id_programa']]) ?>" 
                       class="btn btn-outline-danger" 
                       title="Eliminar" 
                       data-confirm="¿Desea eliminar o cerrar el programa <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>?">
                      <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
                  </div>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
