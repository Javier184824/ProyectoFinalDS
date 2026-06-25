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
    
    public function crearSolicitud(int $idUsuario, int $idCurso): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            INSERT INTO SolicitudCurso (idUsuario, idCurso) VALUES (?, ?)
        ");
        $stmt->execute([$idUsuario, $idCurso]);
    }

    public function listarSolicitudesPorCurso(int $idCurso): array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            SELECT s.idSolicitud, s.idUsuario, s.estado, s.fechaSolicitud,
                u.nombre, u.correo, u.nombreUsuario AS carnet
            FROM SolicitudCurso s
            JOIN Usuario u ON u.idUsuario = s.idUsuario
            WHERE s.idCurso = ? AND s.estado = 'PENDIENTE'
            ORDER BY s.fechaSolicitud ASC
        ");
        $stmt->execute([$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function responderSolicitud(int $idSolicitud, string $estado): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE SolicitudCurso SET estado = ? WHERE idSolicitud = ?
            ");
            $stmt->execute([$estado, $idSolicitud]);

            if ($estado === 'ACEPTADA') {
                // traer idUsuario e idCurso de la solicitud
                $stmt2 = $this->pdo->prepare("
                    SELECT idUsuario, idCurso FROM SolicitudCurso WHERE idSolicitud = ?
                ");
                $stmt2->execute([$idSolicitud]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $stmt3 = $this->pdo->prepare("
                        INSERT IGNORE INTO EstudianteXCurso (idUsuario, idCurso) VALUES (?, ?)
                    ");
                    $stmt3->execute([$row['idUsuario'], $row['idCurso']]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listarTodosLosCursos(): array
    {
        $stmt = $this->pdo->prepare("SELECT idCurso, nombreCurso FROM Curso ORDER BY nombreCurso");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstudiantesPorCurso(int $idCurso): array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("
            SELECT u.idUsuario,
                   u.nombreUsuario AS carnet,
                   u.nombre,
                   u.correo,
                   NULL AS grupoActual
            FROM EstudianteXCurso ec
            JOIN Usuario u ON u.idUsuario = ec.idUsuario
            WHERE ec.idCurso = ?
              AND u.rol = 'ESTUDIANTE'
            ORDER BY u.nombre
        ");
        $stmt->execute([$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}