<?php


require_once __DIR__ . '/Core/Database.php';
// se tuvo que agregar en la tabla ArchivoP el idUsuario porque vi que faltaba
class ArchivoPModel //implements IArchivoRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function guardarArchivo(
        int $idUsuario,
        string $nombreArchivoP,
        string $ruta,
        string $contenido,
        string $fechaCreacion,
        string $fechaModificacion,
        string $firma
    ): void {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $this->pdo->prepare("
            INSERT INTO ArchivoP 
            (idUsuario, nombreArchivoP, ruta, contenido, fechaCreacion, fechaModificacion, firma)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $idUsuario,
            $nombreArchivoP,
            $ruta,
            $contenido,
            $fechaCreacion,
            $fechaModificacion,
            $firma
        ]);
    }

    public function buscarArchivo(
        int $idUsuario,
        string $nombreArchivoP,
        string $ruta
    ): ?array {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM ArchivoP
            WHERE idUsuario = ?
              AND nombreArchivoP = ?
              AND ruta = ?
        ");

        $stmt->execute([
            $idUsuario,
            $nombreArchivoP,
            $ruta
        ]);

        $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $archivo ?: null;
    }

    public function actualizarArchivo(
    int $idUsuario,
    string $nombreArchivoP,
    string $ruta,
    string $contenido,
    string $fechaModificacionNueva,
    string $firma
): void {
    $stmt = $this->pdo->prepare("
        UPDATE ArchivoP
        SET contenido = ?,
            fechaModificacion = ?,
            firma = ?
        WHERE idUsuario = ?
          AND nombreArchivoP = ?
          AND ruta = ?
    ");

    $stmt->execute([
        $contenido,
        $fechaModificacionNueva,
        $firma,
        $idUsuario,
        $nombreArchivoP,
        $ruta
    ]);
}
}