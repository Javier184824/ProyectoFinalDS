<?php

require_once __DIR__ . '/Core/Database.php';

class EntregaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function crearEntrega(string $idUsuario, int $idGrupoTrabajo, int $idTarea, int $idArchivoP, string $fechaCreacion, int $version, float $nota, ?string $contenido, ?string $comentarioProfesor): void{
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtEntrega = $this->pdo->prepare("
            INSERT INTO Entrega (idUsuario, idGrupoTrabajo, idTarea, idArchivoP, fechaCreacion, version, nota, comentarioProfesor, contenido)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtEntrega->execute([$idUsuario, $idGrupoTrabajo, $idTarea, $idArchivoP, $fechaCreacion, $version, $nota, $comentarioProfesor, $contenido]);
    }

    public function verificarGrupo(int $idTarea, string $idUsuario): ?array
    {
        $stmt = $this->pdo->prepare("SELECT idGrupoTrabajo 
        FROM EstudianteXGrupoTrabajo 
        WHERE idUsuario = ?");
        $stmt->execute([$idUsuario]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    

    // lista las entregas de una tarea con nombre del estudiante y del archivo
    public function listarPorTarea(int $idTarea): array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            SELECT e.idEntrega, e.idTarea, e.version, e.nota, e.comentarioProfesor, e.fechaCreacion,
                   u.nombre AS estudiante, a.nombreArchivoP AS archivo
            FROM Entrega e
            JOIN Usuario u ON e.idUsuario = u.idUsuario
            LEFT JOIN ArchivoP a ON e.idArchivoP = a.idArchivoP
            WHERE e.idTarea = ?
            ORDER BY e.fechaCreacion DESC
        ");
        $stmt->execute([$idTarea]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // devuelve el detalle de una entrega incluyendo contenido y firma del archivo
    public function obtenerPorId(int $idEntrega): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            SELECT e.idEntrega, e.idTarea, e.version, e.nota, e.comentarioProfesor, e.fechaCreacion,
                   u.nombre AS estudiante,
                   a.nombreArchivoP AS archivo, a.contenido, a.firma
            FROM Entrega e
            JOIN Usuario u ON e.idUsuario = u.idUsuario
            LEFT JOIN ArchivoP a ON e.idArchivoP = a.idArchivoP
            WHERE e.idEntrega = ?
        ");
        $stmt->execute([$idEntrega]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // guarda la nota y el comentario del profesor sobre una entrega
    public function calificar(int $idEntrega, float $nota, ?string $comentarioProfesor): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            UPDATE Entrega
            SET nota = ?, comentarioProfesor = ?
            WHERE idEntrega = ?
        ");
        $stmt->execute([$nota, $comentarioProfesor, $idEntrega]);
    }
}