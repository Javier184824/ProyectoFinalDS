<?php
// =====================================
// Controller.php - BaseController
// Helpers para todos los Controllers:
// responder JSON con el status correcto
// leer el body JSON del request
// obtener la IP real 
// =====================================

abstract class BaseController {

    // Envia una respuesta JSON exitosa
    // $data puede ser array o cualquier cosa serializable a JSON
    protected function jsonResponse(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit; // ya respondimos, cortamos la ejecucion
    }

    // Envia una respuesta de error
    protected function jsonError(string $message, int $status = 400): void {
        $this->jsonResponse(['success' => false, 'error' => $message], $status);
    }

    // Lee y parsea el JSON del request (para POST/PUT)
    protected function getBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // Intenta obtener la IP real del cliente
    protected function getClientIp(): string {
        $cabeceras = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_FORWARDED_FOR',    // proxy generico
            'HTTP_X_REAL_IP',          // nginx proxy
            'REMOTE_ADDR',             // directo
        ];

        foreach ($cabeceras as $cabecera) {
            if (!empty($_SERVER[$cabecera])) {
                $ip = trim(explode(',', $_SERVER[$cabecera])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // fallback por si no encontramos nada 
        return '0.0.0.0';
    }
}
