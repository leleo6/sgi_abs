<?php
include BASE_PATH . '/views/layouts/header.php';

$procesos = $procesos ?? [];
$selectedProcesoId = (int)($evidencia['id_proceso'] ?? ($_GET['id_proceso'] ?? 0));
?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-8">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="fw-bold tracking-tight text-dark mb-1">
          <i class="bi bi-cloud-arrow-up text-primary me-2"></i> Cargar Evidencia o Bitácora
        </h3>
        <p class="text-muted small mb-0">Suba sus informes de seguimiento, bitácoras periódicas o documentos institucionales.</p>
      </div>
      <a href="<?= url('evidencias') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Evidencias
      </a>
    </div>

    <form method="POST" action="<?= url('evidencias', 'guardar') ?>" enctype="multipart/form-data" id="subirEvidenciaForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">

          <div class="form-section-title">
            <i class="bi bi-folder-plus text-primary"></i> 1. Proceso de Grado y Tipo de Documento
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12">
              <label for="id_proceso" class="form-label">Proceso de Grado / Estudiante <span class="text-danger">*</span></label>
              <select class="form-select" id="id_proceso" name="id_proceso" required>
                <option value="">Seleccione el proceso correspondiente...</option>
                <?php foreach ($procesos as $p): ?>
                  <option value="<?= $p['id_proceso'] ?>" <?= ($selectedProcesoId == $p['id_proceso']) ? 'selected' : '' ?>>
                    Expediente #<?= $p['id_proceso'] ?> &bull; <?= htmlspecialchars(($p['estudiante_nombres'] ?? '') . ' ' . ($p['estudiante_apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($p['modalidad'] ?? 'PRACTICA', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($p['entidad_razon_social'] ?? ($p['titulo_proyecto'] ?? 'Institucional'), ENT_QUOTES, 'UTF-8') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-8">
              <label for="tipo_evidencia" class="form-label">Tipo de Evidencia / Entregable <span class="text-danger">*</span></label>
              <select class="form-select" id="tipo_evidencia" name="tipo_evidencia" required>
                <option value="">Seleccione el tipo de entregable...</option>
                <optgroup label="Bitácoras de Etapa Práctica (12 Entregas)">
                  <?php for ($i = 1; $i <= 12; $i++): ?>
                    <?php $bCode = 'BITACORA_' . str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $bCode ?>"><?= $bCode ?> — Informe Quincenal / Mensual #<?= $i ?></option>
                  <?php endfor; ?>
                </optgroup>
                <optgroup label="Documentación Requerida para Paz y Salvo">
                  <option value="CEDULA_CIUDADANIA">CEDULA_CIUDADANIA — Fotocopia al 150% del Documento de Identidad</option>
                  <option value="REGISTRO_APE">REGISTRO_APE — Constancia de Inscripción en la Agencia Pública de Empleo</option>
                  <option value="CARNET_INSTITUCIONAL">CARNET_INSTITUCIONAL — Paz y salvo o carnet institucional entregado</option>
                  <option value="EVALUACION_EFEP">EVALUACION_EFEP — Evaluación Final de la Etapa Práctica firmada por la empresa</option>
                </optgroup>
                <optgroup label="Otros Soportes y Proyectos">
                  <option value="PROPUESTA_PROYECTO">PROPUESTA_PROYECTO — Anteproyecto de Grado</option>
                  <option value="INFORME_FINAL">INFORME_FINAL — Monografía / Memoria Técnica Final</option>
                  <option value="CARTA_CUMPLIMIENTO">CARTA_CUMPLIMIENTO — Certificación laboral de horas cumplidas</option>
                  <option value="OTRO">OTRO — Soportes extraordinarios</option>
                </optgroup>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label for="version_entrega" class="form-label">Versión de Entrega <span class="text-danger">*</span></label>
              <input 
                type="number" 
                min="1" 
                max="20" 
                class="form-control" 
                id="version_entrega" 
                name="version_entrega" 
                value="1" 
                required
              >
              <div class="form-text">Si es corrección, aumente el número.</div>
            </div>
          </div>

          <div class="form-section-title">
            <i class="bi bi-file-earmark-arrow-up text-primary"></i> 2. Archivo Digital y Observaciones
          </div>

          <div class="row g-3 mb-2">
            <div class="col-12">
              <label for="archivo_evidencia" class="form-label">Archivo de Evidencia (PDF, ZIP, DOCX, JPG, PNG) <span class="text-danger">*</span></label>
              <input 
                type="file" 
                class="form-control" 
                id="archivo_evidencia" 
                name="archivo_evidencia" 
                accept=".pdf,.zip,.rar,.docx,.doc,.jpg,.jpeg,.png" 
                data-preview="filePreviewBox" 
                data-max-size="10" 
                required
              >
              <div class="form-text">Tamaño máximo admitido: 10 MB. El archivo se almacenará de manera segura.</div>
            </div>

            <!-- Dynamic File Preview Container -->
            <div class="col-12">
              <div id="filePreviewBox"></div>
            </div>

            <div class="col-12">
              <label for="observaciones" class="form-label">Observaciones o Comentarios del Estudiante</label>
              <textarea 
                class="form-control" 
                id="observaciones" 
                name="observaciones" 
                rows="3" 
                placeholder="Detalle brevemente las actividades contenidas en esta entrega..."
              ></textarea>
            </div>
          </div>

        </div>

        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
          <a href="<?= url('evidencias') ?>" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <span>Subir y Registrar Evidencia</span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
