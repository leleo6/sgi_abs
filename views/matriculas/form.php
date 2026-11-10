<?php
include BASE_PATH . '/views/layouts/header.php';

$isEdit = !empty($matricula['id_matricula']);
$formTitle = $isEdit ? 'Editar Matrícula #' . $matricula['id_matricula'] : 'Nueva Matrícula de Estudiante';
$formAction = $isEdit ? url('matriculas', 'actualizar', ['id' => $matricula['id_matricula']]) : url('matriculas', 'guardar');

$estudiantes = $estudiantes ?? [];
$programas = $programas ?? [];
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-9">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-person-plus text-primary me-2"></i> <?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted small mb-0">Asocie un estudiante al programa académico y su respectivo periodo de ingreso.</p>
      </div>
      <a href="<?= url('matriculas') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Matrículas
      </a>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="matriculaForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_matricula" value="<?= (int)$matricula['id_matricula'] ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <div class="form-section-title">
            <i class="bi bi-person-badge text-primary"></i> 1. Estudiante y Programa
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label for="id_usuario" class="form-label">Estudiante <span class="text-danger">*</span></label>
              <?php if ($isEdit): ?>
                <input type="hidden" name="id_usuario" value="<?= (int)$matricula['id_usuario'] ?>">
                <input 
                  type="text" 
                  class="form-control bg-light" 
                  value="<?= htmlspecialchars(($matricula['estudiante_nombres'] ?? '') . ' ' . ($matricula['estudiante_apellidos'] ?? '') . ' (' . ($matricula['documento'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?>" 
                  readonly
                >
                <div class="form-text">El estudiante asignado a una matrícula no puede modificarse.</div>
              <?php else: ?>
                <select class="form-select" id="id_usuario" name="id_usuario" required>
                  <option value="">Seleccione el estudiante...</option>
                  <?php foreach ($estudiantes as $est): ?>
                    <option value="<?= $est['id_usuario'] ?>" <?= (($matricula['id_usuario'] ?? '') == $est['id_usuario']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($est['apellidos'] . ' ' . $est['nombres'] . ' — ' . ($est['tipo_documento'] ?? 'CC') . ' ' . ($est['documento'] ?? '') . ' (' . $est['correo'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Solo se listan usuarios con rol ESTUDIANTE.</div>
              <?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
              <label for="id_programa" class="form-label">Programa Curricular / Versión <span class="text-danger">*</span></label>
              <select class="form-select" id="id_programa" name="id_programa" required>
                <option value="">Seleccione el programa...</option>
                <?php foreach ($programas as $p): ?>
                  <option value="<?= $p['id_programa'] ?>" <?= (($matricula['id_programa'] ?? '') == $p['id_programa']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['codigo'] . ' (v' . $p['version'] . ') — ' . $p['nombre'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-calendar3 text-primary"></i> 2. Periodo y Estado de Matrícula
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12 col-md-4">
              <label for="periodo_ingreso" class="form-label">Periodo de Ingreso <span class="text-danger">*</span></label>
              <input 
                type="text" 
                class="form-control font-monospace" 
                id="periodo_ingreso" 
                name="periodo_ingreso" 
                placeholder="Ej. 2026-1" 
                value="<?= htmlspecialchars($matricula['periodo_ingreso'] ?? '2026-1', ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="fecha_ingreso" class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
              <input 
                type="date" 
                class="form-control" 
                id="fecha_ingreso" 
                name="fecha_ingreso" 
                value="<?= htmlspecialchars($matricula['fecha_ingreso'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" 
                required
              >
            </div>

            <div class="col-12 col-md-4">
              <label for="estado" class="form-label">Estado de Matrícula <span class="text-danger">*</span></label>
              <select class="form-select" id="estado" name="estado" required>
                <?php
                $estadosMat = ['ACTIVA', 'SUSPENDIDA', 'FINALIZADA', 'RETIRADA'];
                $selEstado = $matricula['estado'] ?? 'ACTIVA';
                foreach ($estadosMat as $em):
                ?>
                  <option value="<?= $em ?>" <?= $selEstado === $em ? 'selected' : '' ?>><?= $em ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('matriculas') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-check-lg"></i>
            <span><?= $isEdit ? 'Actualizar Matrícula' : 'Registrar Matrícula' ?></span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
