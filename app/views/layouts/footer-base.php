<footer class="dashboard-footer border-top bg-white mt-5 py-4">
    <div
        class="container d-flex flex-wrap justify-content-between align-items-center gap-3">

        <p class="mb-0">
            © <?= date('Y') ?> InternMatch
        </p>

        <nav
            class="d-flex flex-wrap gap-3"
            aria-label="Footer navigation">

            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('opportunities.php')) ?>">Internships</a>
            <a href="<?= e(url('companies.php')) ?>">Companies</a>
            <a href="<?= e(url('help.php')) ?>">Help & Guidance</a>

        </nav>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/script.js')) ?>"></script>

</body>
</html>