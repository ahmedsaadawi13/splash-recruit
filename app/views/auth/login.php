<?php
// FILE: /app/views/auth/login.php
require_once __DIR__ . '/../shared/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Login to SplashRecruit</h1>

        <form method="POST" action="/login" class="auth-form">
            <?= CSRF::field() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
        </form>

        <div class="auth-footer">
            <p><strong>Demo Credentials:</strong></p>
            <ul>
                <li>Platform Admin: admin@splashrecruit.com / password123</li>
                <li>TechCorp Admin: sarah@techcorp.com / password123</li>
                <li>TechCorp Recruiter: mike@techcorp.com / password123</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
