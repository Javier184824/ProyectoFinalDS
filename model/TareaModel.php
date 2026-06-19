<?php

require_once __DIR__ . '/Core/Database.php';

class TareaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function crearTarea(string $idUsuario, int $idCurso, string $nombreTarea, string $descripcion, string $fechaEntrega, int $esGrupal): int {
        $stmt = $this->pdo->prepare("INSERT INTO Tarea (idUsuario, idCurso, nombreTarea, descripcion, fechaEntrega, esGrupal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$idUsuario, $idCurso, $nombreTarea, $descripcion, $fechaEntrega, $esGrupal]);
        return (int) $this->pdo->lastInsertId();
    }

    // actualiza los datos editables de una tarea existente
    public function actualizarTarea(int $idTarea, string $nombreTarea, string $descripcion, string $fechaEntrega, int $esGrupal): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            UPDATE Tarea
            SET nombreTarea = ?, descripcion = ?, fechaEntrega = ?, esGrupal = ?
            WHERE idTarea = ?
        ");
        $stmt->execute([$nombreTarea, $descripcion, $fechaEntrega, $esGrupal, $idTarea]);
    }

    public function cargarTareasCurso(string $idCurso): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtTareas = $this->pdo->prepare("
                SELECT t.nombreTarea, c.nombreCurso, t.fechaCreacion, t.fechaEntrega, t.descripcion, t.idTarea, t.esGrupal
                FROM Tarea t
                JOIN Curso c on t.idCurso = c.idCurso
                WHERE t.idCurso = ?
            ");
        $stmtTareas->execute([$idCurso]);
        $tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);
        return $tareas;
    }

    public function verificarGrupoT(int $idTarea): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Tarea WHERE idTarea = ?");
        $stmt->execute([$idTarea]);

        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tarea ?: null;

    }

}