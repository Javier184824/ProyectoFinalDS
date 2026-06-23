<?php
require_once __DIR__ . '/../model/UsuarioModel.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class UsuarioController
{
    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    public function login(): void
    {
        session_start();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->json(400, ['success' => false, 'error' => 'JSON inválido']);
        }
        if (empty($data['correo']) || empty($data['contrasena'])) {
            $this->json(400, [
                'success' => false,
                'error' => 'Correo y contraseña requeridos'
            ]);
        }

        try {
            $usuario = $this->usuarioModel->buscarPorCorreo((string) $data['correo']);

            if (!$usuario || !password_verify($data['contrasena'], $usuario['contrasena'])) {
                $this->json(401, [
                    'success' => false,
                    'error' => 'Credenciales inválidas'
                ]);
            }

            $response = [
                'success'   => true,
                'idUsuario' => $usuario['idUsuario'],
                'nombre'    => $usuario['nombre'],
                'rol'       => $usuario['rol'],
                'token'     => bin2hex(random_bytes(16)),
            ];

            $_SESSION['login_response'] = $response; //antes 
            http_response_code(200);
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;

        } catch (PDOException $e) {
            $this->json(500, [
                'success' => false,
                'error' => 'Error interno al iniciar sesión'
            ]);
        }
    }

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $campos = ['correo', 'nombre', 'nombreUsuario', 'contrasena', 'rol'];
        foreach ($campos as $campo) {
            if (empty($data[$campo])) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }
        
        $rol = strtoupper($data['rol']);
        if (!in_array($rol, ['ESTUDIANTE', 'PROFESOR'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'rol invalido, debe ser ESTUDIANTE o PROFESOR']);
            exit;
        }
        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        try{
            $idUsuario = $this->usuarioModel->registrarUsuario(
                (string) $data['correo'],
                (string) $data['nombre'],
                (string) $data['nombreUsuario'],
                $hash,
                $rol
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'success'   => true,
                'idUsuario' => $idUsuario,
                'estado'    => 'registrado'
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            if ($code === '23000') {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Correo o nombre de usuario ya existe']);
                exit;
            }
            elseif ($code === 'HY000' && str_contains($msg, 'chk_correo')) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8'); // por si acaso
                echo json_encode(['success' => false, 'error' => 'Formato de correo inválido']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al registrar']);
            exit;
        }
    }

    public function perfil(): void
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
            $usuario = $this->usuarioModel->verPerfil($data['idUsuario']);
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'usuario'  => $usuario
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Error interno al buscar usuario'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

    }

    public function cambioCorreo(): void
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
        $data = json_decode(file_get_contents('php://input'), true);

        $campos = ['correo'];
        foreach ($campos as $campo) {
            if (empty($data[$campo])) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }
        try{
            $success = $this->usuarioModel->cambiarCorreo(
                (string) $usuario['idUsuario'],
                (string) $data['correo']
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'success'   => true,
                'estado'    => 'correo cambiado'
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            if ($code === '23000') {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Correo o nombre de usuario ya existe']);
                exit;
            }
            elseif ($code === 'HY000' && str_contains($msg, 'chk_correo')) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8'); // por si acaso
                echo json_encode(['success' => false, 'error' => 'Formato de correo inválido']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al registrar']);
            exit;
        }
    }
    public function cambioNombre(): void
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
        $data = json_decode(file_get_contents('php://input'), true);

        $campos = ['nombre'];
        foreach ($campos as $campo) {
            if (empty($data[$campo])) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }
        try{
            $success = $this->usuarioModel->cambiarNombre(
                (string) $usuario['idUsuario'],
                (string) $data['nombre']
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'success'   => true,
                'estado'    => 'nombre cambiado'
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            if ($code === '23000') {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Correo o nombre de usuario ya existe']);
                exit;
            }
            elseif ($code === 'HY000' && str_contains($msg, 'chk_correo')) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8'); // por si acaso
                echo json_encode(['success' => false, 'error' => 'Formato de correo inválido']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al registrar']);
            exit;
        }
    }

    public function enviarCodigoCambioContrasena(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['correo']) || trim($data['correo']) === '') {
            echo json_encode([
                "success" => false,
                "error" => "Correo requerido"
            ]);
            return;
        }

        $correo = trim($data['correo']);

        try {
            $codigo = (string) random_int(100000, 999999);

            $enviado = $this->enviarCorreoRecuperacion($correo, $codigo);

            if (!$enviado) {
                echo json_encode([
                    "success" => false,
                    "error" => "No se pudo enviar el correo"
                ]);
                return;
            }

            echo json_encode([
                "success" => true,
                "message" => "Código enviado correctamente",
                "codigo" => $codigo
            ]);

        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }

    private function enviarCorreoRecuperacion(string $destinatario, string $codigo): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username = 'idedstec@gmail.com';
            $mail->Password = 'qwri cftr gdbj vgud';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('idedstec@gmail.com', 'Sistema de Entregas');
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Código para cambio de contraseña';

            $mail->Body = "
                <h2>Cambio de contraseña</h2>
                <p>Su código de verificación es:</p>
                <h1>$codigo</h1>
                <p>Ingrese este código en la aplicación para continuar con el cambio de contraseña.</p>
            ";

            $mail->AltBody = "Su código de verificación es: $codigo";

            $mail->send();

            return true;

        } catch (Exception $e) {
            error_log("Error PHPMailer: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function verificarCorreo(): void
    {
        session_start();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            $this->json(400, ['success' => false, 'error' => 'JSON inválido']);
        }
        if (empty($data['correo'])) {
            $this->json(400, [
                'success' => false,
                'error' => 'Correo requerido'
            ]);
        }

        try {
            $usuario = $this->usuarioModel->buscarPorCorreo((string) $data['correo']);

            if (!$usuario) {
                $this->json(401, [
                    'success' => false,
                    'error' => 'Credenciales inválidas/Correo inexistente'
                ]);
            }

            $response = [
                'success'   => true
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;

        } catch (PDOException $e) {
            $this->json(500, [
                'success' => false,
                'error' => 'Error interno al iniciar sesión'
            ]);
        }
    }

    public function cambioContrasena(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $campos = ['correo','contrasena'];
        foreach ($campos as $campo) {
            if (empty($data[$campo])) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => "campo requerido: {$campo}"]);
                exit;
            }
        }
        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        try{
            $idUsuario = $this->usuarioModel->cambiarContrasena(
                (string) $data['correo'],
                $hash
            );

            http_response_code(201);
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'success'   => true,
                'estado'    => 'Contrasena cambiada'
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            if ($code === '23000') {
                http_response_code(409);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Correo o nombre de usuario ya existe']);
                exit;
            }
            elseif ($code === 'HY000' && str_contains($msg, 'chk_correo')) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8'); // por si acaso
                echo json_encode(['success' => false, 'error' => 'Formato de correo inválido']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno al registrar']);
            exit;
        }
    }

    private function json(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    
}