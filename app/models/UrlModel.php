<?php
// ==================================
// Url.php - UrlModel
// Acceso a datos de la tabla `urls`.
// Wrapper sobre SQL.
// ==================================

class UrlModel extends BaseModel {

    protected string $table = 'urls';

    // Inserta una URL y devuelve registro recien creado
    public function create(string $originalUrl, string $shortCode): array {
        $this->query(
            'INSERT INTO urls (short_code, original_url) VALUES (?, ?)',
            [$shortCode, $originalUrl]
        );
        return $this->findById((int) $this->pdo->lastInsertId());
    }

    // Busca una URL por su codigo corto
    public function findByShortCode(string $code): array {
        $stmt = $this->query(
            'SELECT * FROM urls WHERE short_code = ?',
            [$code]
        );
        return $stmt->fetch() ?: [];
    }

    // Devuelve todas las URLs ordenadas (recientes)
    public function findAll(): array {
        $stmt = $this->query(
            'SELECT * FROM urls ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    // Incrementa el contador de clicks
    // Evita hacer COUNT(*) en `clicks` en cada listado
    public function incrementClickCount(int $id): void {
        $this->query(
            'UPDATE urls SET click_count = click_count + 1 WHERE id = ?',
            [$id]
        );
    }

    // Verifica si ya existe un short_code
    public function existsByShortCode(string $code): bool {
        $stmt = $this->query(
            'SELECT id FROM urls WHERE short_code = ?',
            [$code]
        );
        return (bool) $stmt->fetch();
    }

    // Busca si ya existe la URL original
    // Devuelve registro completo si existe, array vacio si no
    public function existsByOriginalUrl(string $url): array {
        $stmt = $this->query(
            'SELECT * FROM urls WHERE original_url = ?',
            [$url]
        );
        return $stmt->fetch() ?: [];
    }
}
