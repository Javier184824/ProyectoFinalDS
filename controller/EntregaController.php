<?php
require_once __DIR__ . '/../model/EntregaModel.php';
class EntregaController
{

    private EntregaModel $entregaModel;

    public function __construct()
    {

        $this->entregaModel = new EntregaModel();
    }

    public function crearEntrega(): void
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

        $usuario = $_SESSION['login_response'];
        $idUsuario = (int) $usuario['idUsuario'];

        $data = json_decode(file_get_contents('php://input'), true);

        $campos = ['idGrupoTrabajo', 'idTarea', 'idArchivoP', 'fechaCreacion', 'version', 'nota'];

        foreach ($campos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '') {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }

        $comentarioProfesor = $data['comentarioProfesor'] ?? null;      

        try {
            $this->entregaModel->crearEntrega(
                $idUsuario,
                (int) $data['idGrupoTrabajo'],
                (int) $data['idTarea'],
                (int) $data['idArchivoP'],
                (string) $data['fechaCreacion'],
                (int) $data['version'],
                (float) $data['nota'],
                $comentarioProfesor
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'estado'  => 'Entrega guardada'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    // GET /api/tareas/{idTarea}/entregas
    // lista las entregas recibidas para una tarea
    public function listarPorTarea(int $idTarea): void
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

        try {
            $entregas = $this->entregaModel->listarPorTarea($idTarea);

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'  => true,
                'entregas' => $entregas
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al listar entregas'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // GET /api/entregas/{idEntrega}
    // devuelve el detalle de una entrega con su codigo y firma
    public function obtener(int $idEntrega): void
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

        try {
            $entrega = $this->entregaModel->obtenerPorId($idEntrega);

            if (!$entrega) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Entrega no encontrada']);
                exit;
            }

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'entrega' => $entrega
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al obtener la entrega'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // PUT /api/entregas/{idEntrega}
    // solo el profesor puede calificar una entrega
    public function calificar(int $idEntrega): void
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
            echo json_encode(['success' => false, 'error' => 'Solo los profesores pueden calificar']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || !isset($data['nota']) || $data['nota'] === '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'campo requerido: nota']);
            exit;
        }

        $nota = (float) $data['nota'];
        if ($nota < 0 || $nota > 100) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'La nota debe estar entre 0 y 100']);
            exit;
        }

        $comentarioProfesor = $data['comentarioProfesor'] ?? null;

        try {
            $this->entregaModel->calificar($idEntrega, $nota, $comentarioProfesor);

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'estado'  => 'calificacion guardada'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al guardar la calificacion'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

}