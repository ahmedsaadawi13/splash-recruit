<?php
// FILE: /app/views/dashboard/platform_admin.php
require_once __DIR__ . '/../shared/header.php';
?>

<div class="dashboard">
    <h1>Platform Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Tenants</h3>
            <div class="stat-value"><?= $stats['total_tenants'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="stat-value"><?= $stats['total_users'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Jobs</h3>
            <div class="stat-value"><?= $stats['total_jobs'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Candidates</h3>
            <div class="stat-value"><?= $stats['total_candidates'] ?></div>
        </div>
    </div>

    <div class="section">
        <h2>Recent Tenants</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTenants as $tenant): ?>
                <tr>
                    <td><?= View::e($tenant['name']) ?></td>
                    <td><?= View::e($tenant['code']) ?></td>
                    <td><?= View::e($tenant['primary_contact_email']) ?></td>
                    <td><span class="badge badge-<?= $tenant['status'] ?>"><?= ucfirst($tenant['status']) ?></span></td>
                    <td><?= DateHelper::humanReadable($tenant['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
