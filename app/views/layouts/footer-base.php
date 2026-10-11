<?php
$footerRole = $role ?? 'guest';

$workspaceLinks = match ($footerRole) {
    'student' => [
        ['Dashboard', 'student-dashboard.php'],
        ['Find Internships', 'opportunities.php'],
        ['My Applications', 'my-applications.php'],
        ['My Profile & CV', 'student-profile.php'],
    ],
    'company' => [
        ['Dashboard', 'company-dashboard.php'],
        ['My Internships', 'company-internships.php'],
        ['Applicants', 'company-applications.php'],
        ['Company Profile', 'company-profile.php'],
    ],
    'admin' => [
        ['Dashboard', 'admin-dashboard.php'],
        ['Manage Users', 'admin-users.php'],
        ['Company Verification', 'admin-verifications.php'],
        ['Platform Insights', 'admin-insights.php'],
    ],
    default => [
        ['Find Internships', 'opportunities.php'],
        ['Companies', 'companies.php'],
        ['Create Account', 'register.php'],
        ['Login', 'login.php'],
    ],
};

$footerColumns = [
    $footerRole === 'guest' ? 'Get Started' : 'Your Workspace'
        => $workspaceLinks,
    'Discover' => [
        ['Internships', 'opportunities.php'],
        ['Companies', 'companies.php'],
        ['Career Resources', 'career-articles.php'],
    ],
    'Support' => [
        ['Help & Guidance', 'help.php'],
        ['Home', 'index.php'],
    ],
];


?>

<footer class="dashboard-footer im-site-footer">
    <div class="container">
        <div class="im-footer-main">
            <div class="im-footer-brand">
                <a class="im-footer-logo" href="<?= e(url('index.php')) ?>">
                    <span class="im-footer-logo-icon"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></span>
                    <span>Intern<span>Match</span></span>
                </a>
                <p>Helping students discover meaningful experience and helping companies meet emerging talent.</p>
                <div class="im-footer-trust"><i class="bi bi-shield-check" aria-hidden="true"></i> A clearer path to your next opportunity</div>
            </div>

            <div class="im-footer-columns">
                <?php foreach ($footerColumns as $heading => $links): ?>
                    <div class="im-footer-column">
                        <h2><?= e($heading) ?></h2>
                        <nav aria-label="<?= e($heading) ?> footer links">
                            <?php foreach ($links as [$label, $path]): ?>
                                <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="im-footer-bottom">
            <p class="mb-0">© <?= date('Y') ?> InternMatch. Building brighter career paths.</p>
            <nav class="im-footer-legal" aria-label="Legal links">
                <a href="<?= e(url('help.php')) ?>">Help</a>
                <a href="<?= e(url('help.php')) ?>">Privacy</a>
                <a href="<?= e(url('help.php')) ?>">Terms</a>
            </nav>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/script.js')) ?>"></script>

</body>
</html>
