<?php
// FILE: /app/views/shared/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? View::e($title) : 'SplashRecruit' ?></title>
    <?= CSRF::metaTag() ?>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php if (Auth::getInstance()->isAuthenticated()): ?>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <a href="/dashboard">SplashRecruit</a>
            </div>
            <ul class="navbar-menu">
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/jobs">Jobs</a></li>
                <li><a href="/candidates">Candidates</a></li>
                <li><a href="/applications">Applications</a></li>
                <li class="navbar-user">
                    <span><?= View::e(Auth::getInstance()->user('name')) ?></span>
                    <a href="/logout">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container">
        <?php
        $session = Session::getInstance();
        if ($session->hasFlash('success')):
        ?>
        <div class="alert alert-success">
            <?= View::e($session->getFlash('success')) ?>
        </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-error">
            <?= View::e($session->getFlash('error')) ?>
        </div>
        <?php endif; ?>
