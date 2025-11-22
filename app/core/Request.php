<?php
// FILE: /app/core/Request.php

/**
 * Request class - Handles HTTP request data
 *
 * Provides methods to access GET, POST, and other request data.
 */
class Request
{
    /**
     * Get GET parameter
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }

        return isset($_GET[$key]) ? $this->clean($_GET[$key]) : $default;
    }

    /**
     * Get POST parameter
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }

        return isset($_POST[$key]) ? $this->clean($_POST[$key]) : $default;
    }

    /**
     * Get request parameter (GET or POST)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $this->clean($_POST[$key]);
        }

        if (isset($_GET[$key])) {
            return $this->clean($_GET[$key]);
        }

        return $default;
    }

    /**
     * Get all input data
     *
     * @return array
     */
    public function all()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if request has a parameter
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Check if request is POST
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if request is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method() === 'GET';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get uploaded file
     *
     * @param string $key
     * @return array|null
     */
    public function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get request URL
     *
     * @return string
     */
    public function url()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public function ip()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     *
     * @return string
     */
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Clean input data
     *
     * @param mixed $data
     * @return mixed
     */
    private function clean($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'clean'], $data);
        }

        return trim($data);
    }
}
