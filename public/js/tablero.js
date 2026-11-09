/**
 * ESS-Lavadero - Gráficas del tablero gerencial con Chart.js (RF-06).
 *
 * Los datos llegan desde PHP en <script id="datosTablero" type="application/json">.
 *
 * Reglas de diseño aplicadas:
 *  - Nunca dos ejes Y en una gráfica: servicios e ingresos van en gráficas separadas.
 *  - Una serie = un solo color; los colores de carro y moto se validaron
 *    para que se distingan también con daltonismo.
 *  - El rojo de "bajo el mínimo" es un color de ESTADO (alerta), no de serie.
 *  - Marcas delgadas, esquinas redondeadas de 4 px, cuadrícula suave, tooltip al pasar el mouse.
 */
(function () {
    'use strict';

    const fuente = document.getElementById('datosTablero');
    if (!fuente || typeof Chart === 'undefined') return;
    const d = JSON.parse(fuente.textContent);

    // Colores desde las variables CSS (estilos.css): una sola fuente de verdad
    const css = getComputedStyle(document.documentElement);
    const v = (n) => css.getPropertyValue(n).trim();
    const C = {
        serie: v('--graf-serie'),         // azul: series de un solo valor y "carro"
        moto: v('--graf-moto'),           // ámbar: "moto"
        alerta: v('--graf-alerta'),       // rojo de estado: stock bajo el mínimo
        neutro: v('--graf-neutro'),       // gris: referencias (meta, mínimo)
        texto: v('--ess-texto-suave'),
        grilla: v('--graf-grilla'),
        superficie: v('--ess-tarjeta')
    };

    const pesos = (x) => '$ ' + Math.round(x).toLocaleString('es-CO');
    const pesosCorto = (x) => x >= 1e6 ? '$ ' + (x / 1e6).toLocaleString('es-CO', { maximumFractionDigits: 1 }) + ' M'
        : x >= 1e3 ? '$ ' + Math.round(x / 1e3) + ' mil' : '$ ' + x;

    // Estilo común
    Chart.defaults.font.family = "'Manrope', system-ui, 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = C.texto;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.plugins.legend.display = false;            // una serie: el título la nombra
    Chart.defaults.plugins.tooltip.backgroundColor = '#0E2A3A';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.interaction.mode = 'index';
    Chart.defaults.interaction.intersect = false;

    const ejeX = { grid: { display: false }, border: { color: C.grilla }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 12 } };
    const ejeY = (extra = {}) => ({ beginAtZero: true, grid: { color: C.grilla }, border: { display: false }, ticks: { precision: 0, ...extra } });
    const barra = { borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 36 };

    const crear = (id, config) => {
        const lienzo = document.getElementById(id);
        if (lienzo) new Chart(lienzo, config);
    };

    // ---------- KPI 1: servicios por día / mes ----------
    crear('graficaServicios', {
        type: 'bar',
        data: { labels: d.series.etiquetas, datasets: [{ label: 'Servicios', data: d.series.servicios, backgroundColor: C.serie, ...barra }] },
        options: { scales: { x: ejeX, y: ejeY() } }
    });

    // ---------- KPI 2: ingresos por día / mes ----------
    crear('graficaIngresos', {
        type: 'bar',
        data: { labels: d.series.etiquetas, datasets: [{ label: 'Ingresos', data: d.series.ingresos, backgroundColor: C.serie, ...barra }] },
        options: {
            scales: { x: ejeX, y: ejeY({ callback: pesosCorto }) },
            plugins: { tooltip: { callbacks: { label: (c) => ' Ingresos: ' + pesos(c.parsed.y) } } }
        }
    });

    // ---------- KPI 4: carro vs. moto ----------
    crear('graficaTipos', {
        type: 'doughnut',
        data: {
            labels: d.tipos.etiquetas,
            datasets: [{
                data: d.tipos.valores,
                backgroundColor: [C.serie, C.moto],
                borderColor: C.superficie, borderWidth: 2      // separación de 2 px entre segmentos
            }]
        },
        options: {
            cutout: '62%',
            interaction: { mode: 'nearest', intersect: true },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const total = c.dataset.data.reduce((a, b) => a + b, 0) || 1;
                            return ' ' + c.label + ': ' + c.parsed + ' (' + Math.round(c.parsed / total * 100) + ' %)';
                        }
                    }
                }
            }
        }
    });

    // ---------- KPI 5: servicios más vendidos (barras horizontales) ----------
    crear('graficaMasVendidos', {
        type: 'bar',
        data: {
            labels: d.masVendidos.etiquetas,
            datasets: [{
                label: 'Vendidos', data: d.masVendidos.valores, backgroundColor: C.serie,
                borderRadius: { topRight: 4, bottomRight: 4 }, borderSkipped: 'left', maxBarThickness: 22
            }]
        },
        options: {
            indexAxis: 'y',
            interaction: { mode: 'nearest', axis: 'y', intersect: false },
            scales: {
                x: { beginAtZero: true, grid: { color: C.grilla }, border: { display: false }, ticks: { precision: 0 } },
                y: { grid: { display: false }, border: { color: C.grilla } }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (c) => ' ' + c.parsed.x + ' vendidos · ' + pesos(d.masVendidos.facturado[c.dataIndex])
                    }
                }
            }
        }
    });

    // ---------- KPI 6: servicios por lavador ----------
    crear('graficaLavadores', {
        type: 'bar',
        data: { labels: d.lavadores.etiquetas, datasets: [{ label: 'Servicios', data: d.lavadores.valores, backgroundColor: C.serie, ...barra, maxBarThickness: 56 }] },
        options: {
            scales: { x: ejeX, y: ejeY() },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (c) => ' ' + c.parsed.y + ' servicios',
                        afterLabel: (c) => ' Facturado: ' + pesos(d.lavadores.facturado[c.dataIndex])
                    }
                }
            }
        }
    });

    // ---------- KPI 7: tiempo promedio de atención con la meta ----------
    crear('graficaTiempo', {
        type: 'line',
        data: {
            labels: d.series.etiquetas,
            datasets: [
                {
                    label: 'Tiempo promedio', data: d.series.tiempo, borderColor: C.serie, backgroundColor: C.serie,
                    borderWidth: 2, pointRadius: d.series.tiempo.length > 40 ? 0 : 3, pointHoverRadius: 5,
                    spanGaps: true, tension: 0.25
                },
                {
                    label: 'Meta', data: d.series.etiquetas.map(() => d.meta), borderColor: C.neutro,
                    borderWidth: 1.5, borderDash: [6, 5], pointRadius: 0, pointHoverRadius: 0
                }
            ]
        },
        options: {
            scales: { x: ejeX, y: ejeY({ callback: (x) => x + ' min' }) },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'line', boxWidth: 24 } },
                tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + (c.parsed.y === null ? 'sin datos' : Math.round(c.parsed.y) + ' min') } }
            }
        }
    });

    // ---------- KPI 8: insumos bajo el mínimo (stock actual frente al mínimo) ----------
    crear('graficaInsumos', {
        type: 'bar',
        data: {
            labels: d.insumos.etiquetas,
            datasets: [
                { label: 'Stock actual', data: d.insumos.stock, backgroundColor: C.alerta, borderRadius: 4, maxBarThickness: 16 },
                { label: 'Mínimo', data: d.insumos.minimo, backgroundColor: C.neutro, borderRadius: 4, maxBarThickness: 16 }
            ]
        },
        options: {
            indexAxis: 'y',
            interaction: { mode: 'index', axis: 'y', intersect: false },
            datasets: { bar: { categoryPercentage: 0.7, barPercentage: 0.9 } },
            scales: {
                x: { beginAtZero: true, grid: { color: C.grilla }, border: { display: false } },
                y: { grid: { display: false }, border: { color: C.grilla } }
            },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded' } },
                tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + c.parsed.x.toLocaleString('es-CO') + ' ' + d.insumos.unidades[c.dataIndex] } }
            }
        }
    });
})();
