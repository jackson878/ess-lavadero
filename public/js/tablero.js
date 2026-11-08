/**
 * ESS-Lavadero - Gráfica "Ingresos de la semana" del tablero (Chart.js).
 *
 * Los datos llegan desde PHP en <script id="datosSemana" type="application/json">
 * con esta forma: { etiquetas: [...], valores: [...], indiceHoy: 6, indiceMejor: 5 }
 */
(function () {
    'use strict';

    const lienzo = document.getElementById('graficaSemana');
    const fuente = document.getElementById('datosSemana');
    if (!lienzo || !fuente || typeof Chart === 'undefined') {
        return;
    }

    const datos = JSON.parse(fuente.textContent);

    // Misma fuente que el resto de la página (con respaldo sin serifa mientras carga)
    Chart.defaults.font.family = "'Manrope', system-ui, 'Segoe UI', sans-serif";

    // Colores tomados de las variables CSS (estilos.css), así hay una sola fuente de verdad
    const css = getComputedStyle(document.documentElement);
    const color = (nombre) => css.getPropertyValue(nombre).trim();
    const claro = color('--ess-teal-claro');
    const fuerte = color('--ess-teal');
    const acento = color('--ess-acento');

    // Hoy = amarillo, mejor día = azul fuerte, los demás = azul claro
    const colores = datos.valores.map((_, i) => {
        if (i === datos.indiceHoy) return acento;
        if (i === datos.indiceMejor) return fuerte;
        return claro;
    });

    // Formato de pesos colombianos: 25000 -> "$ 25.000"
    const pesos = (valor) => '$ ' + Math.round(valor).toLocaleString('es-CO');

    new Chart(lienzo, {
        type: 'bar',
        data: {
            labels: datos.etiquetas,
            datasets: [{
                label: 'Ingresos',
                data: datos.valores,
                backgroundColor: colores,
                borderRadius: { topLeft: 6, topRight: 6 },
                borderSkipped: 'bottom',
                categoryPercentage: 0.85,
                barPercentage: 0.95
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: (ctx) => pesos(ctx.parsed.y) }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: color('--ess-borde') },
                    ticks: { color: color('--ess-texto-suave'), font: { size: 13 } }
                },
                y: {
                    display: false,
                    beginAtZero: true
                }
            }
        }
    });
})();
