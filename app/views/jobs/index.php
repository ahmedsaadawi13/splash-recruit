<?php
// FILE: /app/views/jobs/index.php
require_once __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
    <h1>Jobs</h1>
    <a href="/jobs/create" class="btn btn-primary">Create Job</a>
</div>

<div class="filters">
    <form method="GET" action="/jobs" class="filter-form">
        <input type="text" name="search" placeholder="Search jobs..." value="<?= View::e($filters['search'] ?? '') ?>">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Reference</th>
            <th>Department</th>
            <th>Location</th>
            <th>Status</th>
            <th>Applications</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($jobs as $job): ?>
        <tr>
            <td><a href="/jobs/<?= $job['id'] ?>"><?= View::e($job['title']) ?></a></td>
            <td><?= View::e($job['reference_code']) ?></td>
            <td><?= View::e($job['department']) ?></td>
            <td><?= View::e($job['location_city']) ?></td>
            <td><span class="badge badge-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
            <td><?= $job['applications_count'] ?></td>
            <td>
                <a href="/jobs/<?= $job['id'] ?>/edit" class="btn btn-sm">Edit</a>
                <a href="/jobs/<?= $job['id'] ?>" class="btn btn-sm">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $paginator->render('/jobs') ?>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
