<?php
// FILE: /app/core/CSRF.php

/**
 * CSRF class - Cross-Site Request Forgery protection
 *
 * Provides methods for CSRF token generation and validation.
 */
class CSRF
{
    /**
     * Generate CSRF token
     *
     * @return string
     */
    public static function generateToken()
    {
        $session = Session::getInstance();

        if (!$session->has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $session->set('csrf_token', $token);
        }

        return $session->get('csrf_token');
    }

    /**
     * Get CSRF token
     *
     * @return string
     */
    public static function getToken()
    {
        $session = Session::getInstance();
        return $session->get('csrf_token', self::generateToken());
    }

    /**
     * Validate CSRF token
     *
     * @param string $token
     * @return bool
     */
    public static function validate($token)
    {
        $session = Session::getInstance();
        $sessionToken = $session->get('csrf_token');

        if (!$sessionToken || !$token) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Get CSRF input field HTML
     *
     * @return string
     */
    public static function field()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF meta tag HTML
     *
     * @return string
     */
    public static function metaTag()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
