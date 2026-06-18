<?php
// ============================================================
// Router.php - Router regex casero
// Mapea pares (metodo HTTP + patron URI) a callbacks/handlers.
// Sin dependencias externas, puro PHP vanilla.
// ============================================================

class Router {

    // Lista de rutas registradas
    // Cada elemento: ['method' => 'GET', 'pattern' => '#^/...#', 'handler' => callable]
    private array $routes = [];

    // Registra una ruta nueva
    // $pattern puede contener marcadores tipo {shortCode} que se convierten a grupos de captura
    public function add(string $method, string $pattern, callable $handler): void {
        // Convertimos {param} → grupo de captura nombrado (?P<param>[^/]+)
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $regex,
            'handler' => $handler,
        ];
    }

    // Despacha el request actual buscando la primera ruta que haga match
    // $method: el verbo HTTP (GET, POST, ...)
    // $uri:    la URI limpia (sin query string, sin BASE_PATH)
    public function dispatch(string $method, string $uri): void {
        $method = strtoupper($method);

        foreach ($this->routes as $ruta) {
            // Verificamos metodo y patron
            if ($ruta['method'] !== $method) continue;

            if (preg_match($ruta['pattern'], $uri, $matches)) {
                // Filtramos los indices numericos, dejamos solo grupos nombrados
                $params = array_filter(
                    $matches,
                    fn($key) => !is_int($key),
                    ARRAY_FILTER_USE_KEY
                );

                // Llamamos al handler con los parametros capturados
                call_user_func_array($ruta['handler'], array_values($params));
                return;
            }
        }

        // Ninguna ruta hizo match → 404
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Ruta no encontrada',
        ]);
    }
}
