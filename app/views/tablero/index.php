<?php
/**
 * Vista: tablero gerencial con los 8 KPI (RF-06). Solo Dueño.
 * Las gráficas se dibujan con Chart.js en public/js/tablero.js usando
 * los datos del bloque JSON "datosTablero" al final de esta vista.
 *
 * @var string $desde
 * @var string $hasta
 * @var string $rango      atajo activo ('7', '30', 'hoy', 'mes', 'mes_anterior' o '')
 * @var string $agrupar    'dia' o 'mes'
 * @var array  $actual     resumen del rango [servicios, ingresos, cobros, ticket, tiempo]
 * @var array  $anterior   resumen del periodo anterior de igual duración
 * @var array  $series     etiquetas y valores por periodo
 * @var array  $tipos      ['carro' => n, 'moto' => n]
 * @var array  $masVendidos
 * @var array  $lavadores
 * @var array  $insumos    bajo el mínimo
 * @var int    $equipos    con mantenimiento vencido o próximo
 * @var array  $enLavadero
 * @var array  $graficas   datos para Chart.js
 */
require VIEWS_PATH . 'layouts/header.php';

[$notaServ, $claseServ]     = variacion_kpi($actual['servicios'], $anterior['servicios']);
[$notaIng, $claseIng]       = variacion_kpi($actual['ingresos'], $anterior['ingresos']);
[$notaTicket, $claseTicket] = variacion_kpi($actual['ticket'], $anterior['ticket']);
$porDia     = $agrupar === 'dia';
$totalTipos = max(1, $tipos['carro'] + $tipos['moto']);
$atajos     = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', 'mes' => 'Este mes', 'mes_anterior' => 'Mes anterior'];
$textoRango = $desde === $hasta ? fecha_larga($desde) : date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta));
?>

<!-- Encabezado -->
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <div class="ceja">Tablero gerencial</div>
        <h1 class="titulo-pagina"><?= $desde === $hasta ? 'Hoy, ' . e(fecha_larga($desde)) : 'Del ' . e($textoRango) ?></h1>
    </div>
    <a class="btn btn-ess" href="<?= url('orden', 'crear') ?>">+ Nuevo servicio</a>
</div>

<!-- Filtros: en una sola fila, encima de las gráficas -->
<form class="barra-filtros tarjeta mb-3" method="get" action="<?= url() ?>">
    <input type="hidden" name="c" value="tablero">
    <div class="d-flex flex-wrap align-items-end gap-2">
        <div class="btn-group flex-wrap" role="group" aria-label="Rangos rápidos">
            <?php foreach ($atajos as $clave => $texto): ?>
                <a class="btn btn-sm <?= $rango === (string) $clave ? 'btn-ess' : 'btn-ess-borde' ?>"
                   href="<?= url('tablero', 'index', ['rango' => $clave]) ?>"><?= e($texto) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="ms-lg-auto d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small mb-1" for="desde">Desde</label>
                <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?= e($desde) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label small mb-1" for="hasta">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="hasta" name="hasta" value="<?= e($hasta) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label small mb-1" for="agrupar">Agrupar</label>
                <select class="form-select form-select-sm" id="agrupar" name="agrupar">
                    <option value="dia" <?= $porDia ? 'selected' : '' ?>>Por día</option>
                    <option value="mes" <?= !$porDia ? 'selected' : '' ?>>Por mes</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-ess">Aplicar</button>
        </div>
    </div>
</form>

