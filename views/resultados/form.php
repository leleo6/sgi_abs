<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($resultado['id_resultado']);
$formTitle = $isEdit ? 'Editar Calificación #' . $resultado['id_resultado'] : 'Registrar Nueva Calificación / Evaluación';
$formAction = $isEdit ? url('resultados', 'actualizar', ['id' => $resultado['id_resultado']]) : url('resultados', 'guardar');

$matriculas = $matriculas ?? [];
$materias = $materias ?? [];
$selectedMatriculaId = (int)($resultado['id_matricula'] ?? ($_GET['id_matricula'] ?? 0));
$selectedMateriaId = (int)($resultado['id_materia'] ?? ($_GET['id_materia'] ?? 0));
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-9">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-pencil-square text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Ingrese la calificación ordinaria, habilitación o resolución de homologación.</p>
      </div>
      <a href="<?= url('resultados') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Calificaciones
      </a>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="resultadoForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="nota_minima_ref" value="3.00">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_resultado" value="<?= (int)$resultado['id_resultado'] ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <div class="form-section-title">
            <i class="bi bi-person-lines-fill text-primary"></i> 1. Matrícula y Asignatura
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="id_matricula" class="form-label">Estudiante / Matrícula <span class="text-danger">*</span></label>
              <select class="form-select" id="id_matricula" name="id_matricula" required <?= $isEdit ? 'disabled' : '' ?>>
                <option value="">Seleccione el estudiante matriculado...</option>
                <?php foreach ($matriculas as $mat): ?>
                  <option 
                    value="<?= $mat['id_matricula'] ?>" 
                    data-programa="<?= $mat['id_programa'] ?>"
                    <?= ($selectedMatriculaId == $mat['id_matricula']) ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars(($mat['estudiante_apellidos'] ?? '') . ' ' . ($mat['estudiante_nombres'] ?? '') . ' — ' . ($mat['programa_codigo'] ?? '') . ' (' . ($mat['periodo_ingreso'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if ($isEdit): ?>
                <input type="hidden" name="id_matricula" value="<?= $selectedMatriculaId ?>">
              <?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
              <label for="id_materia" class="form-label">Materia Curricular <span class="text-danger">*</span></label>
              <select class="form-select" id="id_materia" name="id_materia" required <?= $isEdit ? 'disabled' : '' ?>>
                <option value="">Seleccione la materia...</option>
                <?php foreach ($materias as $mat): ?>
                  <option 
                    value="<?= $mat['id_materia'] ?>" 
                    data-programa="<?= $mat['id_programa'] ?>"
                    <?= ($selectedMateriaId == $mat['id_materia']) ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($mat['codigo'] . ' — ' . $mat['nombre'] . ' (Bloque ' . $mat['periodo_bloque'] . ')', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if ($isEdit): ?>
                <input type="hidden" name="id_materia" value="<?= $selectedMateriaId ?>">
              <?php endif; ?>
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-award text-primary"></i> 2. Evaluación, Calificación y Dictamen
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
              <label for="periodo" class="form-label">Periodo Académico <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="periodo" 
                name="periodo" 
                placeholder="Ej. 2026-1" 
                value="<?= htmlspecialchars($resultado['periodo'] ?? '2026-1', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-3">
              <label for="numero_intento" class="form-label">Número de Intento <span class="text-danger">*</span></label>
              <input 
                type="number" 
                min="1" 
                max="10" 
                class="form-control" 
                id="numero_intento" 
                name="numero_intento" 
                value="<?= (int)($resultado['numero_intento'] ?? 1) ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-3">
              <label for="tipo_evaluacion" class="form-label">Tipo de Evaluación <span class="text-danger">*</span></label>
              <select class="form-select" id="tipo_evaluacion" name="tipo_evaluacion" required>
                <?php
                $tiposEval = ['ORDINARIA', 'HABILITACION', 'HOMOLOGACION'];
                $selTipo = $resultado['tipo_evaluacion'] ?? 'ORDINARIA';
                foreach ($tiposEval as $te):
                ?>
                  <option value="<?= $te ?>" <?= $selTipo === $te ? 'selected' : '' ?>><?= $te ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-3">
              <label for="calificacion_input" class="form-label">Calificación (0.00 a 5.00)</label>
              <input 
                type="number" 
                step="0.01" 
                min="0" 
                max="5" 
                class="form-control fw-bold" 
                id="calificacion_input" 
                name="calificacion" 
                placeholder="Ej. 4.20" 
                value="<?= htmlspecialchars($resultado['calificacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <!-- Dictamen en tiempo real -->
            <div class="col-12">
              <div class="p-3 bg-light rounded border d-flex align-items-center justify-content-between">
                <div>
                  <span class="small fw-semibold text-secondary d-block">Estado Calculado en Tiempo Real:</span>
                  <span class="smaller text-muted">Aprobado &ge; 3.0 &bull; Habilitación &ge; 2.5 y &lt; 3.0 &bull; Reprobado &lt; 2.5</span>
                </div>
                <div>
                  <span id="estado_resultado_live" class="badge bg-warning text-dark fs-6 px-3 py-2">
                    <?= !empty($resultado['estado_resultado']) ? htmlspecialchars($resultado['estado_resultado'], ENT_QUOTES, 'UTF-8') : 'EN CURSO' ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- DOCUMENTO DE SOPORTE (OBLIGATORIO SI ES HOMOLOGACIÓN) -->
          <div id="soporte_section" class="form-section-title">
            <i class="bi bi-file-earmark-text text-primary"></i> 3. Soporte Documental (Acta o Resolución)
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12">
              <label for="soporte_documental" class="form-label">
                Número de Acta / Resolución / Soporte <span id="soporte_required_badge" class="text-danger" style="display:none;">* Requerido para homologaciones</span>
              </label>
              <input 
                type="text" 
                class="form-control" 
                id="soporte_documental" 
                name="soporte_documental" 
                placeholder="Ej. Acta de Consejo Académico No. 042-2026" 
                value="<?= htmlspecialchars($resultado['soporte_documental'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
              <div class="form-text">Obligatorio si el tipo de evaluación es HOMOLOGACION.</div>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('resultados') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Actualizar Calificación' : 'Guardar Calificación' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const matSelect = document.getElementById('id_matricula');
    const materiaSelect = document.getElementById('id_materia');
    const tipoEval = document.getElementById('tipo_evaluacion');
    const soporteDoc = document.getElementById('soporte_documental');
    const soporteBadge = document.getElementById('soporte_required_badge');

    // Filter subjects by program of selected enrollment
    function filterSubjectsByMatricula() {
      if (!matSelect || !materiaSelect) return;
      const selectedMat = matSelect.options[matSelect.selectedIndex];
      const progId = selectedMat ? selectedMat.getAttribute('data-programa') : '';

      const options = materiaSelect.querySelectorAll('option[data-programa]');
      options.forEach(function(opt) {
        if (!progId || opt.getAttribute('data-programa') === progId) {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
          if (opt.selected) opt.selected = false;
        }
      });
    }

    if (matSelect) {
      matSelect.addEventListener('change', filterSubjectsByMatricula);
      filterSubjectsByMatricula();
    }

    // Toggle homologation required indicator
    function checkHomologacion() {
      if (tipoEval && tipoEval.value === 'HOMOLOGACION') {
        if (soporteBadge) soporteBadge.style.display = 'inline';
        if (soporteDoc) soporteDoc.required = true;
      } else {
        if (soporteBadge) soporteBadge.style.display = 'none';
        if (soporteDoc) soporteDoc.required = false;
      }
    }

    if (tipoEval) {
      tipoEval.addEventListener('change', checkHomologacion);
      checkHomologacion();
    }
  });
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
