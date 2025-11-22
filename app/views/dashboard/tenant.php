<?php
// FILE: /app/views/dashboard/tenant.php
require_once __DIR__ . '/../shared/header.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Jobs</h3>
            <div class="stat-value"><?= $stats['total_jobs'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Jobs</h3>
            <div class="stat-value"><?= $stats['active_jobs'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Candidates</h3>
            <div class="stat-value"><?= $stats['total_candidates'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Applications</h3>
            <div class="stat-value"><?= $stats['total_applications'] ?></div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="section">
            <h2>Recent Jobs</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Applications</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentJobs as $job): ?>
                    <tr>
                        <td><a href="/jobs/<?= $job['id'] ?>"><?= View::e($job['title']) ?></a></td>
                        <td><?= View::e($job['department']) ?></td>
                        <td><span class="badge badge-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
                        <td><?= $job['applications_count'] ?></td>
                        <td><?= DateHelper::humanReadable($job['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="/jobs" class="btn btn-primary">View All Jobs</a>
        </div>

        <div class="section">
            <h2>Recent Applications</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Stage</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentApplications as $app): ?>
                    <tr>
                        <td><a href="/applications/<?= $app['id'] ?>"><?= View::e($app['candidate_name']) ?></a></td>
                        <td><?= View::e($app['job_title']) ?></td>
                        <td><?= View::e($app['current_stage_name'] ?? 'No stage') ?></td>
                        <td><?= DateHelper::timeAgo($app['applied_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="/applications" class="btn btn-primary">View All Applications</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