<!-- Alertas operativas -->
<?php if ($insumos || $equipos): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($insumos): ?>
            <a class="aviso aviso-alerta text-decoration-none flex-grow-1" href="<?= url('insumo') ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Alerta: <?= count($insumos) ?> <?= count($insumos) === 1 ? 'insumo' : 'insumos' ?> bajo el mínimo:
                <?= e(mb_strtolower(implode(', ', array_column($insumos, 'nombre')))) ?>
            </a>
        <?php endif; ?>
        <?php if ($equipos): ?>
            <a class="aviso aviso-alerta text-decoration-none" href="<?= url('equipo') ?>">
                <i class="bi bi-tools"></i> <?= $equipos ?> <?= $equipos === 1 ? 'equipo necesita' : 'equipos necesitan' ?> mantenimiento
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Tarjetas: KPI 1, 2, 3 y 7 (totales del rango) -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta"><span class="kpi-num">1</span> Servicios</div>
            <div class="kpi-valor"><?= number_format($actual['servicios'], 0, ',', '.') ?></div>
            <div class="kpi-nota <?= $claseServ ?>"><?= e($notaServ) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta"><span class="kpi-num">2</span> Ingresos</div>
            <div class="kpi-valor"><?= pesos($actual['ingresos']) ?></div>
            <div class="kpi-nota <?= $claseIng ?>"><?= e($notaIng) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta"><span class="kpi-num">3</span> Ticket promedio</div>
            <div class="kpi-valor"><?= pesos($actual['ticket']) ?></div>
            <div class="kpi-nota <?= $claseTicket ?>"><?= e($notaTicket) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta"><span class="kpi-num">7</span> Tiempo de atención</div>
            <?php if ($actual['tiempo'] === null): ?>
                <div class="kpi-valor">—</div>
                <div class="kpi-nota neutra">Sin órdenes terminadas</div>
            <?php else:
                $exceso = (int) round($actual['tiempo']) - META_TIEMPO_ATENCION_MIN; ?>
                <div class="kpi-valor"><?= (int) round($actual['tiempo']) ?> min</div>
                <div class="kpi-nota <?= $exceso > 0 ? 'alerta' : 'sube' ?>">
                    <?= $exceso > 0 ? '▲ ' . $exceso . ' min sobre la meta' : '✓ ' . abs($exceso) . ' min bajo la meta' ?>
                    (<?= META_TIEMPO_ATENCION_MIN ?>)
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI 1 y 2: series por día o por mes (dos gráficas, nunca un doble eje) -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">1</span> Servicios por <?= $porDia ? 'día' : 'mes' ?></h2>
            <div class="grafica"><canvas id="graficaServicios" role="img" aria-label="Servicios por <?= $porDia ? 'día' : 'mes' ?>"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">2</span> Ingresos por <?= $porDia ? 'día' : 'mes' ?></h2>
            <div class="grafica"><canvas id="graficaIngresos" role="img" aria-label="Ingresos por <?= $porDia ? 'día' : 'mes' ?>"></canvas></div>
        </div>
    </div>
</div>

<!-- KPI 4 y 5 -->
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">4</span> Carro vs. moto</h2>
            <div class="grafica grafica-dona"><canvas id="graficaTipos" role="img" aria-label="Servicios por tipo de vehículo"></canvas></div>
            <!-- Leyenda con valores: la identidad no depende solo del color -->
            <ul class="leyenda mt-3">
                <li><span class="muestra muestra-carro"></span> Carro <strong><?= $tipos['carro'] ?></strong>
                    <span class="text-secondary">(<?= round($tipos['carro'] / $totalTipos * 100) ?> %)</span></li>
                <li><span class="muestra muestra-moto"></span> Moto <strong><?= $tipos['moto'] ?></strong>
                    <span class="text-secondary">(<?= round($tipos['moto'] / $totalTipos * 100) ?> %)</span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">5</span> Servicios más vendidos</h2>
            <?php if (!$masVendidos): ?>
                <p class="text-secondary mb-0">No hay servicios en este periodo.</p>
            <?php else: ?>
                <div class="grafica"><canvas id="graficaMasVendidos" role="img" aria-label="Servicios más vendidos"></canvas></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI 6 y 7 -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">6</span> Servicios por lavador</h2>
            <div class="grafica"><canvas id="graficaLavadores" role="img" aria-label="Servicios por lavador"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">7</span> Tiempo promedio de atención</h2>
            <div class="grafica"><canvas id="graficaTiempo" role="img" aria-label="Tiempo promedio de atención en minutos"></canvas></div>
            <p class="grafica-nota">De "en espera" a "terminado". La línea punteada es la meta de <?= META_TIEMPO_ATENCION_MIN ?> min.</p>
        </div>
    </div>
