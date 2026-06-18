<?php
// ============================================================
// Database.php - Singleton PDO
// Patron Singleton: una sola conexion por request, sin
// abrir y cerrar la BD en cada Model. Clasico en LAMP casero.
// ============================================================

class Database {

    // La unica instancia que va a existir
    private static ?Database $instance = null;

    // La conexion PDO que todos los Models van a usar
    private PDO $pdo;

    // Constructor privado: nadie puede hacer `new Database()` afuera
    private function __construct() {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // lanza excepciones en errores SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // arrays asociativos por defecto
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements reales
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En produccion no exponemos el mensaje de error
            $mensaje = (APP_ENV === 'development')
                ? 'Error de conexion BD: ' . $e->getMessage()
                : 'Error interno del servidor';
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => $mensaje]));
        }
    }

    // El punto de acceso global - devuelve siempre la misma instancia
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Los Models piden la conexion PDO a traves de aqui
    public function getConnection(): PDO {
        return $this->pdo;
    }

    // Bloqueamos clone y unserialize para que el Singleton no se rompa
    private function __clone() {}
    public function __wakeup() {
        throw new Exception('No se puede deserializar un Singleton');
    }
}
