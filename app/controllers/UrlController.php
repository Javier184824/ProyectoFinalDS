<?php
// ===================================================
// UrlController.php - CRUD de URLs + redireccion
// Crear URL corta, listar todas, redirigir al destino
// ===================================================

class UrlController extends BaseController {

    private UrlModel   $urlModel;
    private UrlService $urlService;
    private ClickModel $clickModel;
    private GeoService $geoService;

    public function __construct() {
        $this->urlModel   = new UrlModel();
        $this->urlService = new UrlService();
        $this->clickModel = new ClickModel();
        $this->geoService = new GeoService();
    }

    // POST /api/urls
    // Crea nueva URL corta o devuelve la existente si ya se acorto
    public function create(): void {
        $body = $this->getBody();
        $url  = trim($body['url'] ?? '');

        // Validacion
        if (empty($url)) {
            $this->jsonError('El campo "url" es requerido', 400);
        }

        // Validar que sea una URL real con http/https
        if (!$this->urlService->validateUrl($url)) {
            $this->jsonError('URL invalida. Debe empezar con http:// o https://', 400);
        }

        // Normalizar antes de guardar (lowercase y trimear espacios)
        $url = $this->urlService->normalizeUrl($url);

        // Revisar si ya existe URL
        $existente = $this->urlModel->existsByOriginalUrl($url);
        if (!empty($existente)) {
            $existente['short_url'] = BASE_URL . '/' . $existente['short_code'];
            $this->jsonResponse([
                'success' => true,
                'data'    => $existente,
                'message' => 'Esta URL ya fue acortada',
            ], 200); // 200 porque no creamos nada nuevo
        }

        // Generar un codigo corto unico
        try {
            $shortCode = $this->urlService->generateUniqueCode($this->urlModel);
        } catch (RuntimeException $e) {
            $this->jsonError('No se pudo generar el codigo. Intenta de nuevo.', 500);
        }

        // Guardar en la BD
        $registro = $this->urlModel->create($url, $shortCode);

        // Agregar la URL corta completa al response
        $registro['short_url'] = BASE_URL . '/' . $registro['short_code'];

        $this->jsonResponse(['success' => true, 'data' => $registro], 201);
    }

    // GET /api/urls
    // Devuelve todas las URLs con su conteo de clicks
    public function index(): void {
        $urls = $this->urlModel->findAll();

        // Agregamos short_url a cada registro para que el frontend no tenga que construirlo
        foreach ($urls as &$url) {
            $url['short_url'] = BASE_URL . '/' . $url['short_code'];
        }

        $this->jsonResponse([
            'success' => true,
            'data'    => $urls,
            'total'   => count($urls),
        ]);
    }

    // GET /{shortCode}
    // Redirige al destino y registra el click con geolocalizacion
    public function redirect(string $shortCode): void {
        $url = $this->urlModel->findByShortCode($shortCode);

        if (empty($url)) {
            $this->jsonError('Codigo no encontrado', 404);
        }

        // Obtener IP y geolocalizacion (puede ser lento si ip-api tarda)
        $ip  = $this->getClientIp();
        $geo = $this->geoService->lookup($ip);

        // Registrar el click
        $this->clickModel->record(
            (int) $url['id'],
            $ip,
            $geo['country'],
            $geo['country_code']
        );

        // Incrementar el contador
        $this->urlModel->incrementClickCount((int) $url['id']);

        // Redirigir al destino
        header('Location: ' . $url['original_url'], true, 302);
        exit;
    }
}
