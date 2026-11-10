/**
 * SGI-ABS - Application Core JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // 1. Initialize Bootstrap Tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // 2. Initialize Bootstrap Popovers
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // 3. Mobile Sidebar Toggle
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.toggle('sidebar-open');
    });
  }

  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.remove('sidebar-open');
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('sidebar-open')) {
      const sidebar = document.getElementById('sidebar-wrapper');
      const toggle = document.getElementById('sidebarToggle');
      if (sidebar && !sidebar.contains(e.target) && (!toggle || !toggle.contains(e.target))) {
        document.body.classList.remove('sidebar-open');
      }
    }
  });

  // 4. Auto-dismiss alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-dismissible');
  autoAlerts.forEach(function (alertEl) {
    setTimeout(function () {
      try {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
        if (bsAlert) {
          bsAlert.close();
        }
      } catch (err) {
        // Fallback remove
        alertEl.remove();
      }
    }, 5000);
  });

  // 5. Global Confirmation Modal for destructive actions
  const confirmModalEl = document.getElementById('confirmActionModal');
  let confirmModal = null;
  if (confirmModalEl) {
    confirmModal = new bootstrap.Modal(confirmModalEl);
  }

  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-confirm]');
    if (trigger) {
      e.preventDefault();
      const message = trigger.getAttribute('data-confirm') || '¿Está seguro de que desea realizar esta acción?';
      const actionUrl = trigger.getAttribute('href') || trigger.getAttribute('data-url');
      const isFormSubmit = trigger.getAttribute('data-form-submit');

      const modalBody = document.getElementById('confirmActionModalBody');
      const confirmBtn = document.getElementById('confirmActionBtn');

      if (modalBody) modalBody.textContent = message;

      if (confirmBtn) {
        if (isFormSubmit) {
          confirmBtn.onclick = function () {
            const form = document.querySelector(isFormSubmit);
            if (form) form.submit();
            if (confirmModal) confirmModal.hide();
          };
        } else if (actionUrl) {
          confirmBtn.setAttribute('href', actionUrl);
          confirmBtn.onclick = null;
        }
      }

      if (confirmModal) confirmModal.show();
    }
  });

  // 6. DataTables Initialization with Spanish Language
  if (window.jQuery && $.fn.DataTable) {
    $('.datatable').each(function () {
      if (!$.fn.DataTable.isDataTable(this)) {
        $(this).DataTable({
          language: {
            decimal: '',
            emptyTable: 'No hay datos disponibles en la tabla',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            infoPostFix: '',
            thousands: ',',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron resultados coincidentes',
            paginate: {
              first: 'Primero',
              last: 'Último',
              next: 'Siguiente',
              previous: 'Anterior'
            },
            aria: {
              sortAscending: ': activar para ordenar columna ascendente',
              sortDescending: ': activar para ordenar columna descendente'
            }
          },
          pageLength: 10,
          responsive: true,
          order: [] // Preserves natural SQL order unless specified
        });
      }
    });
  }

  // 7. File Input Preview & Size Validator
  const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
  fileInputs.forEach(function (input) {
    input.addEventListener('change', function (e) {
      const file = this.files[0];
      const previewTargetId = this.getAttribute('data-preview');
      const previewBox = document.getElementById(previewTargetId);
      const maxSizeMB = parseFloat(this.getAttribute('data-max-size') || '10');

      if (file) {
        // Size validation
        const sizeMB = file.size / (1024 * 1024);
        if (sizeMB > maxSizeMB) {
          alert('El archivo supera el tamaño máximo permitido de ' + maxSizeMB + ' MB.');
          this.value = '';
          if (previewBox) previewBox.innerHTML = '<span class="text-muted small">Sin archivo seleccionado</span>';
          return;
        }

        if (previewBox) {
          let icon = 'bi-file-earmark-text';
          if (file.type.includes('pdf')) icon = 'bi-file-earmark-pdf text-danger';
          else if (file.type.includes('image')) icon = 'bi-file-earmark-image text-primary';
          else if (file.type.includes('zip') || file.type.includes('rar')) icon = 'bi-file-earmark-zip text-warning';

          previewBox.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border">
              <div class="d-flex align-items-center text-truncate">
                <i class="bi ${icon} fs-4 me-2"></i>
                <div class="text-truncate">
                  <div class="fw-medium text-truncate small">${file.name}</div>
                  <div class="smaller text-muted">${(file.size / 1024).toFixed(1)} KB &bull; ${file.type || 'Archivo'}</div>
                </div>
              </div>
              <span class="badge bg-success-subtle text-success ms-2">Listo</span>
            </div>
          `;
        }
      }
    });
  });

  // 8. Password Visibility Toggle
  document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = this.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye', !isPassword);
          icon.classList.toggle('bi-eye-slash', isPassword);
        }
      }
    });
  });

  // 9. Dynamic Role Form (Show/Hide Student Extra Fields in Usuario Form)
  const rolSelect = document.getElementById('rol_select');
  const estudianteFields = document.getElementById('estudiante_fields');
  if (rolSelect && estudianteFields) {
    const toggleEstudianteFields = function () {
      if (rolSelect.value === 'ESTUDIANTE') {
        estudianteFields.style.display = 'block';
        estudianteFields.querySelectorAll('input, select').forEach(function (el) {
          if (el.getAttribute('data-required-estudiante')) {
            el.required = true;
          }
        });
      } else {
        estudianteFields.style.display = 'none';
        estudianteFields.querySelectorAll('input, select').forEach(function (el) {
          el.required = false;
        });
      }
    };
    rolSelect.addEventListener('change', toggleEstudianteFields);
    toggleEstudianteFields(); // initialize state
  }

  // 10. Grade Live Status Calculator for Resultados Form
  const calificacionInput = document.getElementById('calificacion_input');
  const estadoBadgeLive = document.getElementById('estado_resultado_live');
  const notaMinimaRef = parseFloat(document.getElementById('nota_minima_ref')?.value || '3.0');

  if (calificacionInput && estadoBadgeLive) {
    const updateGradeStatus = function () {
      const val = parseFloat(calificacionInput.value);
      if (isNaN(val)) {
        estadoBadgeLive.className = 'badge bg-warning text-dark';
        estadoBadgeLive.textContent = 'EN CURSO';
        return;
      }

      if (val >= notaMinimaRef) {
        estadoBadgeLive.className = 'badge bg-success';
        estadoBadgeLive.textContent = 'APROBADO (' + val.toFixed(2) + ')';
      } else if (val >= 2.5) {
        estadoBadgeLive.className = 'badge bg-warning text-dark';
        estadoBadgeLive.textContent = 'HABILITACION PROGRAMADA (' + val.toFixed(2) + ')';
      } else {
        estadoBadgeLive.className = 'badge bg-danger';
        estadoBadgeLive.textContent = 'REPROBADO (' + val.toFixed(2) + ')';
      }
    };

    calificacionInput.addEventListener('input', updateGradeStatus);
  }
});
