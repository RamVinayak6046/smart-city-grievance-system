        </main><!-- /.dash-content -->
    </div><!-- /.dash-main -->
</div><!-- /.dash-wrapper -->

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (available globally) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- App JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script src="<?= APP_URL ?>/assets/js/dashboard.js"></script>
<?= $extraJs ?? '' ?>
</body>
</html>
