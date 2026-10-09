<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';

require_once __DIR__ . '/../app/controllers/CompanyFileController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('company');
$userId = (int) $user['user_id'];

$profileError = null;
$profileSuccess = take_flash('company_profile_success');


$fileSuccess = take_flash('company_file_success');
$fileError = take_flash('company_file_error');



try {
    $company = CompanyProfileController::load($userId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post();
        verify_csrf();

        try {
            CompanyProfileController::save($userId, $_POST);

            flash(
                'company_profile_success',
                'Your company profile has been saved.'
            );

            redirect('company-profile.php');
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $profileError = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log((string) $exception);
            http_response_code(500);

            $profileError =
                'Your changes could not be saved. Please try again.';
        }

        $editable = [
            'company_name',
            'description',
            'industry',
            'location',
            'phone',
            'contact_email',
            'website',
        ];

        foreach ($editable as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field])) {
                $company[$field] = $_POST[$field];
            }
        }
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('The company profile could not be loaded.');
}

$verificationStatus = $company['verification_status'];

$verificationLabel =
    CompanyProfileController::verificationLabel($verificationStatus);

$verificationClass =
    CompanyProfileController::verificationClass($verificationStatus);




$hasLogo = CompanyFileController::path($company, 'logo') !== null;

$hasVerificationDocument =
    CompanyFileController::path($company, 'verification') !== null;
?>









<?php render_header($user, 'Company Profile', 'profile'); ?>


