<?php
// FILE: /app/views/candidates/index.php
require_once __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
    <h1>Candidates</h1>
    <a href="/candidates/create" class="btn btn-primary">Add Candidate</a>
</div>

<div class="filters">
    <form method="GET" action="/candidates" class="filter-form">
        <input type="text" name="search" placeholder="Search candidates..." value="<?= View::e($filters['search'] ?? '') ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Location</th>
            <th>Source</th>
            <th>Applications</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($candidates as $candidate): ?>
        <tr>
            <td><a href="/candidates/<?= $candidate['id'] ?>"><?= View::e($candidate['full_name']) ?></a></td>
            <td><?= View::e($candidate['email']) ?></td>
            <td><?= View::e($candidate['phone']) ?></td>
            <td><?= View::e($candidate['city']) ?></td>
            <td><?= View::e($candidate['source']) ?></td>
            <td><?= $candidate['applications_count'] ?></td>
            <td>
                <a href="/candidates/<?= $candidate['id'] ?>" class="btn btn-sm">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $paginator->render('/candidates') ?>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
