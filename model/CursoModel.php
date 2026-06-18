<?php

require_once __DIR__ . '/Core/Database.php';

class CursoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function cargarCursosEstudiante(string $idUsuario): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtCursos = $this->pdo->prepare("
            SELECT c.idCurso, c.nombreCurso, u.nombre
            FROM EstudianteXCurso ec
            JOIN Curso c ON ec.idCurso = c.idCurso
            JOIN Usuario u ON c.idUsuario = u.idUsuario
            WHERE ec.idUsuario = ?
        ");
        $stmtCursos->execute([$idUsuario]);
        $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
        return $cursos;
    }

    public function cargarCursosProfesor(string $idUsuario): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtCursos = $this->pdo->prepare("
            SELECT idCurso, nombreCurso, idUsuario
            FROM Curso
            WHERE idUsuario = ?
        ");
        $stmtCursos->execute([$idUsuario]);
        $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
        return $cursos;
    }

    // inserta un curso del profesor y devuelve el id generado
    public function crearCurso(int $idUsuario, string $nombreCurso, ?string $descripcion): int
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            INSERT INTO Curso (idUsuario, nombreCurso, descripcion)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$idUsuario, $nombreCurso, $descripcion]);
        return (int) $this->pdo->lastInsertId();
    }
    public function unirseACurso(int $idUsuario, int $idCurso): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO EstudianteXCurso (idUsuario, idCurso) VALUES (?, ?)
        ");
        $stmt->execute([$idUsuario, $idCurso]);
    }
    public function listarTodosLosCursos(): array
    {
        $stmt = $this->pdo->prepare("SELECT idCurso, nombreCurso FROM Curso ORDER BY nombreCurso");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    
}