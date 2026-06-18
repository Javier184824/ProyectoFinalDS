<?php
// ===============================================================
// home.php - unico View PHP del sistema
// Solo inyecta las constantes de configuracion que el JS necesita
// y sirve el HTML inicial. El resto lo hace app.js.
// ===============================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de entregas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="site-header">
        <div class="container">
            <h1 class="site-title">Sistema de entregas</h1>
            <p class="site-subtitle">Inicio de sesión</p>
        </div>
    </header>

    <main class="container">

        <!--  Formulario para crear URL corta  -->
        <section class="card" id="section-create">
            <h2>Nueva URL corta</h2>
            <form id="form-create" autocomplete="off">
                <div class="input-group">
                    <input
                        type="url"
                        id="input-url"
                        name="url"
                        placeholder="https://mi-url-muy-larga.com/pagina/con/ruta"
                        required
                    >
                    <button type="submit" id="btn-create">Acortar</button>
                </div>
                <p id="form-message" class="form-message" aria-live="polite"></p>
            </form>
        </section>

        <!--  Resultado de la ultima URL creada  -->
        <section class="card hidden" id="section-result">
            <h2>¡Lista! Aqui esta tu URL corta</h2>
            <div class="result-box">
                <a id="result-link" href="#" target="_blank" rel="noopener"></a>
                <button id="btn-copy" class="btn-secondary" title="Copiar al portapapeles">📋 Copiar</button>
            </div>
            <p id="copy-feedback" class="form-message" aria-live="polite"></p>
        </section>

        <!--  Lista de URLs  -->
        <section class="card" id="section-list">
            <div class="section-header">
                <h2>Mis URLs</h2>
                <button id="btn-refresh" class="btn-secondary">↺ Actualizar</button>
            </div>
            <div id="url-list-container">
                <!-- Spinner -->
                <div class="spinner" id="spinner-list"></div>
                <!-- La tabla se inyecta por app.js -->
                <div id="url-list"></div>
            </div>
        </section>

        <!--  Panel de estadisticas (se muestra al hacer click en "Ver stats")  -->
        <section class="card hidden" id="section-stats">
            <div class="section-header">
                <h2 id="stats-title">Estadisticas</h2>
                <button id="btn-close-stats" class="btn-secondary">✕ Cerrar</button>
            </div>

            <div id="stats-summary" class="stats-summary"></div>

            <div class="stats-columns">
                <!-- Grafica de barras por pais -->
                <div>
                    <h3>clicks por pais</h3>
                    <div id="chart-container" class="chart-container"></div>
                </div>

                <!-- Tabla de actividad reciente -->
                <div>
                    <h3>Actividad reciente</h3>
                    <div id="recent-clicks"></div>
                </div>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        <div class="container">
            <p>Proyecto Diseño de Software - Stack LAMP · MVC · SPA</p>
        </div>
    </footer>

    <!-- Inyectamos BASE_URL para que app.js pueda construir las rutas de la API -->
    <script>
        const BASE_URL = '<?= rtrim(BASE_URL, '/') ?>';
    </script>
    <script src="js/app.js"></script>

</body>
</html>
