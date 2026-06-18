<?php

require_once __DIR__ . '/../model/ArchivoPModel.php';
require_once __DIR__ . '/../model/IArchivoRepositorio.php';
require_once __DIR__ . '/../model/ArchivoDecorator.php';
require_once __DIR__ . '/../model/ArchivoLogDecorator.php';
require_once __DIR__ . '/../model/ArchivoFirmaDecorator.php';


class ArchivoPController
{
    private ArchivoPModel $archivoPModel;

    public function __construct()
    {
        // Cadena de los decoradores: 
        // ArchivoLogDecorator   → registra en bitácora (conectar BitacoraModel en el futuro)
        // ArchivoFirmaDecorator → valida que SHA-256 del contenido coincida con la firma
        // ArchivoPModel         → persiste en base de datos
        //$this->archivoPModel = new ArchivoLogDecorator(
        //    new ArchivoFirmaDecorator(
        //        new ArchivoPModel()
        //    )
        //);

        $this->archivoPModel = new ArchivoPModel();
    }

    public function guardarArchivo(): void
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

        $campos = ['nombreArchivoP', 'ruta', 'contenido', 'fechaCreacion', 'fechaModificacion', 'firma'];

        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $data)) {
                echo json_encode([
                    "success" => false,
                    "error" => "campo requerido: " . $campo
                ]);
                return;
            }
        }

        try {
            $this->archivoPModel->guardarArchivo(
                $idUsuario,
                (string) $data['nombreArchivoP'],
                (string) $data['ruta'],
                (string) $data['contenido'],
                (string) $data['fechaCreacion'],
                (string) $data['fechaModificacion'],
                (string) $data['firma']
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'estado'  => 'archivo guardado'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;
        }
    }

    public function actualizarArchivo(): void
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

        $campos = ['nombreArchivoP', 'ruta', 'contenido', 'fechaModificacionNueva', 'firma'];

        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $data)) {
                echo json_encode([
                    "success" => false,
                    "error" => "campo requerido: " . $campo
                ]);
                return;
            }
        }

        try {
            $archivo = $this->archivoPModel->buscarArchivo(
                $idUsuario,
                (string) $data['nombreArchivoP'],
                (string) $data['ruta']
            );

            if (!$archivo) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Archivo no encontrado'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $this->archivoPModel->actualizarArchivo(
                $idUsuario,
                (string) $data['nombreArchivoP'],
                (string) $data['ruta'],
                (string) $data['contenido'],
                (string) $data['fechaModificacionNueva'],
                (string) $data['firma']
            );

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'estado'  => 'archivo actualizado'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al actualizar archivo'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    public function verificarArchivo(): void
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

        $campos = ['nombreArchivoP', 'firma', 'ruta'];

        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $data)) {
                echo json_encode([
                    "success" => false,
                    "error" => "campo requerido: " . $campo
                ]);
                return;
            }
        }

        try {
            $archivo = $this->archivoPModel->buscarArchivo(
                $idUsuario,
                (string) $data['nombreArchivoP'],
                (string) $data['ruta']
            );

            if (!$archivo) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Archivo no encontrado'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if ($archivo['firma'] !== $data['firma']) {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'El archivo fue modificado fuera del IDE'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'estado'  => 'archivo verificado',
                'idArchivoP' => $archivo['idArchivoP']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;
        }
    }
}