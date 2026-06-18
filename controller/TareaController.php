<?php
require_once __DIR__ . '/../model/CursoModel.php';
require_once __DIR__ . '/../model/UsuarioModel.php';
require_once __DIR__ . '/../model/TareaModel.php';
class TareaController
{

    private TareaModel $tareaModel;
    private UsuarioModel $usuarioModel;

    public function __construct()
    {

        $this->tareaModel = new TareaModel();
        $this->usuarioModel = new UsuarioModel();
    }
    
    public function cargarTareas(): void
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

        if (empty($_GET['idCurso'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'idCurso requerido'
            ]);
            exit;
        }

        $idCurso = $_GET['idCurso'];
        try{
            $tareas = $this->tareaModel->cargarTareasCurso($idCurso);
            
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'tareas'  => $tareas
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al buscar tareas'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
    }


    // nuevas funciones para el controller
    // ── POST /api/tareas ──────────────────────────────────
    // Solo el profesor puede crear tareas
    public function crearTarea(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
 
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
            echo json_encode(['success' => false, 'error' => 'Solo los profesores pueden crear tareas']);
            exit;
        }
 
        $data = json_decode(file_get_contents('php://input'), true);
 
        if (!is_array($data)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON inválido']);
            exit;
        }
 
        // FIX: el HTML envia "nombre" y "descripcion"; el controller esperaba "nombreTarea"
        // Aceptamos ambos para ser compatibles con el formulario actual
        $nombreTarea = $data['nombreTarea'] ?? $data['nombre'] ?? '';
 
        $campos = ['idCurso', 'fechaEntrega', 'esGrupal'];
        foreach ($campos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '') {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }
 
        if (empty($nombreTarea)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'campo requerido: nombre']);
            exit;
        }
 
        try {
            $usuario = $this->usuarioModel->verificarRol((string) $session['idUsuario']);
 
            if (!$usuario || $usuario['rol'] !== 'PROFESOR') {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Profesor no encontrado']);
                exit;
            }
 
            $idTarea = $this->tareaModel->crearTarea(
                $session['idUsuario'], // idUsuario
                (int)    $data['idCurso'],
                (string) $nombreTarea,
                (string) ($data['descripcion'] ?? ''),
                (string) $data['fechaEntrega'],
                (int)    $data['esGrupal']
            );
 
            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'idTarea' => $idTarea,
                'estado'  => 'tarea creada'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
 
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Error interno al crear tarea']);
            exit;
        }
    }

    // PUT /api/tareas/{idTarea}
    // solo el profesor puede modificar una tarea existente
    public function modificarTarea(int $idTarea): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

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
            echo json_encode(['success' => false, 'error' => 'Solo los profesores pueden modificar tareas']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'JSON inválido']);
            exit;
        }

        // aceptamos nombre o nombreTarea igual que en crearTarea
        $nombreTarea = $data['nombreTarea'] ?? $data['nombre'] ?? '';

        $campos = ['fechaEntrega', 'esGrupal'];
        foreach ($campos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '') {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }

        if (empty($nombreTarea)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'campo requerido: nombre']);
            exit;
        }

        try {
            $this->tareaModel->actualizarTarea(
                $idTarea,
                (string) $nombreTarea,
                (string) ($data['descripcion'] ?? ''),
                (string) $data['fechaEntrega'],
                (int)    $data['esGrupal']
            );

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'idTarea' => $idTarea,
                'estado'  => 'tarea actualizada'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Error interno al modificar tarea']);
            exit;
        }
    }

}