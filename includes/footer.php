<!-- Main Footer -->
<footer class="main-footer text-center">
    <div class="float-right d-none d-sm-inline">
        Version 1.0.0
    </div>
    <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#">Online Routing System</a>.</strong> All rights reserved.
</footer>

<style>
.main-footer {
    background-color: #fff;
    border-top: 1px solid #dee2e6;
    color: #869099;
    padding: 1rem;
    margin-left: 250px;
    position: relative;
    bottom: 0;
    width: calc(100% - 250px);
}

.main-footer a {
    color: #007bff;
    text-decoration: none;
}

.main-footer a:hover {
    color: #0056b3;
    text-decoration: underline;
}

@media (max-width: 991.98px) {
    .main-footer {
        margin-left: 0;
        width: 100%;
    }
}
</style>

<!-- Custom Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" role="dialog" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutConfirmModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

</div>
<!-- ./wrapper -->