<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($programa['id_programa']);
$formTitle = $isEdit ? 'Editar Programa: ' . ($programa['nombre'] ?? '') : 'Nuevo Programa Académico';
$formAction = $isEdit ? url('programas', 'actualizar', ['id' => $programa['id_programa']]) : url('programas', 'guardar');
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-mortarboard text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Especifique los parámetros y reglas de aprobación del programa académico.</p>
      </div>
      <a href="<?= url('programas') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Programas
      </a>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="programaForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_programa" value="<?= (int)$programa['id_programa'] ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <!-- IDENTIFICACIÓN DEL PROGRAMA -->
          <div class="form-section-title">
            <i class="bi bi-card-text text-primary"></i> 1. Identificación y Malla
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
              <label for="codigo" class="form-label">Código del Programa <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="codigo" 
                name="codigo" 
                placeholder="Ej. T-SOFT" 
                value="<?= htmlspecialchars($programa['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="version" class="form-label">Versión de la Malla <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="version" 
                name="version" 
                placeholder="Ej. 1.0" 
                value="<?= htmlspecialchars($programa['version'] ?? '1.0', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
              <div class="form-text">La combinación (código + versión) debe ser única.</div>
            </div>

            <div class="col-12 col-md-4">
              <label for="regimen" class="form-label">Régimen Institucional <span class="text-danger">*</span></label>
              <select class="form-select" id="regimen" name="regimen" required>
                <?php
                $regimenes = ['SENA', 'IES', 'ETDH', 'OTRO'];
                $selRegimen = $programa['regimen'] ?? 'SENA';
                foreach ($regimenes as $reg):
                ?>
                  <option value="<?= $reg ?>" <?= $selRegimen === $reg ? 'selected' : '' ?>><?= $reg ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label for="nombre" class="form-label">Nombre del Programa <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control" 
                id="nombre" 
                name="nombre" 
                placeholder="Ej. Técnico Laboral en Desarrollo de Software" 
                value="<?= htmlspecialchars($programa['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>
          </div>

          <!-- PARÁMETROS ACADÉMICOS -->
          <div class="form-section-title">
            <i class="bi bi-sliders text-primary"></i> 2. Parámetros Curriculares y Calificación
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
              <label for="unidad_medida" class="form-label">Unidad de Medida <span class="text-danger">*</span></label>
              <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                <?php
                $unidades = ['MATERIAS', 'CREDITOS', 'HORAS'];
                $selUnidad = $programa['unidad_medida'] ?? 'MATERIAS';
                foreach ($unidades as $u):
                ?>
                  <option value="<?= $u ?>" <?= $selUnidad === $u ? 'selected' : '' ?>><?= $u ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label for="total_periodos" class="form-label">Total Periodos / Bloques <span class="text-danger">*</span></label>
              <input 
                type="number" 
                min="1" 
                max="24" 
                class="form-control" 
                id="total_periodos" 
                name="total_periodos" 
                value="<?= (int)($programa['total_periodos'] ?? 4) ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="nota_minima" class="form-label">Nota Mínima Aprobatoria (Escala 0 a 5) <span class="text-danger">*</span></label>
              <input 
                type="number" 
                step="0.01" 
                min="0" 
                max="5" 
                class="form-control" 
                id="nota_minima" 
                name="nota_minima" 
                value="<?= htmlspecialchars($programa['nota_minima'] ?? '3.00', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
              <div class="form-text">Define el umbral para considerar una materia superada.</div>
            </div>
          </div>

          <!-- VIGENCIAS Y ESTADO -->
          <div class="form-section-title">
            <i class="bi bi-calendar-range text-primary"></i> 3. Fechas de Vigencia y Estado
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12 col-md-4">
              <label for="vigente_desde" class="form-label">Vigente Desde <span class="text-danger">*</span></label>
              <input 
                type="date" 
                class="form-control" 
                id="vigente_desde" 
                name="vigente_desde" 
                value="<?= htmlspecialchars($programa['vigente_desde'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="vigente_hasta" class="form-label">Vigente Hasta (Opcional)</label>
              <input 
                type="date" 
                class="form-control" 
                id="vigente_hasta" 
                name="vigente_hasta" 
                value="<?= htmlspecialchars($programa['vigente_hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="estado" class="form-label">Estado de la Malla <span class="text-danger">*</span></label>
              <select class="form-select" id="estado" name="estado" required>
                <?php
                $estadosProg = ['BORRADOR', 'PUBLICADA', 'CERRADA'];
                $selEstado = $programa['estado'] ?? 'BORRADOR';
                foreach ($estadosProg as $ep):
                ?>
                  <option value="<?= $ep ?>" <?= $selEstado === $ep ? 'selected' : '' ?>><?= $ep ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('programas') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Actualizar Programa' : 'Crear Programa' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
