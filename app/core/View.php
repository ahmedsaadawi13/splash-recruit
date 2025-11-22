<?php
// FILE: /app/core/View.php

/**
 * View class - Handles view rendering
 *
 * Provides methods for loading and rendering views with data.
 */
class View
{
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    public static function render($view, $data = [])
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
     * Escape HTML output
     *
     * @param string $string
     * @return string
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias for escape method
     *
     * @param string $string
     * @return string
     */
    public static function e($string)
    {
        return self::escape($string);
    }
}
