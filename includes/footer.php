<?php
// No leading or trailing whitespace outside PHP tags to prevent header issues
?>

</div> <!-- Close row -->
</div> <!-- Close container-fluid -->

<footer class="bg-light text-center text-muted py-3 mt-auto">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> Interior Project Management. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js (if used everywhere) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Your custom JS -->
<script src="/project-management/assets/js/custom.js"></script>

<!-- Optional role-based JS -->
<?php if ($current_user['role'] === 'admin'): ?>
<script src="/project-management/assets/js/admin.js"></script>
<?php elseif ($current_user['role'] === 'manager'): ?>
<script src="/project-management/assets/js/manager.js"></script>
<?php elseif ($current_user['role'] === 'designer'): ?>
<script src="/project-management/assets/js/designer.js"></script>
<?php endif; ?>

</body>

</html>