</div>

<!-- KPI 8 y vehículos en el lavadero -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo"><span class="kpi-num">8</span> Insumos bajo el stock mínimo</h2>
            <?php if (!$insumos): ?>
                <div class="aviso aviso-ok"><i class="bi bi-check-circle"></i> Todos los insumos están sobre el mínimo.</div>
            <?php else: ?>
                <div class="grafica grafica-baja"><canvas id="graficaInsumos" role="img" aria-label="Stock actual frente al mínimo"></canvas></div>
                <p class="grafica-nota mb-0">Estado actual del inventario (no depende del rango de fechas).
                    <a href="<?= url('insumo') ?>">Registrar compra</a></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Vehículos en el lavadero ahora</h2>
            <?php if (!$enLavadero): ?>
                <p class="text-secondary mb-0">No hay vehículos en el lavadero en este momento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla-ess">
                        <thead><tr><th>Placa</th><th>Servicio</th><th>Lavador</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($enLavadero as $o): ?>
                            <tr>
                                <td class="placa"><?= e(placa_formato($o['placa'])) ?></td>
                                <td><?= e($o['servicios']) ?> <span class="text-secondary small">· <?= e($o['tipo']) ?></span></td>
                                <td><?= e($o['lavador']) ?></td>
                                <td><?= estado_badge($o['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Datos en tabla (accesibilidad: los mismos datos de las gráficas, sin depender del color) -->
<details class="tarjeta mb-3">
    <summary class="fw-semibold">Ver los datos de las gráficas en tabla</summary>
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="table-responsive tabla-datos">
                <table class="table tabla-ess">
                    <thead><tr><th><?= $porDia ? 'Día' : 'Mes' ?></th><th class="text-end">Servicios</th><th class="text-end">Ingresos</th><th class="text-end">Tiempo</th></tr></thead>
                    <tbody>
                    <?php foreach ($series['etiquetas'] as $i => $etiqueta): ?>
                        <tr>
                            <td><?= e($etiqueta) ?></td>
                            <td class="text-end"><?= (int) $series['servicios'][$i] ?></td>
                            <td class="text-end"><?= pesos($series['ingresos'][$i]) ?></td>
                            <td class="text-end"><?= $series['tiempo'][$i] === null ? '—' : (int) $series['tiempo'][$i] . ' min' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <table class="table tabla-ess">
                <thead><tr><th>Servicio</th><th class="text-end">Vendidos</th><th class="text-end">Facturado</th></tr></thead>
                <tbody>
                <?php foreach ($masVendidos as $s): ?>
                    <tr><td><?= e($s['nombre']) ?> · <?= e($s['tipo_vehiculo']) ?></td><td class="text-end"><?= (int) $s['cantidad'] ?></td><td class="text-end"><?= pesos($s['facturado']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <table class="table tabla-ess mt-3">
                <thead><tr><th>Lavador</th><th class="text-end">Servicios</th><th class="text-end">Facturado</th><th class="text-end">Tiempo prom.</th></tr></thead>
                <tbody>
                <?php foreach ($lavadores as $l): ?>
                    <tr><td><?= e($l['nombre']) ?></td><td class="text-end"><?= (int) $l['servicios'] ?></td><td class="text-end"><?= pesos($l['facturado']) ?></td>
                        <td class="text-end"><?= $l['tiempo'] === null ? '—' : (int) round($l['tiempo']) . ' min' ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>

<!-- Datos para Chart.js (JSON seguro dentro de HTML) -->
<script id="datosTablero" type="application/json">
<?= json_encode($graficas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
