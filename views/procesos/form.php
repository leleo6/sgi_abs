<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($proceso['id_proceso']);
$formTitle = $isEdit ? 'Editar Proceso de Grado #' . $proceso['id_proceso'] : 'Registrar Nuevo Proceso de Grado';
$formAction = $isEdit ? url('procesos', 'actualizar', ['id' => $proceso['id_proceso']]) : url('procesos', 'guardar');

$matriculas = $matriculas ?? [];
$selectedMatriculaId = (int)($proceso['id_matricula'] ?? ($_GET['id_matricula'] ?? 0));
$estudianteEsLimpio = $estudianteEsLimpio ?? true;
$materiasPendientes = $materiasPendientes ?? [];
$isAdminOrCoord = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION']);
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-award text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Formalización de modalidad de titulación, entidad receptora y datos de seguimiento.</p>
      </div>
      <a href="<?= url('procesos') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Procesos
      </a>
    </div>

    <!-- BLOQUEO POR DEUDA ACADÉMICA -->
    <?php if (!$estudianteEsLimpio && !$isEdit): ?>
      <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="d-flex">
          <i class="bi bi-shield-lock-fill fs-2 text-danger me-3"></i>
          <div>
            <h5 class="alert-heading fw-bold">Bloqueo de Elegibilidad: Estudiante con Deuda Académica</h5>
            <p class="mb-2 small">
              El estudiante seleccionado registra asignaturas pendientes de aprobación en su plan de estudios. Por política institucional, no se permite la apertura de un proceso de etapa práctica a menos que cuente con una <strong>Excepción Formal Autorizada</strong> por Coordinación.
            </p>
            <?php if (!empty($materiasPendientes)): ?>
              <div class="fw-semibold small mb-1">Materias Pendientes:</div>
              <ul class="mb-2 smaller">
                <?php foreach ($materiasPendientes as $mp): ?>
                  <li><?= htmlspecialchars($mp['codigo_materia'] ?? '') ?> - <?= htmlspecialchars($mp['nombre_materia'] ?? '') ?> (Bloque <?= $mp['periodo_bloque'] ?? 1 ?>)</li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= $formAction ?>" id="procesoForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_proceso" value="<?= (int)$proceso['id_proceso'] ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <!-- 1. MATRÍCULA Y MODALIDAD -->
          <div class="form-section-title">
            <i class="bi bi-person-badge text-primary"></i> 1. Matrícula y Modalidad de Grado
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-7">
              <label for="id_matricula" class="form-label">Estudiante / Matrícula <span class="text-danger">*</span></label>
              <?php if ($isEdit): ?>
                <input type="hidden" name="id_matricula" value="<?= $selectedMatriculaId ?>">
                <input 
                  type="text" 
                  class="form-control bg-light" 
                  value="<?= htmlspecialchars(($proceso['estudiante_nombres'] ?? '') . ' ' . ($proceso['estudiante_apellidos'] ?? '') . ' — ' . ($proceso['programa_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                  readonly
                >
              <?php else: ?>
                <select class="form-select" id="id_matricula" name="id_matricula" required onchange="window.location.href = 'index.php?module=procesos&action=nuevo&id_matricula=' + this.value">
                  <option value="">Seleccione el estudiante...</option>
                  <?php foreach ($matriculas as $mat): ?>
                    <option value="<?= $mat['id_matricula'] ?>" <?= ($selectedMatriculaId == $mat['id_matricula']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars(($mat['estudiante_apellidos'] ?? '') . ' ' . ($mat['estudiante_nombres'] ?? '') . ' — ' . ($mat['programa_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>

            <div class="col-12 col-md-5">
              <label for="modalidad" class="form-label">Modalidad de Grado <span class="text-danger">*</span></label>
              <select class="form-select" id="modalidad" name="modalidad" required>
                <?php
                $modalidades = ['PRACTICA', 'PASANTIA', 'PROYECTO'];
                $selModalidad = $proceso['modalidad'] ?? 'PRACTICA';
                foreach ($modalidades as $mod):
                ?>
                  <option value="<?= $mod ?>" <?= $selModalidad === $mod ? 'selected' : '' ?>><?= $mod ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- 2. DATOS DE ENTIDAD / CONVENIO / PROYECTO -->
          <div class="form-section-title">
            <i class="bi bi-building text-primary"></i> 2. Entidad Receptora o Proyecto
          </div>

          <!-- Solo para PROYECTO -->
          <div id="seccion_proyecto" class="row g-3 mb-3" style="display: <?= ($selModalidad === 'PROYECTO') ? 'flex' : 'none' ?>;">
            <div class="col-12">
              <label for="titulo_proyecto" class="form-label">Título del Proyecto de Grado <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                id="titulo_proyecto" 
                name="titulo_proyecto" 
                placeholder="Ej. Sistema de Monitoreo y Trazabilidad para la Cadena de Suministro" 
                value="<?= htmlspecialchars($proceso['titulo_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>
          </div>

          <!-- Para PRACTICA / PASANTIA -->
          <div id="seccion_empresa" class="row g-3 mb-4" style="display: <?= ($selModalidad !== 'PROYECTO') ? 'flex' : 'none' ?>;">
            <div class="col-12 col-md-4">
              <label for="entidad_nit" class="form-label">NIT Entidad Receptora</label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="entidad_nit" 
                name="entidad_nit" 
                placeholder="Ej. 900.123.456-7" 
                value="<?= htmlspecialchars($proceso['entidad_nit'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-8">
              <label for="entidad_razon_social" class="form-label">Razón Social Entidad</label>
              <input 
                type="text" 
                class="form-control" 
                id="entidad_razon_social" 
                name="entidad_razon_social" 
                placeholder="Ej. Soluciones Tecnológicas del Caribe S.A.S." 
                value="<?= htmlspecialchars($proceso['entidad_razon_social'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="convenio_numero" class="form-label">Número de Convenio / Contrato</label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="convenio_numero" 
                name="convenio_numero" 
                placeholder="Ej. CONV-2026-089" 
                value="<?= htmlspecialchars($proceso['convenio_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="convenio_inicio" class="form-label">Inicio Vigencia Convenio</label>
              <input 
                type="date" 
                class="form-control" 
                id="convenio_inicio" 
                name="convenio_inicio" 
                value="<?= htmlspecialchars($proceso['convenio_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="convenio_fin" class="form-label">Fin Vigencia Convenio</label>
              <input 
                type="date" 
                class="form-control" 
                id="convenio_fin" 
                name="convenio_fin" 
                value="<?= htmlspecialchars($proceso['convenio_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>
          </div>

          <!-- 3. SUPERVISIÓN Y EJECUCIÓN -->
          <div class="form-section-title">
            <i class="bi bi-clock-history text-primary"></i> 3. Fechas de Ejecución y Tutor / Supervisor
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="supervisor_nombre" class="form-label">Nombre del Tutor / Supervisor</label>
              <input 
                type="text" 
                class="form-control" 
                id="supervisor_nombre" 
                name="supervisor_nombre" 
                placeholder="Ej. Ing. Carlos Rodríguez" 
                value="<?= htmlspecialchars($proceso['supervisor_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-6">
              <label for="supervisor_correo" class="form-label">Correo Electrónico Tutor</label>
              <input 
                type="email" 
                class="form-control" 
                id="supervisor_correo" 
                name="supervisor_correo" 
                placeholder="supervisor@empresa.com" 
                value="<?= htmlspecialchars($proceso['supervisor_correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
              <input 
                type="date" 
                class="form-control" 
                id="fecha_inicio" 
                name="fecha_inicio" 
                value="<?= htmlspecialchars($proceso['fecha_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="fecha_fin" class="form-label">Fecha Estimada de Finalización</label>
              <input 
                type="date" 
                class="form-control" 
                id="fecha_fin" 
                name="fecha_fin" 
                value="<?= htmlspecialchars($proceso['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="horas_cumplidas" class="form-label">Horas Cumplidas Acumuladas</label>
              <input 
                type="number" 
                step="0.5" 
                min="0" 
                class="form-control" 
                id="horas_cumplidas" 
                name="horas_cumplidas" 
                value="<?= htmlspecialchars($proceso['horas_cumplidas'] ?? '0.0', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>
          </div>

          <!-- 4. EXCEPCIÓN FORMAL (SI APLICA PARA ESTUDIANTES CON DEUDA) -->
          <?php if ($isAdminOrCoord): ?>
          <div class="form-section-title">
            <i class="bi bi-shield-exclamation text-warning"></i> 4. Excepción Formal a la Elegibilidad (Autorización de Coordinación)
          </div>

          <div class="p-3 bg-light rounded border mb-2">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="excepcion_acta" class="form-label">Número de Acta de Aprobación Excepción</label>
                <input 
                  type="text" 
                  class="form-control font-monospace" 
                  id="excepcion_acta" 
                  name="excepcion_acta" 
                  placeholder="Ej. ACTA-COORD-2026-15" 
                  value="<?= htmlspecialchars($proceso['excepcion_acta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
              </div>

              <div class="col-12 col-md-6">
                <label for="excepcion_vigente_hasta" class="form-label">Vigencia Límite de la Excepción</label>
                <input 
                  type="date" 
                  class="form-control" 
                  id="excepcion_vigente_hasta" 
                  name="excepcion_vigente_hasta" 
                  value="<?= htmlspecialchars($proceso['excepcion_vigente_hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
              </div>

              <div class="col-12">
                <label for="excepcion_motivo" class="form-label">Motivo y Justificación de la Excepción</label>
                <textarea 
                  class="form-control" 
                  id="excepcion_motivo" 
                  name="excepcion_motivo" 
                  rows="2" 
                  placeholder="Explique el sustento pedagógico o institucional..."
                ><?= htmlspecialchars($proceso['excepcion_motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('procesos') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button 
            type="submit" 
            class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm"
            <?= (!$estudianteEsLimpio && !$isEdit && !$isAdminOrCoord) ? 'disabled' : '' ?>
          >
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Guardar Cambios' : 'Registrar Proceso' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const modSelect = document.getElementById('modalidad');
    const secProyecto = document.getElementById('seccion_proyecto');
    const secEmpresa = document.getElementById('seccion_empresa');

    if (modSelect) {
      modSelect.addEventListener('change', function() {
        if (this.value === 'PROYECTO') {
          secProyecto.style.display = 'flex';
          secEmpresa.style.display = 'none';
        } else {
          secProyecto.style.display = 'none';
          secEmpresa.style.display = 'flex';
        }
      });
    }
  });
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
