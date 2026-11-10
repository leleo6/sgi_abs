<?php
include BASE_PATH . '/views/layouts/header.php';

$proceso = $proceso ?? [];
$evidencias = $evidencias ?? [];
$checklist = $checklist ?? [
    'malla_limpia' => true,
    'horas_completas' => true,
    'evidencias_aprobadas' => true,
    'documentos_identidad' => true
];
$canEmitirPazSalvo = !empty($checklist['malla_limpia']) && !empty($checklist['horas_completas']) && !empty($checklist['evidencias_aprobadas']) && !empty($checklist['documentos_identidad']);

$canManage = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION', 'RESP_PRACTICAS']);
$isCoordOrAdmin = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION']);
$estadoProceso = $proceso['estado'] ?? 'ELEGIBLE';
?>

<!-- Header with Process Status & Transition Buttons -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      
      <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
          <span class="badge bg-light text-secondary border font-monospace">EXP-<?= (int)$proceso['id_proceso'] ?></span>
          <h4 class="fw-bold tracking-tight text-dark mb-0">
            <?= htmlspecialchars($proceso['modalidad'] ?? 'PRACTICA', ENT_QUOTES, 'UTF-8') ?>: 
            <?= htmlspecialchars($proceso['entidad_razon_social'] ?? ($proceso['titulo_proyecto'] ?? 'Sin título'), ENT_QUOTES, 'UTF-8') ?>
          </h4>
          <?= render_badge($estadoProceso) ?>
        </div>
        <p class="text-muted small mb-0">
          <strong>Estudiante:</strong> <?= htmlspecialchars(($proceso['estudiante_nombres'] ?? '') . ' ' . ($proceso['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?> &bull; 
          <strong>Programa:</strong> <?= htmlspecialchars($proceso['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull;
          <strong>Horas:</strong> <span class="fw-bold text-primary"><?= number_format((float)($proceso['horas_cumplidas'] ?? 0), 1) ?> hrs</span>
        </p>
      </div>

      <!-- Action Transitions -->
      <?php if ($canManage): ?>
      <div class="d-flex gap-2 flex-wrap">
        <?php if ($estadoProceso === 'ELEGIBLE'): ?>
          <a href="<?= url('procesos', 'cambiar_estado', ['id' => $proceso['id_proceso'], 'nuevo_estado' => 'AVALADO']) ?>" 
             class="btn btn-success btn-sm d-flex align-items-center gap-1"
             data-confirm="¿Desea AVALAR formalmente este proceso de práctica?">
            <i class="bi bi-check2-circle"></i> Avalar Proceso
          </a>
        <?php elseif ($estadoProceso === 'AVALADO'): ?>
          <a href="<?= url('procesos', 'cambiar_estado', ['id' => $proceso['id_proceso'], 'nuevo_estado' => 'EN_EJECUCION']) ?>" 
             class="btn btn-warning btn-sm d-flex align-items-center gap-1"
             data-confirm="¿Desea iniciar la etapa de EJECUCIÓN del proceso?">
            <i class="bi bi-play-circle"></i> Iniciar Ejecución
          </a>
        <?php elseif ($estadoProceso === 'EN_EJECUCION'): ?>
          <a href="<?= url('procesos', 'cambiar_estado', ['id' => $proceso['id_proceso'], 'nuevo_estado' => 'FINALIZADO']) ?>" 
             class="btn btn-purple btn-sm d-flex align-items-center gap-1"
             data-confirm="¿Desea marcar como FINALIZADO este proceso tras cumplimiento de horas y requisitos?">
            <i class="bi bi-flag"></i> Finalizar Proceso
          </a>
          <a href="<?= url('procesos', 'cambiar_estado', ['id' => $proceso['id_proceso'], 'nuevo_estado' => 'INTERRUMPIDO']) ?>" 
             class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
             data-confirm="¿Confirma la INTERRUPCIÓN del convenio/práctica?">
            <i class="bi bi-stop-circle"></i> Interrumpir
          </a>
        <?php endif; ?>
        
        <a href="<?= url('procesos', 'editar', ['id' => $proceso['id_proceso']]) ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-pencil"></i>
        </a>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<div class="row g-4">
  
  <!-- Left Column: Details & Evidencias Table -->
  <div class="col-12 col-xl-8">
    
    <!-- Details Card -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle text-primary me-2"></i> Información del Convenio y Supervisión</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php if (($proceso['modalidad'] ?? '') === 'PROYECTO'): ?>
            <div class="col-12">
              <span class="smaller text-muted d-block">Título del Proyecto</span>
              <strong class="text-dark"><?= htmlspecialchars($proceso['titulo_proyecto'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
          <?php else: ?>
            <div class="col-12 col-md-6">
              <span class="smaller text-muted d-block">Entidad Receptora</span>
              <strong class="text-dark"><?= htmlspecialchars($proceso['entidad_razon_social'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
              <div class="smaller text-muted font-monospace">NIT: <?= htmlspecialchars($proceso['entidad_nit'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-12 col-md-6">
              <span class="smaller text-muted d-block">Convenio Institucional</span>
              <strong class="text-dark font-monospace"><?= htmlspecialchars($proceso['convenio_numero'] ?? 'Sin convenio formal', ENT_QUOTES, 'UTF-8') ?></strong>
              <div class="smaller text-muted">Vigencia: <?= htmlspecialchars($proceso['convenio_inicio'] ?? '—') ?> al <?= htmlspecialchars($proceso['convenio_fin'] ?? '—') ?></div>
            </div>
          <?php endif; ?>

          <div class="col-12 col-md-6">
            <span class="smaller text-muted d-block">Tutor / Supervisor Empresarial</span>
            <strong class="text-dark"><?= htmlspecialchars($proceso['supervisor_nombre'] ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="smaller text-muted"><?= htmlspecialchars($proceso['supervisor_correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
          </div>

          <div class="col-12 col-md-6">
            <span class="smaller text-muted d-block">Fechas de Práctica</span>
            <strong class="text-dark"><?= htmlspecialchars($proceso['fecha_inicio'] ?? '—') ?> hasta <?= htmlspecialchars($proceso['fecha_fin'] ?? '—') ?></strong>
            <div class="smaller text-muted">Horas Registradas: <span class="fw-bold text-primary"><?= number_format((float)($proceso['horas_cumplidas'] ?? 0), 1) ?>h</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Evidencias / Bitácoras Table -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-folder2-open text-primary me-2"></i> Portafolio de Evidencias y Bitácoras
        </h6>
        <a href="<?= url('evidencias', 'subir', ['id_proceso' => $proceso['id_proceso']]) ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-cloud-arrow-up me-1"></i> Cargar Evidencia
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Tipo Evidencia</th>
              <th>Versión</th>
              <th>Archivo</th>
              <th>Fecha Entrega</th>
              <th>Estado</th>
              <th class="text-end">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($evidencias)): ?>
              <?php foreach ($evidencias as $ev): ?>
                <tr>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($ev['tipo_evidencia'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($ev['observaciones'])): ?>
                      <div class="smaller text-muted text-truncate" style="max-width: 180px;"><?= htmlspecialchars($ev['observaciones'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-secondary-subtle text-secondary font-monospace">v<?= (int)($ev['version_entrega'] ?? 1) ?></span></td>
                  <td>
                    <?php if (!empty($ev['nombre_archivo'])): ?>
                      <a href="<?= url('evidencias', 'descargar', ['id' => $ev['id_evidencia']]) ?>" class="smaller text-decoration-none text-truncate d-inline-block" style="max-width: 140px;">
                        <i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($ev['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted smaller">Pendiente carga</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="smaller text-muted"><?= htmlspecialchars($ev['fecha_entrega'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><?= render_badge($ev['estado'] ?? 'PENDIENTE') ?></td>
                  <td class="text-end">
                    <?php if ($canManage): ?>
                      <div class="btn-group btn-group-sm">
                        <a href="<?= url('evidencias', 'revisar', ['id' => $ev['id_evidencia'], 'nuevo_estado' => 'APROBADA']) ?>" class="btn btn-outline-success" title="Aprobar Evidencia">
                          <i class="bi bi-check-lg"></i>
                        </a>
                        <a href="<?= url('evidencias', 'revisar', ['id' => $ev['id_evidencia'], 'nuevo_estado' => 'RECHAZADA']) ?>" class="btn btn-outline-danger" title="Rechazar">
                          <i class="bi bi-x-lg"></i>
                        </a>
                      </div>
                    <?php else: ?>
                      <span class="text-muted smaller">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4 small">
                  No se han registrado entregables o bitácoras en este proceso.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Right Column: Paz y Salvo Verification Checklist -->
  <div class="col-12 col-xl-4">
    
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-card-checklist text-success me-2"></i> Lista de Chequeo para Titulación
        </h6>
      </div>
      <div class="card-body">
        
        <!-- Checklist items -->
        <div class="checklist-item <?= !empty($checklist['malla_limpia']) ? 'check-pass' : 'check-fail' ?>">
          <div class="d-flex align-items-center">
            <i class="bi bi-<?= !empty($checklist['malla_limpia']) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> fs-5 me-2"></i>
            <div>
              <div class="fw-semibold small">Malla Académica Superada</div>
              <div class="smaller text-muted">100% materias aprobadas</div>
            </div>
          </div>
          <span class="badge bg-<?= !empty($checklist['malla_limpia']) ? 'success' : 'danger' ?>"><?= !empty($checklist['malla_limpia']) ? 'CUMPLE' : 'PENDIENTE' ?></span>
        </div>

        <div class="checklist-item <?= !empty($checklist['horas_completas']) ? 'check-pass' : 'check-fail' ?>">
          <div class="d-flex align-items-center">
            <i class="bi bi-<?= !empty($checklist['horas_completas']) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> fs-5 me-2"></i>
            <div>
              <div class="fw-semibold small">Total de Horas Prácticas</div>
              <div class="smaller text-muted"><?= number_format((float)($proceso['horas_cumplidas'] ?? 0), 1) ?> horas reportadas</div>
            </div>
          </div>
          <span class="badge bg-<?= !empty($checklist['horas_completas']) ? 'success' : 'danger' ?>"><?= !empty($checklist['horas_completas']) ? 'CUMPLE' : 'PENDIENTE' ?></span>
        </div>

        <div class="checklist-item <?= !empty($checklist['evidencias_aprobadas']) ? 'check-pass' : 'check-fail' ?>">
          <div class="d-flex align-items-center">
            <i class="bi bi-<?= !empty($checklist['evidencias_aprobadas']) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> fs-5 me-2"></i>
            <div>
              <div class="fw-semibold small">Evidencias y Bitácoras</div>
              <div class="smaller text-muted">Revisadas y avaladas</div>
            </div>
          </div>
          <span class="badge bg-<?= !empty($checklist['evidencias_aprobadas']) ? 'success' : 'danger' ?>"><?= !empty($checklist['evidencias_aprobadas']) ? 'CUMPLE' : 'PENDIENTE' ?></span>
        </div>

        <div class="checklist-item <?= !empty($checklist['documentos_identidad']) ? 'check-pass' : 'check-fail' ?>">
          <div class="d-flex align-items-center">
            <i class="bi bi-<?= !empty($checklist['documentos_identidad']) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> fs-5 me-2"></i>
            <div>
              <div class="fw-semibold small">Documentos de Identidad & APE</div>
              <div class="smaller text-muted">Legajo documental validado</div>
            </div>
          </div>
          <span class="badge bg-<?= !empty($checklist['documentos_identidad']) ? 'success' : 'danger' ?>"><?= !empty($checklist['documentos_identidad']) ? 'CUMPLE' : 'PENDIENTE' ?></span>
        </div>

        <!-- Paz y Salvo Status / Emission Section -->
        <div class="mt-4 pt-3 border-top">
          <?php if (($proceso['paz_salvo_estado'] ?? '') === 'EMITIDO'): ?>
            <div class="alert alert-success border-0 shadow-sm text-center py-3">
              <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-1"></i>
              <div class="fw-bold">Paz y Salvo Emitido</div>
              <div class="font-monospace smaller text-dark mt-1">Cód: <?= htmlspecialchars($proceso['codigo_verificacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="smaller text-muted mt-1">Fecha: <?= htmlspecialchars($proceso['paz_salvo_emitido_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              
              <div class="d-grid gap-2 mt-3">
                <a href="<?= url('procesos', 'certificado', ['id' => $proceso['id_proceso']]) ?>" class="btn btn-outline-success btn-sm" target="_blank">
                  <i class="bi bi-printer me-1"></i> Imprimir Certificado
                </a>
                <?php if ($isCoordOrAdmin): ?>
                  <a href="<?= url('procesos', 'revocar_paz_salvo', ['id' => $proceso['id_proceso']]) ?>" 
                     class="btn btn-outline-danger btn-sm" 
                     data-confirm="¿Está seguro de REVOCAR el Paz y Salvo emitido? Esta acción se auditará permanentemente.">
                    <i class="bi bi-x-circle me-1"></i> Revocar Paz y Salvo
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="d-grid">
              <?php if ($isCoordOrAdmin): ?>
                <a href="<?= $canEmitirPazSalvo ? url('procesos', 'emitir_paz_salvo', ['id' => $proceso['id_proceso']]) : '#' ?>" 
                   class="btn btn-success py-2 fw-semibold shadow-sm <?= !$canEmitirPazSalvo ? 'disabled' : '' ?>"
                   <?= !$canEmitirPazSalvo ? 'aria-disabled="true" data-bs-toggle="tooltip" title="No se puede emitir el Paz y Salvo hasta que todos los puntos del checklist estén en estado CUMPLE."' : 'data-confirm="¿Confirma la emisión del Paz y Salvo Académico y de Prácticas para este estudiante?"' ?>>
                  <i class="bi bi-award-fill me-1"></i> Emitir Paz y Salvo
                </a>
                <?php if (!$canEmitirPazSalvo): ?>
                  <div class="smaller text-muted text-center mt-2">
                    <i class="bi bi-info-circle me-1"></i> Requiere validar todos los requisitos del checklist.
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <div class="alert alert-light border text-center py-2 smaller text-muted mb-0">
                  El Paz y Salvo se encuentra pendiente de emisión por la Coordinación.
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
