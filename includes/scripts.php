<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.3.0/dist/js/adminlte.min.js"></script>
<!-- Custom scripts -->
<script src="../assets/js/scripts.js"></script>
<!-- AJAX handling -->
<script src="../assets/js/ajax.js"></script>

<script>
$(document).ready(function() {
    // Initialize tooltips
    // Bootstrap 5 tooltips initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Initialize popovers
    // Bootstrap 5 popovers initialization
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
    
    // Handle modal events using Bootstrap 5 syntax
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) form.reset();
        });
    });

    // Handle table responsive
    document.querySelectorAll('.table-responsive').forEach(function(table) {
        table.addEventListener('show.bs.dropdown', function () {
            this.style.overflow = 'inherit';
        });
        table.addEventListener('hide.bs.dropdown', function () {
            this.style.overflow = 'auto';
        });
    });
});
</script>