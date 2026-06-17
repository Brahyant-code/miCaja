<?php
namespace Core;

class Router {

    // Rutas registradas, indexadas por verbo HTTP.
    // Cada entrada: ['pattern' => regex, 'action' => 'Controlador@metodo']
    private $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    private $publicRoutes = [
        '',
        'home',
        'login',
    ];

    // --- Registro de rutas (estilo amigable) ---
    public function get($ruta, $accion)    { return $this->addRoute('GET', $ruta, $accion); }
    public function post($ruta, $accion)   { return $this->addRoute('POST', $ruta, $accion); }
    public function put($ruta, $accion)    { return $this->addRoute('PUT', $ruta, $accion); }
    public function delete($ruta, $accion) { return $this->addRoute('DELETE', $ruta, $accion); }

    private function addRoute($verbo, $ruta, $accion) {
        $this->routes[$verbo][] = [
            'path'    => trim($ruta, '/'),
            'pattern' => $this->compilar($ruta),
            'action'  => $accion,
        ];
        return $this; // permite encadenar
    }

    // Convierte una ruta amigable ("/usuarios/{id}") en una expresión regular.
    // Los segmentos {nombre} capturan cualquier valor que no contenga "/".
    // Los segmentos fijos se escapan para evitar sorpresas con caracteres especiales.
    private function compilar($ruta) {
        $partes = explode('/', trim($ruta, '/'));
        $regexPartes = [];

        foreach ($partes as $parte) {
            if (preg_match('#^\{[a-zA-Z_][a-zA-Z0-9_]*\}$#', $parte)) {
                $regexPartes[] = '([^/]+)';
            } else {
                $regexPartes[] = preg_quote($parte, '#');
            }
        }

        return '#^' . implode('/', $regexPartes) . '$#';
    }

    public function run() {
        // 1. Verbo HTTP de la petición
        $verbo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!isset($this->routes[$verbo])) {
            return $this->notFound();
        }

        // 2. URL limpia (mismo saneo que ya usábamos)
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url = trim($url, '/');

        // El .htaccess a veces inyecta "public" como primer segmento; lo quitamos.
        if (strpos($url, 'public/') === 0) {
            $url = substr($url, strlen('public/'));
        } elseif ($url === 'public') {
            $url = '';
        }

        // 3. Buscar la primera ruta que coincida
        foreach ($this->routes[$verbo] as $ruta) {
            if (preg_match($ruta['pattern'], $url, $matches)) {
                $params = array_slice($matches, 1); // valores capturados {..}
                if (!$this->isRoutePublic($ruta['path'])) {
                    $this->requireAuth();
                }
                return $this->despachar($ruta['action'], $params);
            }
        }

        // 4. Sin coincidencia
        $this->notFound();
    }

    private function isRoutePublic($ruta) {
        return in_array(trim($ruta, '/'), $this->publicRoutes, true);
    }

    private function requireAuth() {
        $token = \Core\Auth::obtenerToken();
        if (!$token || !\Core\Auth::verificarToken($token)) {
            Response::error('No autorizado', 401);
        }
    }

    // Resuelve "Controlador@metodo" e invoca el método con los parámetros.
    private function despachar($accion, array $params) {
        // Formato esperado: "HomeController@index"
        if (strpos($accion, '@') === false) {
            return $this->notFound();
        }

        list($controllerName, $methodName) = explode('@', $accion, 2);
        $controllerClass = "\\App\\Controllers\\" . $controllerName;

        // No permitir métodos mágicos/internos
        if ($methodName === '' || $methodName[0] === '_') {
            return $this->notFound();
        }

        if (!class_exists($controllerClass)) {
            return $this->notFound();
        }

        $controller = new $controllerClass();

        // El método debe existir y ser público (no heredado/protegido ni estático)
        if (!method_exists($controller, $methodName)) {
            return $this->notFound();
        }
        $reflection = new \ReflectionMethod($controller, $methodName);
        if (!$reflection->isPublic() || $reflection->isStatic()) {
            return $this->notFound();
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    // Respuesta 404 consistente en JSON (vía el punto único de salida)
    private function notFound() {
        Response::error('Ruta no encontrada', 404);
    }
}
