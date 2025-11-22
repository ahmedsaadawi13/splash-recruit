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
        <div class="section" style="text-align: center; padding: 4rem 2rem;">
            <h1>Thank You!</h1>
            <p style="font-size: 1.25rem; margin: 1.5rem 0;">Your application has been submitted successfully.</p>
            <p>We will review your application and get back to you soon.</p>
            <a href="/careers/<?= View::e($tenant['slug']) ?>" class="btn btn-primary" style="margin-top: 2rem;">View More Jobs</a>
        </div>
    </div>
</body>
</html>
