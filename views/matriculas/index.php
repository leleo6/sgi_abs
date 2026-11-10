<?php
include BASE_PATH . '/views/layouts/header.php';

$matriculas = $matriculas ?? [];
$canManage = in_array($currentUser['rol'] ?? '', ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-person-lines-fill text-primary me-2"></i> Registro de Matrículas
    </h3>
    <p class="text-muted small mb-0">Vinculación de estudiantes con programas curriculares y cohortes académicas.</p>
  </div>
  <?php if ($canManage): ?>
  <div>
    <a href="<?= url('matriculas', 'nuevo') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nueva Matrícula</span>
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
            <th>ID</th>
            <th>Estudiante</th>
            <th>Documento</th>
            <th>Programa Curricular</th>
            <th>Periodo Ingreso</th>
            <th>Fecha Ingreso</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($matriculas)): ?>
            <?php foreach ($matriculas as $m): ?>
              <tr>
                <td>
                  <span class="text-muted font-monospace smaller">#<?= (int)$m['id_matricula'] ?></span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar-sm me-2 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold border" style="width: 32px; height: 32px; font-size: 0.72rem;">
                      <?= strtoupper(substr($m['estudiante_nombres'] ?? 'E', 0, 1) . substr($m['estudiante_apellidos'] ?? '', 0, 1)) ?>
                    </div>
                    <div>
                      <a href="<?= url('matriculas', 'detalle', ['id' => $m['id_matricula']]) ?>" class="fw-semibold text-decoration-none text-dark">
                        <?= htmlspecialchars(($m['estudiante_nombres'] ?? '') . ' ' . ($m['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </a>
                      <div class="smaller text-muted"><?= htmlspecialchars($m['estudiante_correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="smaller font-monospace text-muted">
                    <?= htmlspecialchars($m['tipo_documento'] ?? 'CC', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($m['documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <div class="small fw-medium text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($m['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($m['programa_nombre'] ?? 'Programa', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <span class="badge bg-secondary-subtle text-secondary smaller font-monospace">
                    <?= htmlspecialchars($m['programa_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?> (v<?= htmlspecialchars($m['programa_version'] ?? '1.0', ENT_QUOTES, 'UTF-8') ?>)
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border font-monospace">
                    <?= htmlspecialchars($m['periodo_ingreso'] ?? '2026-1', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="smaller text-muted">
                    <?= htmlspecialchars($m['fecha_ingreso'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <?= render_badge($m['estado'] ?? 'ACTIVA') ?>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('matriculas', 'detalle', ['id' => $m['id_matricula']]) ?>" class="btn btn-outline-info" title="Ver Hoja de Vida Académica">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($canManage): ?>
                    <a href="<?= url('matriculas', 'editar', ['id' => $m['id_matricula']]) ?>" class="btn btn-outline-primary" title="Editar">
                      <i class="bi bi-pencil"></i>
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
