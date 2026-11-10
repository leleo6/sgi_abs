<?php
include BASE_PATH . '/views/layouts/header.php';

$rol = $currentUser['rol'] ?? 'ESTUDIANTE';
$nombreCompleto = trim(($currentUser['nombres'] ?? '') . ' ' . ($currentUser['apellidos'] ?? ''));

// Defaults if not provided by controller
$stats = $stats ?? [
    'usuarios' => 0,
    'programas' => 0,
    'matriculas' => 0,
    'procesos_activos' => 0
];
$estadoAcademico = is_array($estadoAcademico ?? null) 
    ? ($estadoAcademico['estado_academico'] ?? 'LIMPIO') 
    : ($estadoAcademico ?? ($currentUser['estado_academico'] ?? 'LIMPIO'));
$materiasPendientes = $materiasPendientes ?? [];
$procesoActivo = $procesoActivo ?? null;
$ultimosProcesos = $ultimosProcesos ?? [];
$evidenciasPendientes = $evidenciasPendientes ?? [];
$actividadReciente = $actividadReciente ?? [];
$progresoMalla = isset($progresoMalla) ? (int)$progresoMalla : 0;
?>

<div class="row align-items-center mb-4">
  <div class="col-12 col-md-8">
    <h2 class="fw-bold tracking-tight mb-1 text-dark">
      ¡Hola, <?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?>!
    </h2>
    <p class="text-muted mb-0 small">
      Bienvenido al Sistema de Gestión Académica SGI-ABS. Rol activo: 
      <strong class="text-primary"><?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
  </div>
  <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
    <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm small text-muted">
      <i class="bi bi-calendar3 me-2 text-primary"></i>
      <span><?= date('d M, Y') ?> &bull; Periodo <?= htmlspecialchars($periodoActivo ?? '2026-1', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
</div>

<?php if ($rol === 'ADMIN'): ?>
  <!-- ADMIN KPI CARDS -->
  <div class="row g-3 mb-4">
    <!-- Card Usuarios -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-blue h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Total Usuarios</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['usuarios'] ?? 0) ?></div>
            <div class="smaller text-success mt-1"><i class="bi bi-person-check me-1"></i>Activos en plataforma</div>
          </div>
          <div class="stat-icon">
            <i class="bi bi-people-fill"></i>
          </div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('usuarios') ?>" class="smaller text-decoration-none text-primary fw-medium d-flex justify-content-between align-items-center">
            <span>Gestionar usuarios</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Card Programas -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-green h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Programas & Mallas</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['programas'] ?? 0) ?></div>
            <div class="smaller text-muted mt-1"><i class="bi bi-journal-check me-1"></i>Versiones vigentes</div>
          </div>
          <div class="stat-icon">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('programas') ?>" class="smaller text-decoration-none text-success fw-medium d-flex justify-content-between align-items-center">
            <span>Ver programas</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Card Matrículas -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-purple h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Matrículas Totales</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['matriculas'] ?? 0) ?></div>
            <div class="smaller text-purple mt-1"><i class="bi bi-card-checklist me-1"></i>Estudiantes registrados</div>
          </div>
          <div class="stat-icon">
            <i class="bi bi-person-lines-fill"></i>
          </div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('matriculas') ?>" class="smaller text-decoration-none text-purple fw-medium d-flex justify-content-between align-items-center">
            <span>Ver matrículas</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Card Procesos Activos -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-amber h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Procesos de Grado</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['procesos_activos'] ?? 0) ?></div>
            <div class="smaller text-warning mt-1"><i class="bi bi-play-circle me-1"></i>En ejecución / Avalados</div>
          </div>
          <div class="stat-icon">
            <i class="bi bi-award-fill"></i>
          </div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('procesos') ?>" class="smaller text-decoration-none text-warning fw-medium d-flex justify-content-between align-items-center">
            <span>Control de procesos</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (in_array($rol, ['COORDINACION', 'RESP_PRACTICAS'])): ?>
  <!-- COORDINACION / PRACTICAS KPI CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-amber h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Procesos en Ejecución</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['procesos_activos'] ?? 0) ?></div>
            <div class="smaller text-warning mt-1"><i class="bi bi-play-circle me-1"></i>En seguimiento</div>
          </div>
          <div class="stat-icon"><i class="bi bi-award-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('procesos') ?>" class="smaller text-decoration-none text-warning fw-medium d-flex justify-content-between align-items-center">
            <span>Ver procesos</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-blue h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Estudiantes Elegibles</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['procesos_elegibles'] ?? 0) ?></div>
            <div class="smaller text-primary mt-1"><i class="bi bi-check2-circle me-1"></i>Malla completada</div>
          </div>
          <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('procesos', 'index', ['estado' => 'ELEGIBLE']) ?>" class="smaller text-decoration-none text-primary fw-medium d-flex justify-content-between align-items-center">
            <span>Ver elegibles</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-green h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Evidencias Pendientes</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['evidencias_pendientes'] ?? 0) ?></div>
            <div class="smaller text-success mt-1"><i class="bi bi-hourglass-split me-1"></i>Por calificar</div>
          </div>
          <div class="stat-icon"><i class="bi bi-folder-check"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('evidencias') ?>" class="smaller text-decoration-none text-success fw-medium d-flex justify-content-between align-items-center">
            <span>Revisar bitácoras</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-purple h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Matrículas Activas</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['matriculas_activas'] ?? 0) ?></div>
            <div class="smaller text-purple mt-1"><i class="bi bi-card-checklist me-1"></i>Estudiantes en curso</div>
          </div>
          <div class="stat-icon"><i class="bi bi-person-lines-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('matriculas') ?>" class="smaller text-decoration-none text-purple fw-medium d-flex justify-content-between align-items-center">
            <span>Ver matrículas</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($rol === 'REGISTRO_ACADEMICO'): ?>
  <!-- REGISTRO ACADEMICO KPI CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-blue h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Estudiantes</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['estudiantes'] ?? 0) ?></div>
            <div class="smaller text-primary mt-1"><i class="bi bi-people-fill me-1"></i>Registrados</div>
          </div>
          <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('usuarios', 'index', ['rol' => 'ESTUDIANTE']) ?>" class="smaller text-decoration-none text-primary fw-medium d-flex justify-content-between align-items-center">
            <span>Ver estudiantes</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-purple h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Matrículas Totales</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['matriculas'] ?? 0) ?></div>
            <div class="smaller text-purple mt-1"><i class="bi bi-card-checklist me-1"></i>Activas e inactivas</div>
          </div>
          <div class="stat-icon"><i class="bi bi-person-lines-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('matriculas') ?>" class="smaller text-decoration-none text-purple fw-medium d-flex justify-content-between align-items-center">
            <span>Gestionar matrículas</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-green h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Programas Técnicos</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['programas'] ?? 0) ?></div>
            <div class="smaller text-success mt-1"><i class="bi bi-mortarboard me-1"></i>Oferta académica</div>
          </div>
          <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('programas') ?>" class="smaller text-decoration-none text-success fw-medium d-flex justify-content-between align-items-center">
            <span>Ver programas</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card stat-card-amber h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Notas y Resultados</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['calificaciones'] ?? 0) ?></div>
            <div class="smaller text-warning mt-1"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Registros históricos</div>
          </div>
          <div class="stat-icon"><i class="bi bi-journal-check"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('resultados') ?>" class="smaller text-decoration-none text-warning fw-medium d-flex justify-content-between align-items-center">
            <span>Ver resultados</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($rol === 'DOCENTE'): ?>
  <!-- DOCENTE KPI CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6">
      <div class="card stat-card stat-card-blue h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Calificaciones Registradas</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['calificaciones_total'] ?? 0) ?></div>
            <div class="smaller text-primary mt-1"><i class="bi bi-check-all me-1"></i>Evaluaciones en sistema</div>
          </div>
          <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('resultados') ?>" class="smaller text-decoration-none text-primary fw-medium d-flex justify-content-between align-items-center">
            <span>Gestionar notas</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6">
      <div class="card stat-card stat-card-green h-100 card-hover">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted smaller fw-semibold text-uppercase">Materias en Sistema</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['materias_total'] ?? 0) ?></div>
            <div class="smaller text-success mt-1"><i class="bi bi-book me-1"></i>Malla curricular activa</div>
          </div>
          <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
        </div>
        <div class="card-footer bg-light border-0 py-2 px-3">
          <a href="<?= url('materias') ?>" class="smaller text-decoration-none text-success fw-medium d-flex justify-content-between align-items-center">
            <span>Ver materias</span><i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($rol === 'ESTUDIANTE'): ?>
  <!-- ESTUDIANTE SECTION -->
  <div class="row g-4 mb-4">
    <!-- Academic Status Card -->
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <div class="fw-semibold text-dark"><i class="bi bi-speedometer2 text-primary me-2"></i> Estado Académico Actual</div>
          <?= render_badge($estadoAcademico) ?>
        </div>
        <div class="card-body">
          <?php if ($estadoAcademico === 'LIMPIO'): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-3">
              <i class="bi bi-check-circle-fill fs-3 text-success me-3"></i>
              <div>
                <div class="fw-bold">¡Estás al día con tu malla curricular!</div>
                <div class="smaller text-muted">Cumples con el 100% de las materias obligatorias aprobadas y eres elegible para tu proceso de grado.</div>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
              <div>
                <div class="fw-bold">Atención: Cuentas con Deuda Académica</div>
                <div class="smaller text-muted">Tienes materias pendientes o habilitaciones por completar antes de poder optar al proceso de práctica.</div>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="small fw-semibold text-secondary">Avance de Malla Curricular</span>
              <span class="small fw-bold text-dark"><?= (int)$progresoMalla ?>%</span>
            </div>
            <div class="progress">
              <div class="progress-bar <?= $estadoAcademico === 'LIMPIO' ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= (int)$progresoMalla ?>%" aria-valuenow="<?= (int)$progresoMalla ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 pt-2">
            <a href="<?= url('resultados') ?>" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-journal-text me-1"></i> Ver Mis Calificaciones
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Process Card -->
    <div class="col-12 col-lg-6">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <div class="fw-semibold text-dark"><i class="bi bi-award text-purple me-2"></i> Mi Proceso de Práctica / Grado</div>
          <?php if ($procesoActivo): ?>
            <?= render_badge($procesoActivo['estado'] ?? 'EN_EJECUCION') ?>
          <?php else: ?>
            <span class="badge bg-secondary">SIN PROCESO</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if ($procesoActivo): ?>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <span class="smaller text-muted d-block">Modalidad</span>
                <strong class="small text-dark"><?= htmlspecialchars($procesoActivo['modalidad'] ?? 'PRACTICA', ENT_QUOTES, 'UTF-8') ?></strong>
              </div>
              <div class="col-6">
                <span class="smaller text-muted d-block">Entidad Receptora</span>
                <strong class="small text-dark"><?= htmlspecialchars($procesoActivo['entidad_razon_social'] ?? ($procesoActivo['titulo_proyecto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></strong>
              </div>
              <div class="col-6 mt-2">
                <span class="smaller text-muted d-block">Horas Registradas</span>
                <strong class="small text-primary"><?= number_format($procesoActivo['horas_cumplidas'] ?? 0, 1) ?> hrs</strong>
              </div>
              <div class="col-6 mt-2">
                <span class="smaller text-muted d-block">Paz y Salvo</span>
                <strong class="small"><?= render_badge($procesoActivo['paz_salvo_estado'] ?? 'PENDIENTE') ?></strong>
              </div>
            </div>
            <a href="<?= url('procesos', 'detalle', ['id' => $procesoActivo['id_proceso']]) ?>" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-eye me-1"></i> Ver Detalle de Mi Proceso
            </a>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
              <p class="small mb-2">No tienes un proceso de grado activo en este momento.</p>
              <?php if ($estadoAcademico === 'LIMPIO'): ?>
                <a href="<?= url('procesos', 'nuevo') ?>" class="btn btn-success btn-sm">
                  <i class="bi bi-plus-circle me-1"></i> Postular a Proceso de Grado
                </a>
              <?php else: ?>
                <span class="badge bg-danger-subtle text-danger p-2">Requiere tener estado LIMPIO para postular</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Materias Pendientes Table (if any) -->
    <?php if (!empty($materiasPendientes)): ?>
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3">
            <h6 class="mb-0 text-danger fw-bold">
              <i class="bi bi-exclamation-octagon me-2"></i> Materias Pendientes por Aprobar (<?= count($materiasPendientes) ?>)
            </h6>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Código</th>
                  <th>Materia</th>
                  <th>Periodo Bloque</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($materiasPendientes as $mp): ?>
                  <tr>
                    <td><code><?= htmlspecialchars($mp['codigo_materia'] ?? ($mp['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td class="fw-medium"><?= htmlspecialchars($mp['nombre_materia'] ?? ($mp['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>Bloque <?= htmlspecialchars($mp['periodo_bloque'] ?? '1', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($mp['tipo'] ?? 'OBLIGATORIA', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge bg-danger">PENDIENTE</span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (in_array($rol, ['COORDINACION', 'ADMIN', 'RESP_PRACTICAS'])): ?>
  <!-- COORDINATION SECTION -->
  <div class="row g-4 mb-4">
    <!-- Últimos Procesos -->
    <div class="col-12 col-xl-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i> Últimos Procesos Registrados</h6>
          <a href="<?= url('procesos') ?>" class="smaller text-primary text-decoration-none">Ver todos &rarr;</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Estudiante</th>
                <th>Modalidad</th>
                <th>Entidad / Proyecto</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($ultimosProcesos)): ?>
                <?php foreach (array_slice($ultimosProcesos, 0, 5) as $proc): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars(($proc['estudiante_nombres'] ?? '') . ' ' . ($proc['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="smaller text-muted"><?= htmlspecialchars($proc['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($proc['modalidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small text-truncate" style="max-width: 160px;"><?= htmlspecialchars($proc['entidad_razon_social'] ?? ($proc['titulo_proyecto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= render_badge($proc['estado'] ?? '') ?></td>
                    <td>
                      <a href="<?= url('procesos', 'detalle', ['id' => $proc['id_proceso']]) ?>" class="btn btn-outline-primary btn-sm py-1 px-2">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No hay procesos recientes registrados.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Evidencias Pendientes -->
    <div class="col-12 col-xl-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
          <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-folder-check text-warning me-2"></i> Evidencias por Revisar</h6>
          <a href="<?= url('evidencias') ?>" class="smaller text-warning text-decoration-none">Gestionar &rarr;</a>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php if (!empty($evidenciasPendientes)): ?>
              <?php foreach (array_slice($evidenciasPendientes, 0, 4) as $ev): ?>
                <li class="list-group-item px-3 py-2 d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-medium small text-dark"><?= htmlspecialchars($ev['tipo_evidencia'] ?? 'Bitácora', ENT_QUOTES, 'UTF-8') ?> (v<?= $ev['version_entrega'] ?? 1 ?>)</div>
                    <div class="smaller text-muted"><?= htmlspecialchars($ev['estudiante_nombre'] ?? (trim(($ev['estudiante_nombres'] ?? '') . ' ' . ($ev['estudiante_apellidos'] ?? '')) ?: 'Estudiante'), ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($ev['fecha_entrega'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                  <div>
                    <?= render_badge($ev['estado'] ?? 'ENTREGADA') ?>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="list-group-item text-center text-muted py-4 small">
                <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                No hay evidencias pendientes de revisión.
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- AUDITORIA / ACTIVIDAD RECIENTE (Para todos) -->
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-shield-check text-primary me-2"></i> Registro de Actividad Reciente del Sistema
        </h6>
        <?php if ($rol === 'ADMIN'): ?>
          <a href="<?= url('auditoria') ?>" class="smaller text-primary text-decoration-none">Ver auditoría completa &rarr;</a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Fecha y Hora</th>
              <th>Usuario</th>
              <th>Acción</th>
              <th>Entidad</th>
              <th>Registro ID</th>
              <th>IP Origen</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($actividadReciente)): ?>
              <?php foreach (array_slice($actividadReciente, 0, 5) as $act): ?>
                <tr>
                  <td class="small text-muted"><?= htmlspecialchars($act['fecha_evento'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="fw-medium small"><?= htmlspecialchars($act['usuario_nombre'] ?? (trim(($act['usuario_nombres'] ?? '') . ' ' . ($act['usuario_apellidos'] ?? '')) ?: ($act['usuario_correo'] ?? ($act['correo'] ?? 'Sistema'))), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($act['accion'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><code><?= htmlspecialchars($act['entidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                  <td>#<?= htmlspecialchars($act['id_registro'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="smaller text-muted"><?= htmlspecialchars($act['ip_origen'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-3 small">
                  No se registran eventos de auditoría recientes.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
