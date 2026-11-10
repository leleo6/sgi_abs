<?php
include BASE_PATH . '/views/layouts/header.php';

$matricula = $matricula ?? [];
$estadoAcademico = $estadoAcademico ?? 'LIMPIO';
$materiasPendientes = $materiasPendientes ?? [];
$historialResultados = $historialResultados ?? [];
$procesoActivo = $procesoActivo ?? null;

$esLimpio = ($estadoAcademico === 'LIMPIO');
$canGrade = in_array($currentUser['rol'] ?? '', ['ADMIN', 'REGISTRO_ACADEMICO', 'COORDINACION', 'DOCENTE']);
$canStartProcess = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION', 'RESP_PRACTICAS', 'ESTUDIANTE']);
?>

<!-- Header Section with Student and Academic Status -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      
      <div class="d-flex align-items-center">
        <div class="avatar-lg bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 border me-3 flex-shrink-0" style="width: 64px; height: 64px;">
          <?= strtoupper(substr($matricula['estudiante_nombres'] ?? 'E', 0, 1) . substr($matricula['estudiante_apellidos'] ?? '', 0, 1)) ?>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="fw-bold tracking-tight text-dark mb-0">
              <?= htmlspecialchars(($matricula['estudiante_nombres'] ?? '') . ' ' . ($matricula['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </h4>
            <?= render_badge($matricula['estado'] ?? 'ACTIVA') ?>
          </div>
          <p class="text-muted small mb-1">
            <strong><?= htmlspecialchars($matricula['tipo_documento'] ?? 'CC', ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($matricula['documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?> &bull; 
            <strong>Correo:</strong> <?= htmlspecialchars($matricula['estudiante_correo'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull;
            <strong>Periodo Ingreso:</strong> <?= htmlspecialchars($matricula['periodo_ingreso'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </p>
          <div class="small text-secondary">
            <i class="bi bi-mortarboard me-1 text-primary"></i> 
            <span class="fw-semibold text-dark"><?= htmlspecialchars($matricula['programa_nombre'] ?? 'Programa Curricular', ENT_QUOTES, 'UTF-8') ?></span> 
            (Código: <code><?= htmlspecialchars($matricula['programa_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></code> &bull; Versión: <?= htmlspecialchars($matricula['programa_version'] ?? '1.0', ENT_QUOTES, 'UTF-8') ?>)
          </div>
        </div>
      </div>

      <!-- Large Academic Status Indicator -->
      <div class="text-lg-end bg-light p-3 rounded-3 border">
        <div class="smaller text-muted fw-semibold text-uppercase mb-1">Dictamen Curricular</div>
        <?php if ($esLimpio): ?>
          <span class="badge bg-success fs-6 px-3 py-2">
            <i class="bi bi-check2-all me-1"></i> ESTUDIANTE LIMPIO
          </span>
          <div class="smaller text-success mt-1 fw-medium">100% Malla Obligatoria Superada</div>
        <?php else: ?>
          <span class="badge bg-danger fs-6 px-3 py-2">
            <i class="bi bi-exclamation-octagon-fill me-1"></i> CON DEUDA ACADÉMICA
          </span>
          <div class="smaller text-danger mt-1 fw-medium"><?= count($materiasPendientes) ?> materia(s) pendiente(s)</div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <div class="card-footer bg-light border-0 py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <a href="<?= url('matriculas') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Matrículas
      </a>
    </div>
    <div class="d-flex gap-2">
      <?php if ($canGrade): ?>
        <a href="<?= url('resultados', 'nuevo', ['id_matricula' => $matricula['id_matricula']]) ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle me-1"></i> Registrar Calificación
        </a>
      <?php endif; ?>
      <?php if ($canStartProcess && !$procesoActivo && $esLimpio): ?>
        <a href="<?= url('procesos', 'nuevo', ['id_matricula' => $matricula['id_matricula']]) ?>" class="btn btn-success btn-sm">
          <i class="bi bi-award me-1"></i> Iniciar Proceso de Grado
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Alert Banner -->
<?php if ($esLimpio): ?>
  <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
    <i class="bi bi-patch-check-fill fs-3 text-success me-3"></i>
    <div>
      <strong>Verificación Académica Exitosa:</strong> Este estudiante no registra materias obligatorias pendientes ni habilitaciones en curso. Es elegible para realizar su etapa práctica, pasantía o proyecto de grado.
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
    <i class="bi bi-shield-slash-fill fs-3 text-danger me-3"></i>
    <div>
      <strong>Bloqueo de Etapa Práctica:</strong> El aprendiz cuenta con asignaturas no aprobadas o pendientes de habilitación. Conforme a los requerimientos institucionales, no puede avalarse proceso de grado sin resolución previa o excepción autorizada.
    </div>
  </div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <!-- Active Degree Process Card (if exists) -->
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-award text-purple me-2"></i> Proceso de Etapa Práctica / Grado
        </h6>
        <?php if ($procesoActivo): ?>
          <?= render_badge($procesoActivo['estado'] ?? '') ?>
        <?php else: ?>
          <span class="badge bg-secondary">SIN PROCESO REGISTRADO</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($procesoActivo): ?>
          <div class="row g-3">
            <div class="col-12 col-md-3">
              <span class="text-muted smaller d-block">Modalidad de Grado</span>
              <span class="badge bg-light text-dark border fs-6 mt-1"><?= htmlspecialchars($procesoActivo['modalidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="col-12 col-md-3">
              <span class="text-muted smaller d-block">Entidad Receptora / Proyecto</span>
              <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($procesoActivo['entidad_razon_social'] ?? ($procesoActivo['titulo_proyecto'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
              <?php if (!empty($procesoActivo['entidad_nit'])): ?>
                <div class="smaller text-muted">NIT: <?= htmlspecialchars($procesoActivo['entidad_nit'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-12 col-md-3">
              <span class="text-muted smaller d-block">Horas Cumplidas</span>
              <div class="fw-bold fs-5 text-primary mt-1"><?= number_format((float)($procesoActivo['horas_cumplidas'] ?? 0), 1) ?> hrs</div>
            </div>
            <div class="col-12 col-md-3 text-md-end">
              <span class="text-muted smaller d-block mb-1">Paz y Salvo Final</span>
              <?= render_badge($procesoActivo['paz_salvo_estado'] ?? 'PENDIENTE') ?>
              <div class="mt-2">
                <a href="<?= url('procesos', 'detalle', ['id' => $procesoActivo['id_proceso']]) ?>" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Expediente Completo
                </a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted mb-0 small">
            No se encuentra ningún proceso de grado activo para esta matrícula.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Materias Pendientes Table (if any) -->
  <?php if (!empty($materiasPendientes)): ?>
  <div class="col-12">
    <div class="card shadow-sm border-0 border-start border-danger border-4">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 text-danger fw-bold">
          <i class="bi bi-list-check me-2"></i> Asignaturas Obligatorias Pendientes por Aprobar (<?= count($materiasPendientes) ?>)
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Código</th>
              <th>Materia Obligatoria</th>
              <th>Bloque</th>
              <th>Requisito Prelación</th>
              <th>Estado Actual</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($materiasPendientes as $mp): ?>
              <tr>
                <td><code><?= htmlspecialchars($mp['codigo_materia'] ?? ($mp['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                <td class="fw-semibold"><?= htmlspecialchars($mp['nombre_materia'] ?? ($mp['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>Bloque <?= (int)($mp['periodo_bloque'] ?? 1) ?></td>
                <td>
                  <?= !empty($mp['prerrequisito_nombre']) ? htmlspecialchars($mp['prerrequisito_nombre'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted smaller">Ninguno</span>' ?>
                </td>
                <td><span class="badge bg-danger">NO SUPERADA</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Historial de Evaluaciones y Calificaciones -->
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-journal-text text-primary me-2"></i> Historial Completo de Calificaciones y Malla
        </h6>
        <?php if ($canGrade): ?>
          <a href="<?= url('resultados', 'nuevo', ['id_matricula' => $matricula['id_matricula']]) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Agregar Nota
          </a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-sm datatable align-middle mb-0">
          <thead>
            <tr>
              <th>Periodo</th>
              <th>Materia</th>
              <th>Bloque</th>
              <th>Intento</th>
              <th>Tipo Evaluación</th>
              <th>Nota</th>
              <th>Estado Dictamen</th>
              <th>Soporte / Doc</th>
              <th>Registrado Por</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($historialResultados)): ?>
              <?php foreach ($historialResultados as $res): ?>
                <tr>
                  <td><span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($res['periodo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($res['materia_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <code class="smaller"><?= htmlspecialchars($res['materia_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
                  </td>
                  <td>Bloque <?= (int)($res['periodo_bloque'] ?? 1) ?></td>
                  <td>Intento #<?= (int)($res['numero_intento'] ?? 1) ?></td>
                  <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($res['tipo_evaluacion'] ?? 'ORDINARIA', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <?php if ($res['calificacion'] !== null): ?>
                      <span class="fw-bold <?= ((float)$res['calificacion'] >= 3.0) ? 'text-success' : 'text-danger' ?>">
                        <?= number_format((float)$res['calificacion'], 2) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted smaller">—</span>
                    <?php endif; ?>
                  </td>
                  <td><?= render_badge($res['estado_resultado'] ?? 'EN_CURSO') ?></td>
                  <td>
                    <?php if (!empty($res['soporte_documental'])): ?>
                      <span class="smaller font-monospace text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($res['soporte_documental'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-file-earmark-check text-success me-1"></i><?= htmlspecialchars($res['soporte_documental'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted smaller">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="smaller text-muted">
                    <?= htmlspecialchars($res['registrado_por_nombre'] ?? 'Docente', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
