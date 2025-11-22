<?php
// FILE: /app/core/Auth.php

/**
 * Auth class - Handles user authentication
 *
 * Singleton pattern for authentication management.
 */
class Auth
{
    private static $instance = null;
    private $session;
    private $user = null;

    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->session = Session::getInstance();
        $this->loadUser();
    }

    /**
     * Get singleton instance
     *
     * @return Auth
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load user from session
     *
     * @return void
     */
    private function loadUser()
    {
        $userId = $this->session->get('user_id');

        if ($userId) {
            $db = Database::getInstance();
            $db->query("SELECT * FROM users WHERE id = :id AND status = 'active' LIMIT 1");
            $db->bind(':id', $userId);
            $this->user = $db->fetch();

            // Update last activity
            if ($this->user) {
                $this->session->set('last_activity', time());
            }
        }
    }

    /**
     * Attempt to login user
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function attempt($email, $password)
    {
        $db = Database::getInstance();
        $db->query("SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $db->bind(':email', $email);
        $user = $db->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $this->login($user);

            // Update last login
            $db->query("UPDATE users SET last_login_at = NOW() WHERE id = :id");
            $db->bind(':id', $user['id']);
            $db->execute();

            return true;
        }

        return false;
    }

    /**
     * Login user
     *
     * @param array $user
     * @return void
     */
    public function login($user)
    {
        $this->user = $user;
        $this->session->regenerate();
        $this->session->set('user_id', $user['id']);
        $this->session->set('tenant_id', $user['tenant_id']);
        $this->session->set('role', $user['role']);
    }

    /**
     * Logout user
     *
     * @return void
     */
    public function logout()
    {
        $this->user = null;
        $this->session->clear();
        $this->session->destroy();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->user !== null;
    }

    /**
     * Get authenticated user
     *
     * @return array|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user property
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function user($key = null, $default = null)
    {
        if ($key === null) {
            return $this->user;
        }

        return isset($this->user[$key]) ? $this->user[$key] : $default;
    }

    /**
     * Check if user has role
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return in_array($this->user['role'], $roles);
    }

    /**
     * Check if user is platform admin
     *
     * @return bool
     */
    public function isPlatformAdmin()
    {
        return $this->hasRole('platform_admin');
    }

    /**
     * Check if user is tenant admin
     *
     * @return bool
     */
    public function isTenantAdmin()
    {
        return $this->hasRole('tenant_admin');
    }

    /**
     * Get user's tenant ID
     *
     * @return int|null
     */
    public function getTenantId()
    {
        return $this->user('tenant_id');
    }

    /**
     * Hash password
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
