// =========================================
// app.js - SPA del acortador de URLs
// IIFE para no contaminar el scope global.
// Consume la JSON API del backend PHP.
// =========================================

(function () {
    'use strict';

    // Wrapper sobre fetch que ya sabe la base de la API
    const api = {

        // GET
        async get(endpoint) {
            const res = await fetch(BASE_URL + endpoint);
            return res.json();
        },

        // POST con JSON
        async post(endpoint, data) {
            const res = await fetch(BASE_URL + endpoint, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(data),
            });
            return res.json();
        },
    };

    // Referencias DOM
    const formCreate       = document.getElementById('form-create');
    const inputUrl         = document.getElementById('input-url');
    const formMessage      = document.getElementById('form-message');
    const sectionResult    = document.getElementById('section-result');
    const resultLink       = document.getElementById('result-link');
    const btnCopy          = document.getElementById('btn-copy');
    const copyFeedback     = document.getElementById('copy-feedback');
    const btnRefresh       = document.getElementById('btn-refresh');
    const urlList          = document.getElementById('url-list');
    const spinnerList      = document.getElementById('spinner-list');
    const sectionStats     = document.getElementById('section-stats');
    const statsTitle       = document.getElementById('stats-title');
    const statsSummary     = document.getElementById('stats-summary');
    const chartContainer   = document.getElementById('chart-container');
    const recentClicksDiv  = document.getElementById('recent-clicks');
    const btnCloseStats    = document.getElementById('btn-close-stats');

    // Muestra/oculta el spinner de la lista
    function setLoading(loading) {
        spinnerList.style.display = loading ? 'block' : 'none';
    }

    // Muestra mensaje en el formulario
    function showMessage(el, text, type = 'info') {
        el.textContent = text;
        el.className = 'form-message ' + type;
    }

    // Formatea fecha ISO a formato legible en español
    function formatDate(isoString) {
        if (!isoString) return '-';
        const d = new Date(isoString.replace(' ', 'T'));
        return d.toLocaleDateString('es-MX', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    }

    // Trunca texto largo con "..."
    function truncate(text, maxLen = 50) {
        return text.length > maxLen ? text.slice(0, maxLen) + '…' : text;
    }

    // Render de lista de URLs
    function renderUrlList(urls) {
        if (!urls || urls.length === 0) {
            urlList.innerHTML = '<p class="text-muted" style="text-align:center;padding:1.5rem">Aun no hay URLs acortadas. ¡Crea la primera!</p>';
            return;
        }

        // Construir tabla
        const table = document.createElement('table');
        table.className = 'url-table';

        // Encabezados
        const thead = table.createTHead();
        const headRow = thead.insertRow();
        ['Codigo', 'URL original', 'clicks', 'Creada', ''].forEach(text => {
            const th = document.createElement('th');
            th.textContent = text;
            headRow.appendChild(th);
        });

        const tbody = table.createTBody();

        urls.forEach(url => {
            const tr = tbody.insertRow();

            // Columna codigo corto con link
            const tdCode = tr.insertCell();
            const a = document.createElement('a');
            a.href      = url.short_url;
            a.target    = '_blank';
            a.rel       = 'noopener';
            a.className = 'url-short-link';
            a.textContent = url.short_code;
            tdCode.appendChild(a);

            // Columna URL original truncada
            const tdOrig = tr.insertCell();
            tdOrig.className = 'url-original';
            tdOrig.title     = url.original_url; // tooltip con URL completa
            tdOrig.textContent = truncate(url.original_url, 55);

            // Columna contador de clicks
            const tdClicks = tr.insertCell();
            const badge = document.createElement('span');
            badge.className = 'badge-clicks';
            badge.textContent = url.click_count;
            tdClicks.appendChild(badge);

            // Columna fecha de creacion
            const tdDate = tr.insertCell();
            tdDate.textContent = formatDate(url.created_at);
            tdDate.style.fontSize = '0.8rem';
            tdDate.style.color = 'var(--color-text-muted)';

            // Columna boton de stats
            const tdBtn = tr.insertCell();
            const btn = document.createElement('button');
            btn.className   = 'btn-stats';
            btn.textContent = 'Stats';
            btn.dataset.code = url.short_code;
            btn.addEventListener('click', () => loadStats(url.short_code));
            tdBtn.appendChild(btn);
        });

        urlList.innerHTML = '';
        urlList.appendChild(table);
    }

    // Cargar lista de URLs
    async function loadUrls() {
        setLoading(true);
        urlList.innerHTML = '';

        try {
            const res = await api.get('/api/urls');
            if (res.success) {
                renderUrlList(res.data);
            } else {
                urlList.innerHTML = '<p class="text-muted">Error al cargar las URLs.</p>';
            }
        } catch (err) {
            urlList.innerHTML = '<p class="text-muted">Error de red. ¿Esta el servidor corriendo?</p>';
            console.error('Error cargando URLs:', err);
        } finally {
            setLoading(false);
        }
    }

    // Form: crear URL corta
    formCreate.addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = inputUrl.value.trim();
        if (!url) return;

        showMessage(formMessage, 'Acortando…', 'info');
        document.getElementById('btn-create').disabled = true;

        try {
            const res = await api.post('/api/urls', { url });

            if (res.success) {
                const shortUrl = res.data.short_url;

                // Mostrar resultado
                resultLink.href        = shortUrl;
                resultLink.textContent = shortUrl;
                sectionResult.classList.remove('hidden');

                // Mensaje si es nueva o ya existia
                if (res.message) {
                    showMessage(formMessage, res.message, 'info');
                } else {
                    showMessage(formMessage, 'URL acortada con exito', 'success');
                }

                // Limpiar input y recargar lista
                inputUrl.value = '';
                loadUrls();
            } else {
                showMessage(formMessage, res.error, 'error');
            }
        } catch (err) {
            showMessage(formMessage, 'Error de red. Intente de nuevo.', 'error');
            console.error('Error creando URL:', err);
        } finally {
            document.getElementById('btn-create').disabled = false;
        }
    });

    // Copiar al portapapeles 
    btnCopy.addEventListener('click', async () => {
        const text = resultLink.href;
        try {
            await navigator.clipboard.writeText(text);
            showMessage(copyFeedback, 'Copiado!!', 'success');
        } catch {
            // Fallback para browsers sin Clipboard
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showMessage(copyFeedback, 'Copiado!!', 'success');
        }
        setTimeout(() => { copyFeedback.textContent = ''; }, 2500);
    });

    // Boton Actualizar lista
    btnRefresh.addEventListener('click', loadUrls);

    // Grafica de barras
    function renderBarChart(data) {
        chartContainer.innerHTML = '';

        if (!data || data.length === 0) {
            chartContainer.innerHTML = '<p class="chart-empty">Sin datos de clicks aun</p>';
            return;
        }

        // Maximo para calcular porcentajes
        const maxCount = Math.max(...data.map(d => parseInt(d.count, 10)));

        data.forEach(item => {
            const count  = parseInt(item.count, 10);
            const pct    = maxCount > 0 ? Math.round((count / maxCount) * 100) : 0;

            const group = document.createElement('div');
            group.className = 'bar-group';

            const countEl = document.createElement('div');
            countEl.className   = 'bar-count';
            countEl.textContent = count;

            const bar = document.createElement('div');
            bar.className = 'bar';
            bar.style.height = pct + '%';
            bar.title = item.country + ': ' + count + ' click(s)';

            const label = document.createElement('div');
            label.className   = 'bar-label';
            label.textContent = item.country_code || item.country;

            group.appendChild(countEl);
            group.appendChild(bar);
            group.appendChild(label);
            chartContainer.appendChild(group);
        });
    }

    // Tabla de clicks
    function renderRecentClicks(clicks) {
        recentClicksDiv.innerHTML = '';

        if (!clicks || clicks.length === 0) {
            recentClicksDiv.innerHTML = '<p class="text-muted">Sin actividad reciente</p>';
            return;
        }

        const table = document.createElement('table');
        table.className = 'clicks-table';

        const thead = table.createTHead();
        const headRow = thead.insertRow();
        ['IP', 'Pais', 'Fecha'].forEach(t => {
            const th = document.createElement('th');
            th.textContent = t;
            headRow.appendChild(th);
        });

        const tbody = table.createTBody();
        clicks.forEach(click => {
            const tr = tbody.insertRow();

            const tdIp = tr.insertCell();

            // Mostramos solo parte de la IP porprivacidad
            tdIp.textContent = click.ip_address.split('.').slice(0, 3).join('.') + '.***';

            const tdCountry = tr.insertCell();
            tdCountry.textContent = click.country;

            const tdDate = tr.insertCell();
            tdDate.textContent = formatDate(click.accessed_at);
        });

        recentClicksDiv.appendChild(table);
    }

    // Cargar y mostrar estadisticas
    async function loadStats(shortCode) {
        // Mostramos el panel de stats y hacemos scroll
        sectionStats.classList.remove('hidden');
        sectionStats.scrollIntoView({ behavior: 'smooth' });

        statsTitle.textContent = 'Estadisticas de /' + shortCode;
        statsSummary.innerHTML = '<div class="spinner"></div>';
        chartContainer.innerHTML = '';
        recentClicksDiv.innerHTML = '';

        try {
            const res = await api.get('/api/urls/' + shortCode + '/stats');

            if (!res.success) {
                statsSummary.innerHTML = '<p class="text-muted">Error: ' + (res.error || 'desconocido') + '</p>';
                return;
            }

            const { url, stats } = res.data;

            // Resumen numerico
            statsSummary.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${stats.total_clicks}</div>
                    <div class="stat-label">Total de clicks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.clicks_by_country.length}</div>
                    <div class="stat-label">Paises distintos</div>
                </div>
                <div class="stat-card" style="flex:2;text-align:left">
                    <div style="font-size:0.78rem;color:var(--color-text-muted);margin-bottom:2px">URL original</div>
                    <a href="${url.original_url}" target="_blank" rel="noopener"
                       style="font-size:0.82rem;word-break:break-all;color:var(--color-primary)">
                        ${truncate(url.original_url, 80)}
                    </a>
                </div>
            `;

            renderBarChart(stats.clicks_by_country);
            renderRecentClicks(stats.recent_clicks);

        } catch (err) {
            statsSummary.innerHTML = '<p class="text-muted">Error de red al cargar estadisticas.</p>';
            console.error('Error stats:', err);
        }
    }

    // Cerrar stats
    btnCloseStats.addEventListener('click', () => {
        sectionStats.classList.add('hidden');
    });

    // Inicializacion
    document.addEventListener('DOMContentLoaded', () => {
        loadUrls();
    });

    // Tambien cargamos si el DOM ya estaba listo
    if (document.readyState !== 'loading') {
        loadUrls();
    }

})();
