<?php
include BASE_PATH . '/views/layouts/header.php';

$materias = $materias ?? [];
$programas = $programas ?? [];
$selectedPrograma = $_GET['id_programa'] ?? '';
$canManage = in_array($currentUser['rol'] ?? '', ['ADMIN', 'COORDINACION']);
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-book text-primary me-2"></i> Materias y Módulos Curriculares
    </h3>
    <p class="text-muted small mb-0">Gestión de asignaturas, prerrequisitos, intensidades horarias y bloques.</p>
  </div>
  <?php if ($canManage): ?>
  <div>
    <a href="<?= url('materias', 'nuevo', $selectedPrograma ? ['id_programa' => $selectedPrograma] : []) ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
      <i class="bi bi-plus-lg"></i>
      <span>Nueva Materia</span>
    </a>
  </div>
  <?php endif; ?>
</div>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
      <input type="hidden" name="module" value="materias">
      <input type="hidden" name="action" value="index">
      
      <div class="col-12 col-md-6">
        <div class="input-group input-group-sm">
          <label class="input-group-text bg-light text-secondary" for="filter_prog">
            <i class="bi bi-mortarboard me-1"></i> Filtrar por Programa
          </label>
          <select name="id_programa" id="filter_prog" class="form-select" onchange="this.form.submit()">
            <option value="">Todos los programas curriculares</option>
            <?php foreach ($programas as $p): ?>
              <option value="<?= $p['id_programa'] ?>" <?= ($selectedPrograma == $p['id_programa']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['codigo'] . ' (v' . $p['version'] . ') — ' . $p['nombre'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="col-12 col-md-6 text-md-end">
        <?php if ($selectedPrograma): ?>
          <a href="<?= url('materias') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Mostrar todas las materias
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Table Card -->
<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm datatable align-middle mb-0">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre de la Materia</th>
            <th>Programa Académico</th>
            <th>Bloque / Periodo</th>
            <th>Tipo</th>
            <th>Intensidad</th>
            <th>Prerrequisito</th>
            <?php if ($canManage): ?><th class="text-end">Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($materias)): ?>
            <?php foreach ($materias as $m): ?>
              <tr>
                <td>
                  <span class="badge bg-light text-dark font-monospace border">
                    <?= htmlspecialchars($m['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td class="fw-semibold text-dark">
                  <?= htmlspecialchars($m['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                  <div class="small text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($m['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($m['programa_nombre'] ?? 'Programa', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <span class="badge bg-secondary-subtle text-secondary smaller font-monospace">
                    <?= htmlspecialchars($m['programa_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-secondary border">
                    Bloque <?= (int)($m['periodo_bloque'] ?? 1) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= ($m['tipo'] ?? 'OBLIGATORIA') === 'OBLIGATORIA' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' ?>">
                    <?= htmlspecialchars($m['tipo'] ?? 'OBLIGATORIA', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="small text-muted">
                    <?= !empty($m['intensidad']) ? number_format((float)$m['intensidad'], 1) . ' hrs' : '—' ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($m['prerrequisito_nombre'])): ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border" title="Requiere aprobar esta materia primero">
                      <i class="bi bi-lock me-1"></i> <?= htmlspecialchars($m['prerrequisito_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($m['prerrequisito_nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted smaller">Sin prerrequisito</span>
                  <?php endif; ?>
                </td>
                <?php if ($canManage): ?>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= url('materias', 'editar', ['id' => $m['id_materia']]) ?>" class="btn btn-outline-primary" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if (($currentUser['rol'] ?? '') === 'ADMIN'): ?>
                    <a href="<?= url('materias', 'eliminar', ['id' => $m['id_materia']]) ?>" 
                       class="btn btn-outline-danger" 
                       title="Eliminar" 
                       data-confirm="¿Desea eliminar la materia <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>?">
                      <i class="bi bi-trash"></i>
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
