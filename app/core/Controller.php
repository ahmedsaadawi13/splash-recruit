<?php
// FILE: /app/core/Controller.php

/**
 * Base Controller class
 *
 * All controllers extend this base class for common functionality.
 */
class Controller
{
    protected $db;
    protected $auth;
    protected $request;
    protected $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->request = new Request();
        $this->session = Session::getInstance();
    }

    /**
     * Load a model
     *
     * @param string $model
     * @return object
     */
    protected function model($model)
    {
        $modelFile = __DIR__ . '/../models/' . $model . '.php';

        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }

        throw new Exception("Model {$model} not found.");
    }

    /**
     * Load a view
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    protected function view($view, $data = [])
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($viewFile)) {
            extract($data);
            require_once $viewFile;
        } else {
            throw new Exception("View {$view} not found.");
        }
    }

    /**
     * Redirect to a URL
     *
     * @param string $url
     * @return void
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Check if user is authenticated
     *
     * @return void
     */
    protected function requireAuth()
    {
        if (!$this->auth->isAuthenticated()) {
            $this->session->setFlash('error', 'Please login to continue.');
            $this->redirect('/login');
        }
    }

    /**
     * Check if user has required role
     *
     * @param array $roles
     * @return void
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $userRole = $this->auth->user('role');

        if (!in_array($userRole, $roles)) {
            $this->session->setFlash('error', 'You do not have permission to access this page.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Get current tenant ID
     *
     * @return int|null
     */
    protected function getTenantId()
    {
        return $this->auth->user('tenant_id');
    }

    /**
     * Validate CSRF token
     *
     * @return bool
     */
    protected function validateCsrf()
    {
        $token = $this->request->post('csrf_token');
        return CSRF::validate($token);
    }

    /**
     * Require CSRF validation for POST requests
     *
     * @return void
     */
    protected function requireCsrf()
    {
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
        }
    }
}
