<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <a href="/careers/<?= View::e($tenant['slug']) ?>" style="display: inline-block; margin-bottom: 1rem;">&larr; Back to Careers</a>

        <div class="section">
            <h1><?= View::e($job['title']) ?></h1>
            <p style="color: var(--text-secondary); margin: 1rem 0;">
                <?= View::e($job['department']) ?> •
                <?= View::e($job['location_city']) ?>, <?= View::e($job['location_country']) ?> •
                <?= ucfirst(str_replace('_', ' ', $job['employment_type'])) ?>
            </p>

            <div style="margin: 2rem 0;">
                <h2>Job Description</h2>
                <?= $job['description'] ?>
            </div>

            <?php if ($job['requirements']): ?>
            <div style="margin: 2rem 0;">
                <h2>Requirements</h2>
                <?= $job['requirements'] ?>
            </div>
            <?php endif; ?>

            <?php if ($job['benefits']): ?>
            <div style="margin: 2rem 0;">
                <h2>Benefits</h2>
                <?= $job['benefits'] ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="section" style="margin-top: 2rem;">
            <h2>Apply for this Position</h2>

            <?php $session = Session::getInstance(); ?>
            <?php if ($session->hasFlash('error')): ?>
                <div class="alert alert-error"><?= View::e($session->getFlash('error')) ?></div>
            <?php endif; ?>

            <form method="POST" action="/careers/<?= View::e($tenant['slug']) ?>/jobs/<?= View::e($job['slug']) ?>/apply" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city">
                </div>

                <div class="form-group">
                    <label for="cv">Resume/CV (PDF, DOC, DOCX)</label>
                    <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
