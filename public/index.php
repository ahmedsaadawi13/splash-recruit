<?php
// FILE: /public/index.php

/**
 * SplashRecruit - Front Controller
 *
 * Entry point for all requests.
 */

// Load configuration
require_once __DIR__ . '/../config/app.php';

// Autoload core classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/core/' . $class . '.php',
        __DIR__ . '/../app/models/' . $class . '.php',
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/helpers/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize router
$router = new Router();

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

// Public career pages
$router->get('/careers/:tenantSlug', 'PublicCareerController@careers');
$router->get('/careers/:tenantSlug/jobs/:jobSlug', 'PublicCareerController@jobDetail');
$router->post('/careers/:tenantSlug/jobs/:jobSlug/apply', 'PublicCareerController@apply');
$router->get('/careers/:tenantSlug/jobs/:jobSlug/thank-you', 'PublicCareerController@thankYou');

// API Routes
$router->get('/api/jobs', 'ApiController@getJobs');
$router->post('/api/applications', 'ApiController@createApplication');
$router->get('/api/applications/:applicationId/status', 'ApiController@getApplicationStatus');

// ============================================================================
// Authentication Routes
// ============================================================================

$router->get('/', 'AuthController@login');
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@processLogin');
$router->get('/logout', 'AuthController@logout');

// ============================================================================
// Authenticated Routes
// ============================================================================

// Dashboard
$router->get('/dashboard', 'DashboardController@index');

// Jobs
$router->get('/jobs', 'JobController@index');
$router->get('/jobs/create', 'JobController@create');
$router->post('/jobs', 'JobController@store');
$router->get('/jobs/:id', 'JobController@show');
$router->get('/jobs/:id/edit', 'JobController@edit');
$router->post('/jobs/:id/update', 'JobController@update');
$router->post('/jobs/:id/delete', 'JobController@delete');

// Candidates
$router->get('/candidates', 'CandidateController@index');
$router->get('/candidates/create', 'CandidateController@create');
$router->post('/candidates', 'CandidateController@store');
$router->get('/candidates/:id', 'CandidateController@show');

// Applications
$router->get('/applications', 'ApplicationController@index');
$router->get('/applications/:id', 'ApplicationController@show');
$router->post('/applications/:id/move-stage', 'ApplicationController@moveToStage');

// Dispatch request
$request = new Request();
$url = parse_url($request->uri(), PHP_URL_PATH);
$method = $request->method();

$router->dispatch($url, $method);
