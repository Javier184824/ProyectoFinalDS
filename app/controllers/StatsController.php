<?php
// ===========================================================
// StatsController.php - estadisticas de URL
// Devuelve total de clicks, distribucion por pais y actividad
// ===========================================================

class StatsController extends BaseController {

    private UrlModel   $urlModel;
    private ClickModel $clickModel;

    public function __construct() {
        $this->urlModel   = new UrlModel();
        $this->clickModel = new ClickModel();
    }

    // GET /api/urls/{shortCode}/stats
    // Devuelve el objeto URL + estadisticas de clicks
    public function show(string $shortCode): void {
        // Primero buscamos la URL
        $url = $this->urlModel->findByShortCode($shortCode);

        if (empty($url)) {
            $this->jsonError('URL no encontrada', 404);
        }

        $urlId = (int) $url['id'];

        // Objeto de stats
        $stats = [
            'total_clicks'      => (int) $url['click_count'],
            'clicks_by_country' => $this->clickModel->clicksByCountry($urlId),
            'recent_clicks'     => $this->clickModel->recentClicks($urlId, 10),
        ];

        // Agregamos short_url para que el frontend pueda mostrarlo
        $url['short_url'] = BASE_URL . '/' . $url['short_code'];

        $this->jsonResponse([
            'success' => true,
            'data'    => [
                'url'   => $url,
                'stats' => $stats,
            ],
        ]);
    }
}
