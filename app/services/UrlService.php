<?php
// ================================================
// UrlService.php - logica de negocio para URLs
// Genera codigos cortos, valida y normaliza URLs.
// Separado del Controller para mantenerlo bonito.
// ================================================

class UrlService {

    // Alfabeto que puede aparecer en un codigo corto
    // Removemos 0/O y 1/l para evitar confusion
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';

    // Genera un codigo corto aleatorio
    // Usa random_int() en vez de rand() pq es mas seguro
    public function generateShortCode(): string {
        $alfabeto = self::ALPHABET;
        $longitud  = strlen($alfabeto);
        $codigo    = '';

        for ($i = 0; $i < SHORT_CODE_LENGTH; $i++) {
            $codigo .= $alfabeto[random_int(0, $longitud - 1)];
        }

        return $codigo;
    }

    // Genera un codigo que garantiza ser unico contra la BD
    // Reintenta si hay colision (poco probable)
    public function generateUniqueCode(UrlModel $urlModel): string {
        $intentos = 0;
        do {
            $codigo = $this->generateShortCode();
            $intentos++;
            if ($intentos > 10) {
                // Si hay 10 colisiones seguidas algo anda MUY mal
                throw new RuntimeException('No se pudo generar un codigo unico');
            }
        } while ($urlModel->existsByShortCode($codigo));

        return $codigo;
    }

    // Valida que la URL sea correcta
    public function validateUrl(string $url): bool {
        if (empty($url) || strlen($url) > 2048) return false;

        // Primero comprobamos el formato con FILTER_VALIDATE_URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

        // Solo http y https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower($scheme ?? ''), ['http', 'https'], true);
    }

    // Normaliza la URL: trim de espacios, fuerza lowercase en el scheme/host
    public function normalizeUrl(string $url): string {
        $url = trim($url);

        // Extraemos las partes para normalizar solo scheme y host (no el path)
        $partes = parse_url($url);
        if (!$partes) return $url;

        $scheme = strtolower($partes['scheme'] ?? 'https');
        $host   = strtolower($partes['host']   ?? '');
        $path   = $partes['path']   ?? '';
        $query  = isset($partes['query'])    ? '?' . $partes['query']    : '';
        $frag   = isset($partes['fragment']) ? '#' . $partes['fragment'] : '';
        $port   = isset($partes['port'])     ? ':' . $partes['port']     : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$frag}";
    }
}
