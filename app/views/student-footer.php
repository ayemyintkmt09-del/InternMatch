<footer class="dashboard-footer">
    <div class="container">

        <div
            class="d-flex flex-wrap align-items-center justify-content-between gap-3">

            <p class="mb-0">
                © <?= date('Y') ?> InternMatch
                <span aria-hidden="true">•</span>
                Internship Opportunity & Student Matching System
            </p>

            <nav
                class="d-flex flex-wrap gap-3"
                aria-label="Footer navigation">

                <a href="<?= e(url('help.php')) ?>">
                    Help & Guidance
                </a>

                <a href="<?= e(url('index.php')) ?>">
                    Homepage
                </a>
            </nav>

        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('js/script.js')) ?>"></script>

</body>
</html>