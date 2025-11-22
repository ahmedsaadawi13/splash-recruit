<?php
// FILE: /app/core/Router.php

/**
 * Router class - Handles URL routing to controllers
 *
 * Maps URLs to controller actions and manages route parameters.
 */
class Router
{
    private $routes = [];
    private $namedRoutes = [];

    /**
     * Add a GET route
     *
     * @param string $path
     * @param string $handler
     * @param string|null $name
     * @return void
     */
    public function get($path, $handler, $name = null)
    {
        $this->addRoute('GET', $path, $handler, $name);
    }

    /**
     * Add a POST route
     *
     * @param string $path
     * @param string $handler
     * @param string|null $name
     * @return void
     */
    public function post($path, $handler, $name = null)
    {
        $this->addRoute('POST', $path, $handler, $name);
    }

    /**
     * Add a route for any method
     *
     * @param string $method
     * @param string $path
     * @param string $handler
     * @param string|null $name
     * @return void
     */
    private function addRoute($method, $path, $handler, $name = null)
    {
        $pattern = $this->convertToRegex($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'name' => $name
        ];

        if ($name !== null) {
            $this->namedRoutes[$name] = $path;
        }
    }

    /**
     * Convert route path to regex pattern
     *
     * @param string $path
     * @return string
     */
    private function convertToRegex($path)
    {
        // Replace :param with named capture group
        $pattern = preg_replace('/\/:([a-zA-Z0-9_]+)/', '/(?P<$1>[^/]+)', $path);
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }

    /**
     * Dispatch the request to appropriate controller
     *
     * @param string $url
     * @param string $method
     * @return void
     */
    public function dispatch($url, $method)
    {
        // Remove query string
        $url = strtok($url, '?');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $url, $matches)) {
                // Extract parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Parse handler (Controller@method)
                list($controller, $action) = explode('@', $route['handler']);

                $controllerFile = __DIR__ . '/../controllers/' . $controller . '.php';

                if (!file_exists($controllerFile)) {
                    $this->notFound();
                    return;
                }

                require_once $controllerFile;

                if (!class_exists($controller)) {
                    $this->notFound();
                    return;
                }

                $controllerInstance = new $controller();

                if (!method_exists($controllerInstance, $action)) {
                    $this->notFound();
                    return;
                }

                // Call controller method with parameters
                call_user_func_array([$controllerInstance, $action], $params);
                return;
            }
        }

        $this->notFound();
    }

    /**
     * Handle 404 Not Found
     *
     * @return void
     */
    private function notFound()
    {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        exit;
    }

    /**
     * Generate URL from route name
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function route($name, $params = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            return '#';
        }

        $path = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $path = str_replace(':' . $key, $value, $path);
        }

        return $path;
    }
}
