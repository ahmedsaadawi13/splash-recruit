<?php
// FILE: /app/core/Response.php

/**
 * Response class - Handles HTTP responses
 *
 * Provides methods for sending different types of responses.
 */
class Response
{
    /**
     * Send JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     *
     * @param string $url
     * @param int $statusCode
     * @return void
     */
    public static function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Send success JSON response
     *
     * @param mixed $data
     * @param string $message
     * @return void
     */
    public static function success($data = [], $message = 'Success')
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * Send error JSON response
     *
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    public static function error($message, $statusCode = 400)
    {
        self::json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Set HTTP status code
     *
     * @param int $code
     * @return void
     */
    public static function setStatusCode($code)
    {
        http_response_code($code);
    }

    /**
     * Set response header
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function setHeader($key, $value)
    {
        header("{$key}: {$value}");
    }
}
