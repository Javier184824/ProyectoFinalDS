<?php
require_once __DIR__ . '/../model/GrupoModel.php';
require_once __DIR__ . '/../model/UsuarioModel.php';

class GrupoController
{
    private GrupoModel $grupoModel;

    public function __construct()
    {
        $this->grupoModel = new GrupoModel();
    }

    private function sesion(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['login_response'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sesión no encontrada']);
            exit;
        }
        return $_SESSION['login_response'];
    }

    // GET /api/tareas/{idTarea}/grupos
    public function listar(int $idTarea): void
    {
        $this->sesion();
        try {
            $grupos = $this->grupoModel->listarPorTarea($idTarea);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'grupos' => $grupos]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al listar grupos']);
        }
    }

    // GET /api/tareas/{idTarea}/estudiantes
    public function listarEstudiantes(int $idTarea): void
    {
        $this->sesion();
        try {
            $estudiantes = $this->grupoModel->listarEstudiantesDelCurso($idTarea);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'estudiantes' => $estudiantes]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al listar estudiantes']);
        }
    }

    // POST /api/tareas/{idTarea}/grupos
    // Body: { nombre, miembros[] } para crear grupo
    // Body: { idGrupo, accion: 'agregar'|'quitar', carnet } para gestionar miembros
    public function crear(int $idTarea): void
    {
        $session = $this->sesion();
        $data    = json_decode(file_get_contents('php://input'), true);

        // Si viene accion es gestión de miembros desde gestionGrupos.html
        if (!empty($data['accion'])) {
            $this->gestionarMiembro($data, $idTarea);
            return;
        }

        // Si no, es creación de grupo nuevo
        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'nombre requerido']);
            exit;
        }

        
        try {
            $idCurso = $this->grupoModel->obtenerIdCursoDeTarea($idTarea);
            $idGrupo = $this->grupoModel->crear((int) $session['idUsuario'], $idCurso, $data['nombre']);
            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'idGrupo' => $idGrupo]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al crear grupo']);
        }
    }

    private function gestionarMiembro(array $data, int $idTarea): void
    {
        if (empty($data['idGrupo']) || empty($data['carnet'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'idGrupo y carnet requeridos']);
            exit;
        }

        $estudiante = $this->grupoModel->buscarEstudiantePorCarnet($data['carnet']);
        if (!$estudiante) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Estudiante no encontrado']);
            exit;
        }

        try {
            if ($data['accion'] === 'agregar') {
                $this->grupoModel->agregarMiembro((int) $data['idGrupo'], (int) $estudiante['idUsuario'], $idTarea);
            } else {
                $this->grupoModel->quitarMiembro((int) $data['idGrupo'], (int) $estudiante['idUsuario']);
            }
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
        
        } catch (\RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al gestionar miembro']);
        }
    }

    // PUT /api/tareas/{idTarea}/grupos/{idGrupo}
    public function actualizar(int $idGrupo): void
    {
        $this->sesion();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'nombre requerido']);
            exit;
        }

        try {
            $this->grupoModel->actualizar($idGrupo, $data['nombre']);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'estado' => 'grupo actualizado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar grupo']);
        }
    }

    // DELETE /api/tareas/{idTarea}/grupos/{idGrupo}
    public function eliminar(int $idGrupo): void
    {
        $this->sesion();
        try {
            $this->grupoModel->eliminar($idGrupo);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'estado' => 'grupo eliminado']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al eliminar grupo']);
        }
    }

    

}