<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 — Acceso Denegado | SGI-ABS</title>
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #090e17 0%, #0f172a 50%, #1e1b4b 100%);
      color: #f8fafc;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 1.5rem;
    }
    .error-card {
      max-width: 520px;
      width: 100%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(16px);
      border-radius: 20px;
      padding: 3rem 2rem;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .error-code {
      font-size: 6.5rem;
      font-weight: 800;
      letter-spacing: -0.05em;
      line-height: 1;
      background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.5rem;
    }
    .icon-badge {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<div class="error-card">
  <div class="icon-badge">
    <i class="bi bi-shield-slash-fill"></i>
  </div>
  <div class="error-code">403</div>
  <h3 class="fw-bold mb-2">Acceso No Autorizado</h3>
  <p class="text-white-50 small mb-4">
    No cuenta con los privilegios o rol institucional requeridos para ingresar a este recurso del sistema SGI-ABS. Si considera que esto es un error, contacte a la Coordinación Académica.
  </p>
  <div class="d-flex justify-content-center gap-3">
    <a href="index.php?module=dashboard" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2">
      <i class="bi bi-house-door-fill"></i>
      <span>Volver al Inicio</span>
    </a>
  </div>
  <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-white-50 smaller">
    SGI-ABS &bull; Institución Educativa ABS &bull; Seguridad Curricular
  </div>
</div>

</body>
</html>
