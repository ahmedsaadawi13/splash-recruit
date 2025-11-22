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
        <header style="text-align: center; padding: 3rem 0;">
            <h1><?= View::e($tenant['name']) ?></h1>
            <p>Join our team and make a difference</p>
        </header>

        <div class="section">
            <h2>Open Positions</h2>

            <?php if (empty($jobs)): ?>
                <p>No open positions at this time. Please check back later!</p>
            <?php else: ?>
                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($jobs as $job): ?>
                        <div style="border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 8px;">
                            <h3><a href="/careers/<?= View::e($tenant['slug']) ?>/jobs/<?= View::e($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
                            <p style="color: var(--text-secondary); margin: 0.5rem 0;">
                                <?= View::e($job['department']) ?> •
                                <?= View::e($job['location_city']) ?> •
                                <?= ucfirst(str_replace('_', ' ', $job['employment_type'])) ?>
                            </p>
                            <p><?= substr(strip_tags($job['description']), 0, 200) ?>...</p>
                            <a href="/careers/<?= View::e($tenant['slug']) ?>/jobs/<?= View::e($job['slug']) ?>" class="btn btn-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