<main class="profile-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">COMPANY PROFILE</p>
                <h1>Company Profile</h1>
                <p>
                    Help students understand your company and its opportunities.
                </p>
            </div>

            <button
                type="submit"
                form="companyProfileForm"
                class="btn btn-primary profile-save-top">
                <i class="bi bi-check2-circle me-2"></i>
                Save Changes
            </button>
        </div>

        <?php if ($profileError !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($profileError) ?>
            </div>
        <?php endif; ?>

        <?php if ($profileSuccess !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($profileSuccess) ?>
            </div>
        <?php endif; ?>






        <form
            id="companyProfileForm"
            method="post"
            action="<?= e(url('company-profile.php')) ?>">

            <?= csrf_field() ?>

            <section class="profile-section">

                <div class="profile-section-header">
                    <div>
                        <h3>Company Information</h3>
                        <p>Introduce your organization to students.</p>
                    </div>

                    <div class="profile-section-icon blue">
                        <i class="bi bi-building"></i>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="profile-label" for="companyName">
                            Company Name
                        </label>

                        <input
                            type="text"
                            id="companyName"
                            name="company_name"
                            class="form-control profile-input"
                            value="<?= e($company['company_name']) ?>"
                            maxlength="150"
                            autocomplete="organization"
                            required>

                        <small class="profile-help-text">
                            A name change requires a new review
                            if your company is already verified.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="companyIndustry">
                            Industry
                        </label>

                        <input
                            type="text"
                            id="companyIndustry"
                            name="industry"
                            class="form-control profile-input"
                            value="<?= e($company['industry'] ?? '') ?>"
                            placeholder="e.g. Technology & Software Development"
                            maxlength="100">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="companyLocation">
                            Location
                        </label>

                        <input
                            type="text"
                            id="companyLocation"
                            name="location"
                            class="form-control profile-input"
                            value="<?= e($company['location'] ?? '') ?>"
                            placeholder="e.g. Yangon, Myanmar"
                            maxlength="150">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="companyWebsite">
                            Website
                        </label>

                        <input
                            type="url"
                            id="companyWebsite"
                            name="website"
                            class="form-control profile-input"
                            value="<?= e($company['website'] ?? '') ?>"
                            placeholder="https://example.com"
                            maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="profile-label" for="companyDescription">
                            About the Company
                        </label>

                        <textarea
                            id="companyDescription"
                            name="description"
                            class="form-control profile-input profile-textarea"
                            rows="6"
                            maxlength="5000"
                            placeholder="Describe your company, its work, and what interns can learn."><?= e($company['description'] ?? '') ?></textarea>
                    </div>

                </div>
            </section>

            <section class="profile-section">

                <div class="profile-section-header">
                    <div>
                        <h3>Contact Information</h3>
                        <p>Provide contact details for your company.</p>
                    </div>

                    <div class="profile-section-icon green">
                        <i class="bi bi-telephone"></i>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="profile-label" for="companyPhone">
                            Contact Phone
                        </label>

                        <input
                            type="tel"
                            id="companyPhone"
                            name="phone"
                            class="form-control profile-input"
                            value="<?= e($company['phone'] ?? '') ?>"
                            placeholder="+95 9 xxx xxx xxx"
                            maxlength="30"
                            autocomplete="tel">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="companyContactEmail">
                            Contact Email
                        </label>

                        <input
                            type="email"
                            id="companyContactEmail"
                            name="contact_email"
                            class="form-control profile-input"
                            value="<?= e($company['contact_email'] ?? '') ?>"
                            placeholder="careers@example.com"
                            maxlength="150">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="companyLoginEmail">
                            Login Email
                        </label>

                        <input
                            type="email"
                            id="companyLoginEmail"
                            class="form-control profile-input"
                            value="<?= e($company['login_email']) ?>"
                            readonly>

                        <small class="profile-help-text">
                            Changing your contact email does not
                            change the email used to sign in.
                        </small>
                    </div>

                </div>
            </section>

        </form>


        <section class="profile-section" id="company-files">

        <div class="profile-section-header">
            <div>
                <h3>Company Logo</h3>
                <p>Add a recognizable logo for your company.</p>
            </div>

            <div class="profile-section-icon blue">
                <i class="bi bi-image"></i>
            </div>
        </div>

        <?php if ($fileError !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($fileError) ?>
            </div>
        <?php endif; ?>

        <?php if ($fileSuccess !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($fileSuccess) ?>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="company-large-logo">
                <?php if ($hasLogo): ?>
                    <img
                        src="<?= e(url('company-file.php?kind=logo')) ?>"
                        alt="<?= e($company['company_name']) ?> logo"
                        class="company-logo-image"
                        width="64"
                        height="64">
                <?php else: ?>
                    <?= e(mb_strtoupper(
                        mb_substr($company['company_name'], 0, 1, 'UTF-8'),
                        'UTF-8'
                    )) ?>
                <?php endif; ?>
            </div>

            <div>
                <strong><?= e($company['company_name']) ?></strong>
                <p class="mb-0 text-muted">
                    PNG or JPEG, up to 2 MB and 2048 × 2048 pixels.
                </p>
            </div>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
            action="<?= e(url('company-file-upload.php')) ?>">

            <?= csrf_field() ?>

            <input type="hidden" name="kind" value="logo">

            <label class="profile-label" for="companyLogo">
                Choose Logo
            </label>

            <input
                type="file"
                id="companyLogo"
                name="company_file"
                class="form-control profile-input"
                accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                required>

            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-upload me-2"></i>
                <?= $hasLogo ? 'Replace Logo' : 'Upload Logo' ?>
            </button>
        </form>

    </section>






            <section class="profile-section">

                <div class="profile-section-header">
                    <div>
                        <h3>Company Verification</h3>
                        <p>Your company's current review status.</p>
                    </div>

                    <div class="profile-section-icon purple">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <span class="badge rounded-pill <?= e($verificationClass) ?>">
                    <?= e($verificationLabel) ?>
                </span>

                <p class="mt-3 mb-0">
                    <?php if ($verificationStatus === 'verified'): ?>
                        Your company has been verified.
                    <?php elseif ($verificationStatus === 'rejected'): ?>
                        Review the feedback below before submitting
                        updated verification documents.
                    <?php else: ?>
                        Your company has not yet been approved.
                    <?php endif; ?>
                </p>

                <?php if (!empty($company['verification_notes'])): ?>
                    <div class="alert alert-secondary mt-3 mb-0">
                        <strong>Review feedback</strong>
                        <div class="mt-1">
                            <?= nl2br(e($company['verification_notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>




                

                <hr class="my-4">

                <h5>Verification Document</h5>

                <p class="text-muted">
                    Upload a company registration document or other supporting
                    business document as a PDF. Maximum size: 5 MB.
                </p>

                <?php if ($hasVerificationDocument): ?>
                    <p>
                        <a
                            href="<?= e(url('company-file.php?kind=verification')) ?>"
                            class="btn btn-outline-primary">
                            <i class="bi bi-download me-2"></i>
                            Download Current Document
                        </a>
                    </p>
                <?php elseif (!empty($company['verification_doc_path'])): ?>
                    <p class="text-warning-emphasis">
                        Your previous document is unavailable in the new storage.
                        Please upload it again.
                    </p>
                <?php else: ?>
                    <p class="text-muted">No verification document uploaded.</p>
                <?php endif; ?>

                <form
                    method="post"
                    enctype="multipart/form-data"
                    action="<?= e(url('company-file-upload.php')) ?>">

                    <?= csrf_field() ?>

                    <input type="hidden" name="kind" value="verification">

                    <label class="profile-label" for="verificationDocument">
                        Choose Verification PDF
                    </label>

                    <input
                        type="file"
                        id="verificationDocument"
                        name="company_file"
                        class="form-control profile-input"
                        accept=".pdf,application/pdf"
                        required>

                    <small class="profile-help-text">
                        Uploading a replacement returns your company to pending review.
                    </small>

                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-upload me-2"></i>
                        <?= $hasVerificationDocument
                            ? 'Replace Document'
                            : 'Upload Document' ?>
                    </button>
                </form>
            
            </section>

            <div class="profile-bottom-actions">
                <a
                    href="<?= e(url('company-dashboard.php')) ?>"
                    class="btn btn-outline-secondary">
                    Back to Dashboard
                </a>

                <button
                    type="submit"
                    form="companyProfileForm"
                    class="btn btn-primary">
                    <i class="bi bi-check2-circle me-2"></i>
                    Save Changes
                </button>
            </div>

        </div>
    </main>
<?php render_footer($user); ?>