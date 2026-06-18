<?php
// ========================================================
// Model.php - BaseModel
// Clase padre de todos los Modelos. Centraliza la conexion
// y ofrece helpers
// ========================================================

abstract class BaseModel {

    // Conexion PDO compartida
    protected PDO $pdo;

    // Nombre de la tabla principal del Model hijo
    protected string $table = '';

    public function __construct() {
        // Pedimos la conexion al Singleton, no creamos una nueva
        $this->pdo = Database::getInstance()->getConnection();
    }

    // Helper: prepara y ejecuta cualquier consulta SQL
    // Devuelve el PDOStatement listo para fetchAll / fetch
    protected function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Helper: busca un registro por su PK (id)
    protected function findById(int $id): array {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $stmt->fetch() ?: [];
    }
}
