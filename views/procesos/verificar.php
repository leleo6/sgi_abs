<?php
declare(strict_types=1);
// Vista pública de verificación de Paz y Salvo (sin autenticación requerida)
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificación de Paz y Salvo — SGI-ABS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); min-height: 100vh; display: flex; align-items: center; }
    .verify-card { background: rgba(255,255,255,.97); border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    .status-valid   { color: #10b981; }
    .status-invalid { color: #ef4444; }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
      <div class="verify-card p-5">
        <div class="text-center mb-4">
          <i class="bi bi-patch-check-fill fs-1 text-primary"></i>
          <h1 class="h3 mt-2 fw-bold">Verificación de Paz y Salvo</h1>
          <p class="text-muted small">Institución Educativa ABS — SGI-ABS</p>
        </div>

        <!-- Formulario de búsqueda -->
        <form method="GET" action="index.php" class="mb-4">
          <input type="hidden" name="module" value="procesos">
          <input type="hidden" name="action" value="verificar">
          <div class="input-group">
            <input type="text" name="codigo" class="form-control form-control-lg"
                   placeholder="Ingrese el código de verificación"
                   value="<?= htmlspecialchars($codigo ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   required maxlength="40">
            <button class="btn btn-primary px-4" type="submit">
              <i class="bi bi-search me-1"></i> Verificar
            </button>
          </div>
        </form>

        <?php if (!empty($codigo)): ?>
          <?php if ($proceso): ?>
            <!-- PAZ Y SALVO VÁLIDO -->
            <div class="alert alert-success border-0 rounded-3">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill fs-2 me-3 status-valid"></i>
                <div>
                  <h5 class="mb-0 fw-bold">Paz y Salvo VÁLIDO</h5>
                  <small class="text-muted">Código: <?= htmlspecialchars($proceso['codigo_verificacion'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>
              </div>
              <hr>
              <table class="table table-sm table-borderless mb-0">
                <tr><th class="text-muted fw-normal" width="40%">Estado:</th>
                    <td><span class="badge bg-<?= $proceso['paz_salvo_estado'] === 'EMITIDO' ? 'success' : 'danger' ?>">
                      <?= htmlspecialchars($proceso['paz_salvo_estado'], ENT_QUOTES, 'UTF-8') ?>
                    </span></td></tr>
                <tr><th class="text-muted fw-normal">Modalidad:</th>
                    <td><?= htmlspecialchars($proceso['modalidad'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th class="text-muted fw-normal">Emitido el:</th>
                    <td><?= $proceso['paz_salvo_emitido_en'] ? date('d/m/Y H:i', strtotime($proceso['paz_salvo_emitido_en'])) : 'N/A' ?></td></tr>
              </table>
              <?php if ($proceso['paz_salvo_estado'] === 'REVOCADO'): ?>
                <div class="alert alert-warning mt-2 mb-0 py-2">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  <strong>Advertencia:</strong> Este Paz y Salvo fue revocado.
                  <?php if (!empty($proceso['paz_salvo_motivo_revocatoria'])): ?>
                    Motivo: <?= htmlspecialchars($proceso['paz_salvo_motivo_revocatoria'], ENT_QUOTES, 'UTF-8') ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- CÓDIGO NO ENCONTRADO -->
            <div class="alert alert-danger border-0 rounded-3">
              <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill fs-2 me-3 status-invalid"></i>
                <div>
                  <h5 class="mb-0 fw-bold">Código no encontrado</h5>
                  <p class="mb-0 mt-1 small">El código <strong><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></strong>
                  no corresponde a ningún Paz y Salvo registrado en el sistema.</p>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-house me-1"></i> Ir al sistema
          </a>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
          <i class="bi bi-shield-lock me-1"></i>
          Sistema SGI-ABS · Institución Educativa ABS · <?= date('Y') ?>
        </p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
