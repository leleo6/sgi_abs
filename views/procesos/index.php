<?php
include BASE_PATH . '/views/layouts/header.php';

$procesos = $procesos ?? [];
$isEstudiante = (($currentUser['rol'] ?? '') === 'ESTUDIANTE');
$canManage = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION', 'RESP_PRACTICAS']);
$estadoEstudiante = $estadoAcademico ?? ($currentUser['estado_academico'] ?? 'LIMPIO');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-award text-primary me-2"></i> Procesos de Etapa Práctica y Grado
    </h3>
    <p class="text-muted small mb-0">Seguimiento de prácticas laborales, pasantías, convenios empresariales y proyectos de grado.</p>
  </div>
  <div>
    <?php if ($canManage || ($isEstudiante && $estadoEstudiante === 'LIMPIO')): ?>
      <a href="<?= url('procesos', 'nuevo') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-plus-lg"></i>
        <span>Nuevo Proceso de Grado</span>
      </a>
    <?php elseif ($isEstudiante): ?>
      <button class="btn btn-secondary" disabled title="Debe encontrarse en estado LIMPIO sin materias pendientes para postular">
        <i class="bi bi-lock me-1"></i> Postulación Bloqueada (Con Deuda)
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Estudiante</th>
            <th>Modalidad</th>
            <th>Entidad / Proyecto</th>
            <th>Convenio / Supervisor</th>
            <th>Periodo Fechas</th>
            <th>Horas</th>
            <th>Estado</th>
            <th>Paz y Salvo</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($procesos)): ?>
            <?php foreach ($procesos as $p): ?>
              <tr>
                <td><span class="text-muted font-monospace smaller">#<?= (int)$p['id_proceso'] ?></span></td>
                <td>
                  <div class="fw-semibold text-dark">
                    <?= htmlspecialchars(($p['estudiante_nombres'] ?? '') . ' ' . ($p['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div class="smaller text-muted"><?= htmlspecialchars($p['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td>
                  <span class="badge bg-light text-primary border font-monospace">
                    <?= htmlspecialchars($p['modalidad'] ?? 'PRACTICA', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <div class="fw-medium text-dark small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($p['entidad_razon_social'] ?? ($p['titulo_proyecto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($p['entidad_razon_social'] ?? ($p['titulo_proyecto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <?php if (!empty($p['entidad_nit'])): ?>
                    <span class="smaller text-muted font-monospace">NIT: <?= htmlspecialchars($p['entidad_nit'], ENT_QUOTES, 'UTF-8') ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="smaller text-dark"><?= htmlspecialchars($p['supervisor_nombre'] ?? ($p['convenio_numero'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if (!empty($p['supervisor_correo'])): ?>
                    <div class="smaller text-muted"><?= htmlspecialchars($p['supervisor_correo'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="smaller text-muted">
                    <?= htmlspecialchars($p['fecha_inicio'] ?? '—', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($p['fecha_fin'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary fw-bold">
                    <?= number_format((float)($p['horas_cumplidas'] ?? 0), 1) ?>h
                  </span>
                </td>
                <td>
                  <?= render_badge($p['estado'] ?? 'ELEGIBLE') ?>
                </td>
                <td>
                  <?= render_badge($p['paz_salvo_estado'] ?? 'PENDIENTE') ?>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('procesos', 'detalle', ['id' => $p['id_proceso']]) ?>" class="btn btn-outline-info" title="Ver Expediente">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($canManage): ?>
                    <a href="<?= url('procesos', 'editar', ['id' => $p['id_proceso']]) ?>" class="btn btn-outline-primary" title="Editar">
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
