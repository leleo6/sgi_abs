<?php
include BASE_PATH . '/views/layouts/header.php';

$programas = $programas ?? [];
$estudiantesReporte = $estudiantesReporte ?? [];
$filterPrograma = $_GET['id_programa'] ?? '';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h3 class="fw-bold tracking-tight text-dark mb-1">
      <i class="bi bi-bar-chart text-primary me-2"></i> Reportes e Indicadores de Gestión
    </h3>
    <p class="text-muted small mb-0">Generación de informes consolidados, estados de elegibilidad y exportación de datos.</p>
  </div>
</div>

<!-- 3 FEATURED REPORTS CARDS -->
<div class="row g-3 mb-4">
  <!-- Card 1: Estado Académico -->
  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm border-0 card-hover">
      <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon bg-success-subtle text-success me-3" style="width: 48px; height: 48px;">
            <i class="bi bi-person-check-fill fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">Elegibilidad y Deuda</h6>
            <span class="smaller text-muted">Control Curricular</span>
          </div>
        </div>
        <p class="small text-muted mb-3">
          Listado de estudiantes clasificados en <strong>LIMPIO</strong> o <strong>CON_DEUDA_ACADEMICA</strong> para determinar aptitud de práctica.
        </p>
        <div class="d-flex gap-2">
          <a href="#tabla-elegibilidad" class="btn btn-outline-success btn-sm w-100">
            <i class="bi bi-eye me-1"></i> Ver en Pantalla
          </a>
          <a href="<?= url('reportes', 'exportar_elegibilidad', ['id_programa' => $filterPrograma]) ?>" class="btn btn-success btn-sm" title="Exportar CSV">
            <i class="bi bi-file-earmark-spreadsheet"></i> CSV
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Seguimiento de Prácticas -->
  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm border-0 card-hover">
      <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon bg-primary-subtle text-primary me-3" style="width: 48px; height: 48px;">
            <i class="bi bi-briefcase-fill fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">Etapa Práctica y Horas</h6>
            <span class="smaller text-muted">Seguimiento Empresarial</span>
          </div>
        </div>
        <p class="small text-muted mb-3">
          Trazabilidad de empresas receptoras, convenios, horas acumuladas y emisión de paz y salvo de graduación.
        </p>
        <div class="d-flex gap-2">
          <a href="<?= url('procesos') ?>" class="btn btn-outline-primary btn-sm w-100">
            <i class="bi bi-eye me-1"></i> Ver Procesos
          </a>
          <a href="<?= url('reportes', 'exportar_procesos', ['id_programa' => $filterPrograma]) ?>" class="btn btn-primary btn-sm" title="Exportar CSV">
            <i class="bi bi-file-earmark-spreadsheet"></i> CSV
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 3: Rendimiento por Materia -->
  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm border-0 card-hover">
      <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon bg-purple-subtle text-purple me-3" style="width: 48px; height: 48px; background: rgba(124,58,237,.12); color:#7c3aed;">
            <i class="bi bi-journal-bookmark-fill fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">Consolidado de Notas</h6>
            <span class="smaller text-muted">Evaluaciones e Intentos</span>
          </div>
        </div>
        <p class="small text-muted mb-3">
          Distribución de notas ordinarias, habilitaciones y homologaciones por asignatura y periodo académico.
        </p>
        <div class="d-flex gap-2">
          <a href="<?= url('resultados') ?>" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-eye me-1"></i> Ver Calificaciones
          </a>
          <a href="<?= url('reportes', 'exportar_notas', ['id_programa' => $filterPrograma]) ?>" class="btn btn-purple btn-sm" title="Exportar CSV">
            <i class="bi bi-file-earmark-spreadsheet"></i> CSV
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Table: Academic Status / Elegibilidad -->
<div class="card shadow-sm border-0" id="tabla-elegibilidad">
  <div class="card-header bg-white py-3">
    <div class="row align-items-center g-2">
      <div class="col-12 col-md-6">
        <h6 class="mb-0 fw-bold text-dark">
          <i class="bi bi-table text-primary me-2"></i> Reporte Detallado de Estudiantes y Estado Académico
        </h6>
      </div>
      <div class="col-12 col-md-6 text-md-end">
        <a href="<?= url('reportes', 'exportar_csv', ['id_programa' => $filterPrograma]) ?>" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar este listado a CSV
        </a>
      </div>
    </div>
  </div>

  <!-- Filter by Program Header -->
  <div class="card-body p-3 bg-light border-bottom">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
      <input type="hidden" name="module" value="reportes">
      <input type="hidden" name="action" value="index">
      
      <div class="col-12 col-md-5">
        <div class="input-group input-group-sm">
          <label class="input-group-text bg-white text-secondary" for="filter_prog_rep">
            <i class="bi bi-mortarboard me-1"></i> Programa Académico
          </label>
          <select name="id_programa" id="filter_prog_rep" class="form-select bg-white" onchange="this.form.submit()">
            <option value="">Todos los programas formativos</option>
            <?php foreach ($programas as $pr): ?>
              <option value="<?= $pr['id_programa'] ?>" <?= ($filterPrograma == $pr['id_programa']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($pr['codigo'] . ' — ' . $pr['nombre'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="col-12 col-md-7 text-md-end">
        <?php if ($filterPrograma): ?>
          <a href="<?= url('reportes') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Limpiar Filtro
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-sm datatable align-middle mb-0">
      <thead>
        <tr>
          <th>Documento</th>
          <th>Nombre del Aprendiz</th>
          <th>Correo Electrónico</th>
          <th>Programa Académico</th>
          <th>Periodo Ingreso</th>
          <th>Materias Pendientes</th>
          <th>Habilitaciones en Curso</th>
          <th>Dictamen Curricular</th>
          <th class="text-end">Expediente</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($estudiantesReporte)): ?>
          <?php foreach ($estudiantesReporte as $est): ?>
            <tr>
              <td>
                <span class="smaller font-monospace text-muted">
                  <?= htmlspecialchars($est['tipo_documento'] ?? 'CC', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($est['documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td>
                <div class="fw-semibold text-dark">
                  <?= htmlspecialchars(($est['nombres'] ?? '') . ' ' . ($est['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
              </td>
              <td>
                <span class="smaller text-muted"><?= htmlspecialchars($est['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td>
                <span class="small text-dark"><?= htmlspecialchars($est['programa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td>
                <span class="badge bg-light text-dark font-monospace border"><?= htmlspecialchars($est['periodo_ingreso'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td>
                <?php $mPend = (int)($est['materias_pendientes'] ?? 0); ?>
                <span class="badge <?= ($mPend === 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold">
                  <?= $mPend ?>
                </span>
              </td>
              <td>
                <?php $hab = (int)($est['habilitaciones_en_curso'] ?? 0); ?>
                <span class="badge <?= ($hab === 0) ? 'bg-light text-muted border' : 'bg-warning text-dark' ?>">
                  <?= $hab ?>
                </span>
              </td>
              <td>
                <?= render_badge($est['estado_academico'] ?? 'LIMPIO') ?>
              </td>
              <td class="text-end">
                <a href="<?= url('matriculas', 'detalle', ['id' => $est['id_matricula'] ?? '']) ?>" class="btn btn-outline-primary btn-sm" title="Ver Detalle">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
