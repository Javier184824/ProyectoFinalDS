<?php
// ============================================================
// public/index.php - Front Controller
// Punto de entrada unico de la aplicacion.
// 1. Carga config y autoloader
// 2. Registra rutas
// 3. Despacha el request al Controller correcto
// ============================================================

// ── 1. Configuracion ──────────────────────────────────────
require_once 'config.php';

// Headers globales: JSON por defecto, CORS abierto para desarrollo
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight CORS - el browser pregunta antes de hacer POST cross-origin
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 2. Autoloader ─────────────────────────────────────────
// Carga automaticamente las clases segun convencion de nombres.
// Busca en app/ (plano) y en todos los subdirectorios de model/
spl_autoload_register(function (string $class): void {
    $directorios = [
        __DIR__ . '/../controller/',
        __DIR__ . '/../model/',
        __DIR__ . '/../model/Core/',
    ];

    foreach ($directorios as $dir) {
        $archivo = $dir . $class . '.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
    throw new RuntimeException("Clase no encontrada: {$class}");
});

// ── 3. Parseo de la URI ────────────────────────────────────
// Quitamos el BASE_PATH para que el Router trabaje con rutas relativas
// Ejemplo: /url-shortener/api/urls → /api/urls
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalizamos el BASE_PATH (puede estar vacio en instalacion en root)
$basePath = rtrim(BASE_PATH, '/');
if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

// Aseguramos que siempre empiece con /
$uri = '/' . ltrim($uri, '/');

$method = $_SERVER['REQUEST_METHOD'];

// ── 4. Registro de rutas ───────────────────────────────────
$router = new Router();

// IMPORTANTE: el orden importa - las rutas especificas de /api PRIMERO
// para que no sean atrapadas por el catch-all /{shortCode}

// API para el Backend

// Manda los datos del HTML para comparlos con los usuarios con la base de datos
$router->add('POST', '/api/auth/login', function () {
    $controller = new UsuarioController();
    $controller->login();
});

// Manda los datos del HTML para crear un nuevo usuario
$router->add('POST', '/api/auth/register', function () {
    $controller = new UsuarioController();
    $controller->register();
});

$router->add('POST', '/api/auth/recCont', function () {
    $controller = new UsuarioController();
    $controller->enviarCodigoCambioContrasena();
});

$router->add('POST', '/api/auth/confirmarCorreo', function () {
    $controller = new UsuarioController();
    $controller->verificarCorreo();
});

$router->add('POST', '/api/auth/cambiarContrasena', function () {
    $controller = new UsuarioController();
    $controller->cambioContrasena();
});

$router->add('GET', '/api/auth/perfil', function () {
    $controller = new UsuarioController();
    $controller->perfil();
});

$router->add('POST', '/api/perfil/cambioNombre', function () {
    $controller = new UsuarioController();
    $controller->cambioNombre();
});

$router->add('POST', '/api/perfil/cambioCorreo', function () {
    $controller = new UsuarioController();
    $controller->cambioCorreo();
});

// Obtiene los cursos según el usuario que inició sesión
$router->add('GET', '/api/cursos', function () {
    $controller = new CursoController();
    $controller->cargarCursos();
});

$router->add('POST', '/api/cursos', function () {
    $controller = new CursoController();
    $controller->crearCurso();
});

$router->add('GET', '/api/cursos/todos', function () {
    (new CursoController())->listarTodos();
});

$router->add('POST', '/api/cursos/unirse', function () {
    (new CursoController())->unirseACurso();
});

$router->add('GET', '/api/tareas', function () {
    $controller = new TareaController();
    $controller->cargarTareas();
});

$router->add('GET', '/api/entregas/individuales', function () {
    $controller = new EntregaController();
    $controller->obtenerEntregasEstudiante();
});

$router->add('GET', '/api/entregas/grupales', function () {
    $controller = new EntregaController();
    $controller->obtenerEntregasGrupos();
});

$router->add('POST', '/api/tareas/verificarGrupo', function () {
    $controller = new TareaController();
    $controller->verificarTGrupo();
});

$router->add('POST', '/api/grupos/verGrupo', function () {
    $controller = new GrupoController();
    $controller->obtenerIdGrupo();
});

$router->add('POST', '/api/archivop/guardar', function () {
    $controller = new ArchivoPController();
    $controller->guardarArchivo();
});

$router->add('POST', '/api/archivop/actualizar', function () {
    $controller = new ArchivoPController();
    $controller->actualizarArchivo();
});

$router->add('POST', '/api/archivop/verificar', function () {
    $controller = new ArchivoPController();
    $controller->verificarArchivo();
});

$router->add('POST', '/api/tareas', function () {
    (new TareaController())->crearTarea();
});

$router->add('PUT', '/api/tareas/{idTarea}', function (int $idTarea) {
    $controller = new TareaController();
    $controller->modificarTarea((int) $idTarea);
});

$router->add('POST', '/api/tareas/entregar', function () {
    $controller = new EntregaController();
    $controller->crearEntrega();
});
$router->add('GET', '/api/tareas/{idTarea}/entregas', function (int $idTarea) {
    $controller = new EntregaController();
    $controller->listarPorTarea((int) $idTarea);
});

// RUTAS que faltaban

$router->add('GET', '/api/tareas/{idTarea}/estudiantes', function (string $idTarea) {
    (new GrupoController())->listarEstudiantes((int) $idTarea);
});

$router->add('GET', '/api/tareas/{idTarea}/grupos', function (string $idTarea) {
    (new GrupoController())->listar((int) $idTarea);
});

$router->add('POST', '/api/tareas/{idTarea}/grupos', function (string $idTarea) {
    (new GrupoController())->crear((int) $idTarea);
});

$router->add('PUT', '/api/tareas/{idTarea}/grupos/{idGrupo}', function (string $idTarea, string $idGrupo) {
    (new GrupoController())->actualizar((int) $idTarea, (int) $idGrupo);
});

$router->add('DELETE', '/api/tareas/{idTarea}/grupos/{idGrupo}', function (string $idTarea, string $idGrupo) {
    (new GrupoController())->eliminar((int) $idTarea, (int) $idGrupo);
});

$router->add('POST', '/api/archivos', function () {});

$router->add('GET', '/api/archivos/{idArchivo}', function (int $idArchivo) {});

$router->add('PUT', '/api/tareas/{idArchivo}', function (int $idArchivo) {});

$router->add('POST', '/api/archivos/{idArchivo}', function (int $idArchivo) {});

$router->add('POST', '/api/ejecucion', function () {});

$router->add('GET', '/api/entregas/{idEntrega}/firma', function (int $idEntrega) {});

$router->add('GET', '/api/entregas/{idEntrega}', function (int $idEntrega) {
    $controller = new EntregaController();
    $controller->obtener((int) $idEntrega);
});

$router->add('PUT', '/api/entregas/{idEntrega}', function (int $idEntrega) {
    $controller = new EntregaController();
    $controller->calificar((int) $idEntrega);
});

$router->add('GET', '/api/bitacora', function () {
    // TODO: conectar con BitacoraController cuando se implemente
    // El profesor podrá ver desde el web el historial de operaciones sobre archivos
    // BitacoraController::listar() → consulta tabla Bitacora filtrada por idUsuario o idArchivo
});

$router->add('GET', '/api/bitacora/ultima-version', function () {
    // TODO: devolver la última entrada de Bitacora para un archivo dado
    // Útil para el cliente desktop para saber si hay una versión más nueva en el servidor
    // Query: SELECT * FROM Bitacora WHERE nombreArchivo = ? ORDER BY fecha DESC LIMIT 1
});

// API para la Base de Datos
$router->add('GET', '/data/usuarios/{id}', function (int $id) {});

$router->add('POST', '/data/usuarios', function () {});

$router->add('GET', '/data/tareas', function () {});

$router->add('POST', '/data/tareas', function () {});

$router->add('POST', '/data/grupos', function () {});

$router->add('POST', '/data/archivos', function () {});

$router->add('GET', '/data/archivos/{idArchivo}', function (int $idArchivo) {});

$router->add('PUT', '/data/archivos/{idArchivo}', function (int $idArchivo) {});

$router->add('POST', '/data/entregas', function () {});

$router->add('GET', '/data/entregas', function () {});

$router->add('POST', '/data/bitacora', function () {});

$router->add('GET', '/data/bitacora', function () {});

$router->add('GET', '/data/usuarios', function () {});

$router->add('PUT', '/data/tareas/{idTarea}', function (int $idTarea) {});

$router->add('PUT', '/data/archivos/{idArchivo}/firma', function (int $idArchivo) {});

$router->add('GET', '/data/entregas/{idEntrega}/firma', function (int $idEntrega) {});

$router->add('GET', '/data/bitacora/ultima-version', function () {});

// API para Git
$router->add('POST', '/git/repos', function () {});

$router->add('POST', '/git/commits', function () {});

$router->add('POST', '/api/urls', function () {
    (new UrlController())->create();
});

// API de rutas base
$router->add('GET', '/', function () {
    // Para el HTML desactivamos el Content-Type JSON que pusimos arriba
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/login.html';
    exit;
});

// Dirige a la página de Inicio de Sesión
$router->add('GET', '/login', function () {
    // Para el HTML desactivamos el Content-Type JSON que pusimos arriba
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/login.html';
    exit;
});

// Dirige a la página de Registro de Cuenta
$router->add('GET', '/register', function () {
    // Para el HTML desactivamos el Content-Type JSON que pusimos arriba
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/register.html';
    exit;
});

// Dirige a la página de menuProfesor
$router->add('GET', '/menuProfesor', function () {
    // Para el HTML desactivamos el Content-Type JSON que pusimos arriba
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/menuProfesor.html';
    exit;
});

// Dirige a la página de menuProfesor
$router->add('GET', '/menuEstudiante', function () {
    // Para el HTML desactivamos el Content-Type JSON que pusimos arriba
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/menuEstudiante.html';
    exit;
});

$router->add('GET', '/crearTarea', function () {
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/crearTarea.html';
    exit;
});

$router->add('GET', '/entregas', function () {
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/verEntregas.html';
    exit;
});

$router->add('GET', '/revisarEntrega', function () {
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/revisarEntrega.html';
    exit;
});

$router->add('GET', '/gestionGrupos', function () {
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../views/gestionGrupos.html';
    exit;
});


// ── 5. Despachar ──────────────────────────────────────────
$router->dispatch($method, $uri);
