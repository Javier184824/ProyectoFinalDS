<?php
// ===========================================================
// GeoService.php - geolocalizacion por IP con ip-api.com
// Para IPs privadas (localhost, LAN) devuelve un valor local.
// ===========================================================

class GeoService {

    // Rangos de IPs privadas/reservadas
    private const PRIVATE_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '::1/128',
        'fc00::/7',
    ];

    // Hace el lookup completo: detecta privada, llama la API, parsea
    // Devuelve ['country' => '...', 'country_code' => '...']
    public function lookup(string $ip): array {
        // Si es IP privada no tiene caso llamar la API
        if ($this->isPrivateIp($ip)) {
            return ['country' => 'Local/Dev', 'country_code' => 'LC'];
        }

        $json = $this->httpGet(IP_API_BASE . urlencode($ip) . '?fields=status,country,countryCode');

        if ($json === false) {
            // Error de red marcamos Unknown
            return ['country' => 'Unknown', 'country_code' => '--'];
        }

        return $this->parseResponse($json);
    }

    // Detecta si una IP esta en los rangos privados/localhost
    public function isPrivateIp(string $ip): bool {
        // Verificamos que la IP sea valida
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;

        // Para IPv6 simplificamos: si es loopback o empieza con fc/fd es privada
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip === '::1'
                || str_starts_with(strtolower($ip), 'fc')
                || str_starts_with(strtolower($ip), 'fd');
        }

        // FILTER_FLAG_NO_PRIV_RANGE y NO_RES_RANGE devuelven false para IPs privadas
        $esPublica = filter_var($ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $esPublica === false;
    }

    // HTTP GET con timeout para no bloquear la redireccion
    private function httpGet(string $url): string|false {
        $contexto = stream_context_create([
            'http' => [
                'timeout'  => 3,        // 3 segundos max
                'method'   => 'GET',
                'header'   => 'User-Agent: PHP-UrlShortener/1.0',
            ],
        ]);

        // Silenciamos el error con @
        return @file_get_contents($url, false, $contexto);
    }

    // Parseamos el JSON de ip-api.com y extrae lo que nos importa
    private function parseResponse(string $json): array {
        $data = json_decode($json, true);

        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return ['country' => 'Unknown', 'country_code' => '--'];
        }

        return [
            'country'      => $data['country']     ?? 'Unknown',
            'country_code' => $data['countryCode'] ?? '--',
        ];
    }
}
