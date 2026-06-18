<?php
// ============================================================
// Click.php - ClickModel
// Acceso a datos de la tabla `clicks`.
// Registra visitas y provee datos para las estadisticas.
// ============================================================

class ClickModel extends BaseModel {

    protected string $table = 'clicks';

    // Registra un nuevo click con IP y geolocalizacion
    public function record(int $urlId, string $ip, string $country, string $countryCode): void {
        $this->query(
            'INSERT INTO clicks (url_id, ip_address, country, country_code) VALUES (?, ?, ?, ?)',
            [$urlId, $ip, $country, $countryCode]
        );
    }

    // Devuelve todos los clicks de una URL especifica
    public function findByUrlId(int $urlId): array {
        $stmt = $this->query(
            'SELECT * FROM clicks WHERE url_id = ? ORDER BY accessed_at DESC',
            [$urlId]
        );
        return $stmt->fetchAll();
    }

    // Agrupacion por pais - para la grafica de barras
    public function clicksByCountry(int $urlId): array {
        $stmt = $this->query(
            'SELECT country, country_code, COUNT(*) as count
             FROM clicks
             WHERE url_id = ?
             GROUP BY country, country_code
             ORDER BY count DESC',
            [$urlId]
        );
        return $stmt->fetchAll();
    }

    // Los N clicks mas recientes - para la tabla de actividad reciente
    public function recentClicks(int $urlId, int $limit = 10): array {
        $stmt = $this->query(
            'SELECT ip_address, country, country_code, accessed_at
             FROM clicks
             WHERE url_id = ?
             ORDER BY accessed_at DESC
             LIMIT ?',
            [$urlId, $limit]
        );
        return $stmt->fetchAll();
    }
}
