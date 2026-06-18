<?php
require_once __DIR__ . '/../model/CursoModel.php';
require_once __DIR__ . '/../model/UsuarioModel.php';
class CursoController
{
    private CursoModel $cursoModel;
    private UsuarioModel $UserModel;

    public function __construct()
    {
        $this->cursoModel = new CursoModel();
        $this->UserModel = new UsuarioModel();
    }

    public function cargarCursos(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login_response'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Sesión no encontrada']);
            exit;
        }

        $data = $_SESSION['login_response'];
        try{
            $usuario = $this->UserModel->verificarRol($data['idUsuario']);
            if ($usuario['rol'] == 'ESTUDIANTE') {
                $cursos = $this->cursoModel->cargarCursosEstudiante($data['idUsuario']);
            } else {
                $cursos = $this->cursoModel->cargarCursosProfesor($data['idUsuario']);
            }

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'cursos'  => $cursos
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al buscar cursos'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

    }

    // POST /api/cursos
    // solo el profesor puede crear un curso
    public function crearCurso(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login_response'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Sesión no encontrada']);
            exit;
        }

        $session = $_SESSION['login_response'];

        if ($session['rol'] !== 'PROFESOR') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Solo los profesores pueden crear cursos']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty($data['nombreCurso'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'campo requerido: nombreCurso']);
            exit;
        }

        $descripcion = $data['descripcion'] ?? null;

        try {
            $idCurso = $this->cursoModel->crearCurso(
                (int) $session['idUsuario'],
                (string) $data['nombreCurso'],
                $descripcion !== null ? (string) $descripcion : null
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'idCurso' => $idCurso,
                'estado'  => 'curso creado'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        catch (PDOException $e) {
            $code = $e->getCode();
            if ($code === '23000') {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Ya existe un curso con ese nombre']);
                exit;
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al crear curso'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // Agregar después del método crearCurso()
    public function unirseACurso(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['login_response'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sesión no encontrada']);
            exit;
        }

        $session = $_SESSION['login_response'];

        if ($session['rol'] !== 'ESTUDIANTE') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo estudiantes pueden unirse a cursos']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['idCurso'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'idCurso requerido']);
            exit;
        }

        try {
            $this->cursoModel->unirseACurso((int) $session['idUsuario'], (int) $data['idCurso']);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'estado' => 'unido al curso']);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al unirse al curso']);
            exit;
        }
    }

    // Y un método para listar todos los cursos (para que el estudiante pueda elegir)
    public function listarTodos(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['login_response'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sesión no encontrada']);
            exit;
        }
        try {
            $cursos = $this->cursoModel->listarTodosLosCursos();
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'cursos' => $cursos]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al listar cursos']);
            exit;
        }
    }
}