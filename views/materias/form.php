<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($materia['id_materia']);
$formTitle = $isEdit ? 'Editar Materia: ' . ($materia['nombre'] ?? '') : 'Nueva Materia o Módulo';
$formAction = $isEdit ? url('materias', 'actualizar', ['id' => $materia['id_materia']]) : url('materias', 'guardar');

$programas = $programas ?? [];
$todasMaterias = $todasMaterias ?? [];
$currentMateriaId = (int)($materia['id_materia'] ?? 0);
$selectedProgramaId = (int)($materia['id_programa'] ?? ($_GET['id_programa'] ?? 0));
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-book text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Configure la materia, tipo curricular y reglas de prelación/prerrequisito.</p>
      </div>
      <a href="<?= url('materias', 'index', $selectedProgramaId ? ['id_programa' => $selectedProgramaId] : []) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Materias
      </a>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="materiaForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_materia" id="current_materia_id" value="<?= $currentMateriaId ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <div class="form-section-title">
            <i class="bi bi-journal-plus text-primary"></i> 1. Información General y Ubicación en Malla
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="id_programa" class="form-label">Programa Curricular <span class="text-danger">*</span></label>
              <select class="form-select" id="id_programa" name="id_programa" required>
                <option value="">Seleccione el programa...</option>
                <?php foreach ($programas as $p): ?>
                  <option value="<?= $p['id_programa'] ?>" <?= ($selectedProgramaId == $p['id_programa']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['codigo'] . ' (v' . $p['version'] . ') — ' . $p['nombre'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-3">
              <label for="codigo" class="form-label">Código de Materia <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="codigo" 
                name="codigo" 
                placeholder="Ej. MAT-101" 
                value="<?= htmlspecialchars($materia['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-3">
              <label for="periodo_bloque" class="form-label">Periodo / Bloque <span class="text-danger">*</span></label>
              <input 
                type="number" 
                min="1" 
                max="24" 
                class="form-control" 
                id="periodo_bloque" 
                name="periodo_bloque" 
                value="<?= (int)($materia['periodo_bloque'] ?? 1) ?>" 
                required
              >
              <div class="form-text">Semestre o bloque en que se cursa.</div>
            </div>

            <div class="col-12">
              <label for="nombre" class="form-label">Nombre de la Materia <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                id="nombre" 
                name="nombre" 
                placeholder="Ej. Programación Orientada a Objetos" 
                value="<?= htmlspecialchars($materia['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-diagram-3 text-primary"></i> 2. Tipo y Prerrequisito
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12 col-md-4">
              <label for="tipo" class="form-label">Tipo de Materia <span class="text-danger">*</span></label>
              <select class="form-select" id="tipo" name="tipo" required>
                <option value="OBLIGATORIA" <?= (($materia['tipo'] ?? 'OBLIGATORIA') === 'OBLIGATORIA') ? 'selected' : '' ?>>OBLIGATORIA (Exigida para estar LIMPIO)</option>
                <option value="ELECTIVA" <?= (($materia['tipo'] ?? '') === 'ELECTIVA') ? 'selected' : '' ?>>ELECTIVA</option>
              </select>
            </div>

            <div class="col-12 col-md-3">
              <label for="intensidad" class="form-label">Intensidad Horaria / Créditos</label>
              <input 
                type="number" 
                step="0.5" 
                min="0" 
                class="form-control" 
                id="intensidad" 
                name="intensidad" 
                placeholder="Ej. 64.0" 
                value="<?= htmlspecialchars($materia['intensidad'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-5">
              <label for="id_materia_requisito" class="form-label">Prerrequisito Obligatorio (Opcional)</label>
              <select class="form-select" id="id_materia_requisito" name="id_materia_requisito">
                <option value="">— Sin prerrequisito (Ingreso directo) —</option>
                <?php foreach ($todasMaterias as $tm): ?>
                  <?php if ($isEdit && $tm['id_materia'] == $currentMateriaId) continue; // Una materia no puede ser su propio prerrequisito ?>
                  <option 
                    value="<?= $tm['id_materia'] ?>" 
                    data-programa="<?= $tm['id_programa'] ?>"
                    <?= (($materia['id_materia_requisito'] ?? 0) == $tm['id_materia']) ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($tm['codigo'] . ' — ' . $tm['nombre'] . ' (Bloque ' . $tm['periodo_bloque'] . ')', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-muted">
                <i class="bi bi-info-circle me-1"></i> Una materia no puede ser prerrequisito de sí misma (regla de integridad).
              </div>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('materias', 'index', $selectedProgramaId ? ['id_programa' => $selectedProgramaId] : []) ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Actualizar Materia' : 'Crear Materia' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
  // Filter prerequisite options by selected program
  document.addEventListener('DOMContentLoaded', function() {
    const progSelect = document.getElementById('id_programa');
    const reqSelect = document.getElementById('id_materia_requisito');
    
    if (progSelect && reqSelect) {
      function filterPrereqs() {
        const progId = progSelect.value;
        const options = reqSelect.querySelectorAll('option[data-programa]');
        options.forEach(function(opt) {
          if (!progId || opt.getAttribute('data-programa') === progId) {
            opt.style.display = '';
          } else {
            opt.style.display = 'none';
            if (opt.selected) opt.selected = false;
          }
        });
      }
      progSelect.addEventListener('change', filterPrereqs);
      filterPrereqs();
    }
  });
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
