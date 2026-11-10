<?php
include BASE_PATH . '/views/layouts/header.php';

$resultados = $resultados ?? [];
$isEstudiante = (($currentUser['rol'] ?? '') === 'ESTUDIANTE');
$canGrade = in_array($currentUser['rol'] ?? '', ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION', 'DOCENTE']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-clipboard-data text-primary me-2"></i> <?= $isEstudiante ? 'Mis Calificaciones y Notas' : 'Registro de Notas y Evaluaciones' ?>
    </h3>
    <p class="text-muted small mb-0">Control de notas ordinarias, habilitaciones programadas y resoluciones de homologación.</p>
  </div>
  <?php if ($canGrade): ?>
  <div>
    <a href="<?= url('resultados', 'nuevo') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-plus-lg"></i>
      <span>Registrar Calificación</span>
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
            <?php if (!$isEstudiante): ?><th>Estudiante</th><?php endif; ?>
            <th>Materia Curricular</th>
            <th>Periodo</th>
            <th>Intento</th>
            <th>Tipo</th>
            <th>Calificación</th>
            <th>Estado Dictamen</th>
            <th>Soporte / Acta</th>
            <th>Registrado Por</th>
            <?php if ($canGrade): ?><th class="text-end">Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($resultados)): ?>
            <?php foreach ($resultados as $r): ?>
              <tr>
                <?php if (!$isEstudiante): ?>
                <td>
                  <div class="fw-semibold text-dark">
                    <?= htmlspecialchars(($r['estudiante_nombres'] ?? '') . ' ' . ($r['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div class="smaller text-muted">
                    <?= htmlspecialchars($r['tipo_documento'] ?? 'CC', ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($r['documento'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <?php endif; ?>

                <td>
                  <div class="fw-medium text-dark"><?= htmlspecialchars($r['materia_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  <span class="badge bg-light text-secondary border font-monospace smaller">
                    <?= htmlspecialchars($r['materia_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                  <span class="smaller text-muted ms-1">(Bloque <?= (int)($r['periodo_bloque'] ?? 1) ?>)</span>
                </td>

                <td>
                  <span class="badge bg-light text-dark font-monospace border">
                    <?= htmlspecialchars($r['periodo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>

                <td>
                  <span class="badge bg-secondary-subtle text-secondary">
                    #<?= (int)($r['numero_intento'] ?? 1) ?>
                  </span>
                </td>

                <td>
                  <span class="badge bg-light text-secondary border font-monospace">
                    <?= htmlspecialchars($r['tipo_evaluacion'] ?? 'ORDINARIA', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>

                <td>
                  <?php if ($r['calificacion'] !== null): ?>
                    <span class="fs-6 fw-bold <?= ((float)$r['calificacion'] >= 3.0) ? 'text-success' : 'text-danger' ?>">
                      <?= number_format((float)$r['calificacion'], 2) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted smaller">En Curso</span>
                  <?php endif; ?>
                </td>

                <td>
                  <?= render_badge($r['estado_resultado'] ?? 'EN_CURSO') ?>
                </td>

                <td>
                  <?php if (!empty($r['soporte_documental'])): ?>
                    <span class="smaller text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($r['soporte_documental'], ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-file-earmark-check text-primary me-1"></i><?= htmlspecialchars($r['soporte_documental'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted smaller">—</span>
                  <?php endif; ?>
                </td>

                <td class="smaller text-muted">
                  <?= htmlspecialchars($r['registrado_por_nombre'] ?? 'Docente', ENT_QUOTES, 'UTF-8') ?>
                </td>

                <?php if ($canGrade): ?>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('resultados', 'editar', ['id' => $r['id_resultado']]) ?>" class="btn btn-outline-primary" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if (($currentUser['rol'] ?? '') === 'ADMIN'): ?>
                    <a href="<?= url('resultados', 'anular', ['id' => $r['id_resultado']]) ?>" 
                       class="btn btn-outline-danger" 
                       title="Anular Registro"
                       data-confirm="¿Desea anular esta nota de <?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?>? Esta acción quedará auditada.">
                      <i class="bi bi-x-circle"></i>
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
