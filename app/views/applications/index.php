<?php
// FILE: /app/views/applications/index.php
require_once __DIR__ . '/../shared/header.php';
?>

<h1>Applications</h1>

<div class="filters">
    <form method="GET" action="/applications" class="filter-form">
        <input type="text" name="search" placeholder="Search applications..." value="<?= View::e($filters['search'] ?? '') ?>">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="applied">Applied</option>
            <option value="in_review">In Review</option>
            <option value="interview_scheduled">Interview Scheduled</option>
            <option value="offered">Offered</option>
            <option value="hired">Hired</option>
            <option value="rejected">Rejected</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Candidate</th>
            <th>Job</th>
            <th>Stage</th>
            <th>Status</th>
            <th>Applied</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($applications as $app): ?>
        <tr>
            <td><a href="/applications/<?= $app['id'] ?>"><?= View::e($app['candidate_name']) ?></a></td>
            <td><?= View::e($app['job_title']) ?></td>
            <td><?= View::e($app['current_stage_name'] ?? 'No stage') ?></td>
            <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
            <td><?= DateHelper::timeAgo($app['applied_at']) ?></td>
            <td>
                <a href="/applications/<?= $app['id'] ?>" class="btn btn-sm">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $paginator->render('/applications') ?>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
