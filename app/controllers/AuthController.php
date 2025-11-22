<?php
// FILE: /app/controllers/AuthController.php

/**
 * AuthController
 *
 * Handles user authentication (login, logout, registration).
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function login()
    {
        // Redirect if already logged in
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [
            'title' => 'Login - SplashRecruit',
            'error' => $this->session->getFlash('error'),
            'success' => $this->session->getFlash('success')
        ]);
    }

    /**
     * Process login
     */
    public function processLogin()
    {
        if (!$this->request->isPost()) {
            $this->redirect('/login');
        }

        $email = $this->request->post('email');
        $password = $this->request->post('password');

        // Validate
        $validator = Validation::make([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('error', $validator->firstError());
            $this->redirect('/login');
        }

        // Attempt login
        if ($this->auth->attempt($email, $password)) {
            // Log activity
            $activityLog = $this->model('ActivityLog');
            $activityLog->log([
                'tenant_id' => $this->auth->user('tenant_id'),
                'user_id' => $this->auth->user('id'),
                'action' => 'login',
                'description' => 'User logged in',
                'entity_type' => 'user',
                'entity_id' => $this->auth->user('id')
            ]);

            $this->redirect('/dashboard');
        } else {
            $this->session->setFlash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Log activity before logout
        if ($this->auth->isAuthenticated()) {
            $activityLog = $this->model('ActivityLog');
            $activityLog->log([
                'tenant_id' => $this->auth->user('tenant_id'),
                'user_id' => $this->auth->user('id'),
                'action' => 'logout',
                'description' => 'User logged out',
                'entity_type' => 'user',
                'entity_id' => $this->auth->user('id')
            ]);
        }

        $this->auth->logout();
        $this->session->setFlash('success', 'You have been logged out successfully.');
        $this->redirect('/login');
    }
}
