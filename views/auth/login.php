<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — SGI-ABS</title>
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Custom App CSS -->
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-dark">

<div class="auth-wrapper">
  <div class="auth-card p-4 p-sm-5">
    
    <!-- Header with Branding -->
    <div class="text-center mb-4">
      <div class="auth-header-icon">
        <i class="bi bi-mortarboard-fill fs-2"></i>
      </div>
      <h3 class="fw-bold text-dark mb-1 tracking-tight">SGI-ABS</h3>
      <p class="text-muted small mb-0">Sistema de Gestión Académica &bull; Institución ABS</p>
    </div>

    <!-- Flash message -->
    <?php if (!empty($flash) && is_array($flash)): ?>
      <?php foreach ($flash as $msg): ?>
      <div class="alert alert-<?= $msg['type'] === 'error' ? 'danger' : htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi bi-exclamation-circle-fill me-2 fs-6"></i>
          <div><?= htmlspecialchars($msg['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="index.php?module=auth&action=login" method="POST" id="loginForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <!-- Email Field -->
      <div class="mb-3">
        <label for="correo" class="form-label text-secondary small fw-semibold">Correo Institucional</label>
        <div class="input-group">
          <span class="input-group-text bg-light text-muted border-end-0">
            <i class="bi bi-envelope"></i>
          </span>
          <input 
            type="email" 
            class="form-control border-start-0 ps-0" 
            id="correo" 
            name="correo" 
            placeholder="ejemplo@abs.edu.co" 
            value="<?= htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
            required 
            autocomplete="email"
            autofocus
          >
        </div>
      </div>

      <!-- Password Field -->
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label for="password" class="form-label text-secondary small fw-semibold mb-1">Contraseña</label>
          <a href="#" class="smaller text-primary text-decoration-none" onclick="alert('Comuníquese con Registro Académico o la Coordinación Institucional para restablecer su clave.'); return false;">
            ¿Olvidó su contraseña?
          </a>
        </div>
        <div class="input-group">
          <span class="input-group-text bg-light text-muted border-end-0">
            <i class="bi bi-lock"></i>
          </span>
          <input 
            type="password" 
            class="form-control border-start-0 border-end-0 ps-0" 
            id="password" 
            name="password" 
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" 
            required 
            autocomplete="current-password"
          >
          <button class="btn btn-outline-secondary border-start-0 btn-toggle-password" type="button" data-target="password" title="Ver/Ocultar contraseña">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <!-- Remember me -->
      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
        <label class="form-check-label text-muted smaller" for="remember">
          Recordar este equipo por 30 días
        </label>
      </div>

      <!-- Submit button -->
      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm" id="btnLogin">
        <span>Ingresar al Sistema</span>
        <i class="bi bi-arrow-right"></i>
      </button>
    </form>

    <!-- Footer of the card -->
    <div class="text-center mt-4 pt-3 border-top">
      <p class="smaller text-muted mb-0">
        &copy; <?= date('Y') ?> Institución Educativa ABS &bull; Todos los derechos reservados.
      </p>
    </div>

  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPass = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPass ? 'text' : 'password');
        const icon = this.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye', !isPass);
          icon.classList.toggle('bi-eye-slash', isPass);
        }
      }
    });
  });

  // Submit button spinner effect
  const loginForm = document.getElementById('loginForm');
  const btnLogin = document.getElementById('btnLogin');
  if (loginForm && btnLogin) {
    loginForm.addEventListener('submit', function() {
      if (loginForm.checkValidity()) {
        btnLogin.disabled = true;
        btnLogin.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Ingresando...';
      }
    });
  }
</script>

</body>
</html>
