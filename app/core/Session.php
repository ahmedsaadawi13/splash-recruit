<?php
// FILE: /app/core/Session.php

/**
 * Session class - Manages user sessions
 *
 * Singleton pattern for session management.
 */
class Session
{
    private static $instance = null;

    /**
     * Private constructor - starts session
     */
    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get singleton instance
     *
     * @return Session
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set session value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Check if session has a key
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     *
     * @param string $key
     * @return void
     */
    public function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session data
     *
     * @return void
     */
    public function clear()
    {
        session_unset();
    }

    /**
     * Destroy session
     *
     * @return void
     */
    public function destroy()
    {
        session_destroy();
    }

    /**
     * Set flash message
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setFlash($key, $value)
    {
        $_SESSION['flash'][$key] = $value;
    }

    /**
     * Get flash message and remove it
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFlash($key, $default = null)
    {
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }

        return $default;
    }

    /**
     * Check if flash message exists
     *
     * @param string $key
     * @return bool
     */
    public function hasFlash($key)
    {
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Regenerate session ID
     *
     * @return void
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }
}
