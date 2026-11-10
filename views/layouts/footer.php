<?php if (isset($currentUser) && $currentUser): ?>
      </div><!-- /container-fluid -->
    </main><!-- /main -->

    <!-- System Footer -->
    <footer class="app-footer bg-white border-top py-2 px-4 text-center text-muted small mt-auto">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
        <span>&copy; <?= date('Y') ?> <strong>SGI-ABS</strong> &bull; Institución Educativa ABS</span>
        <span class="text-secondary">Control Curricular y Etapa Práctica v1.0</span>
      </div>
    </footer>
  </div><!-- /#page-content-wrapper -->
</div><!-- /#wrapper -->

<!-- Global Confirmation Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="confirmActionModalLabel">
          <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Confirmar Acción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body py-3" id="confirmActionModalBody">
        ¿Está seguro de que desea realizar esta acción? Esta operación no se puede deshacer.
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="confirmActionBtn" class="btn btn-danger">Confirmar</a>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
  </div><!-- /.container auth wrapper -->
<?php endif; ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables 1.13.7 JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Application JS -->
<script src="assets/js/app.js"></script>
</body>
</html>
