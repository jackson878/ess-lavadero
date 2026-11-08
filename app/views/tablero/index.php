<?php
/**
 * Vista: Tablero gerencial (solo Dueño).
 *
 * Variables que recibe de TableroController::index():
 *   $dias, $kpi (arreglo con los indicadores), $semana (datos de la gráfica),
 *   $masVendidos, $insumosBajos, $enLavadero
 *
 * @var int   $dias
 * @var array $kpi
 * @var array $semana
 * @var array $masVendidos
 * @var array $insumosBajos
 * @var array $enLavadero
 */
require VIEWS_PATH . 'layouts/header.php';

[$notaServicios, $claseServicios] = comparacion_kpi($kpi['servicios_hoy'], $kpi['servicios_semana_pasada'], 'cantidad');
[$notaIngresos, $claseIngresos]   = comparacion_kpi($kpi['ingresos_hoy'], $kpi['ingresos_semana_pasada'], 'porcentaje');
$maxVendido = $masVendidos ? max(array_column($masVendidos, 'cantidad')) : 1;
?>

<!-- Encabezado -->
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Tablero gerencial</div>
        <h1 class="titulo-pagina">Hoy, <?= e(fecha_larga()) ?></h1>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-ess-borde dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Últimos <?= (int) $dias ?> días
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ([7, 15, 30] as $opcion): ?>
                    <li>
                        <a class="dropdown-item <?= $opcion === $dias ? 'active' : '' ?>"
                           href="<?= url('tablero', 'index', ['dias' => $opcion]) ?>">Últimos <?= $opcion ?> días</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <a class="btn btn-ess" href="<?= url('orden', 'crear') ?>">+ Nuevo servicio</a>
    </div>
</div>

<!-- Fila 1: tarjetas de indicadores -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Servicios hoy</div>
            <div class="kpi-valor"><?= (int) $kpi['servicios_hoy'] ?></div>
            <div class="kpi-nota <?= $claseServicios ?>"><?= e($notaServicios) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Ingresos hoy</div>
            <div class="kpi-valor"><?= pesos($kpi['ingresos_hoy']) ?></div>
            <div class="kpi-nota <?= $claseIngresos ?>"><?= e($notaIngresos) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Ticket promedio</div>
            <div class="kpi-valor"><?= pesos($kpi['ticket']) ?></div>
            <div class="kpi-nota neutra">por servicio · últimos <?= (int) $dias ?> días</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Tiempo de atención</div>
            <?php if ($kpi['tiempo'] === null): ?>
                <div class="kpi-valor">—</div>
                <div class="kpi-nota neutra">Sin órdenes terminadas</div>
            <?php else:
                $exceso = (int) round($kpi['tiempo']) - META_TIEMPO_ATENCION_MIN; ?>
                <div class="kpi-valor"><?= (int) round($kpi['tiempo']) ?> min</div>
                <?php if ($exceso > 0): ?>
                    <div class="kpi-nota alerta">▲ <?= $exceso ?> min sobre la meta</div>
                <?php else: ?>
                    <div class="kpi-nota sube">▼ <?= abs($exceso) ?> min bajo la meta</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fila 2: gráfica de la semana y servicios más vendidos -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Ingresos de la semana</h2>
            <div class="grafica">
                <canvas id="graficaSemana" aria-label="Ingresos de los últimos 7 días" role="img"></canvas>
            </div>
            <p class="grafica-nota">
                <?php if ($semana['mejor'] !== null): ?>
                    Mejor día: <?= e(DIAS_SEMANA[$semana['mejor']['w']]) ?>, <?= pesos($semana['mejor']['valor']) ?> ·
                <?php endif; ?>
                Hoy va en <?= pesos($kpi['ingresos_hoy']) ?>
            </p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="tarjeta d-flex flex-column">
            <h2 class="tarjeta-titulo">Servicios más vendidos</h2>
            <?php if (!$masVendidos): ?>
                <p class="text-secondary">No hay servicios en este periodo.</p>
            <?php endif; ?>
            <div>
                <?php foreach ($masVendidos as $servicio): ?>
                    <div class="ranking-item">
                        <div class="ranking-fila">
                            <span><?= e($servicio['nombre']) ?> <?= e($servicio['tipo_vehiculo']) ?></span>
                            <strong><?= (int) $servicio['cantidad'] ?></strong>
                        </div>
                        <div class="pista">
                            <div class="pista-relleno"
                                 style="width: <?= round($servicio['cantidad'] / $maxVendido * 100) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-auto pt-4">
                <?php if ($insumosBajos): ?>
                    <div class="aviso aviso-alerta">
                        Alerta: <?= count($insumosBajos) ?>
                        <?= count($insumosBajos) === 1 ? 'insumo' : 'insumos' ?> bajo el mínimo:
                        <?= e(mb_strtolower(implode(', ', array_column($insumosBajos, 'nombre')))) ?>
                    </div>
                <?php else: ?>
                    <div class="aviso aviso-ok">Todos los insumos están sobre el stock mínimo.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Fila 3: vehículos que están ahora en el lavadero -->
<div class="tarjeta">
    <h2 class="tarjeta-titulo">Vehículos en el lavadero</h2>
    <?php if (!$enLavadero): ?>
        <p class="text-secondary mb-0">No hay vehículos en el lavadero en este momento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-ess">
                <thead>
                <tr>
                    <th>Placa</th>
                    <th>Servicio</th>
                    <th>Lavador</th>
                    <th>Llegada</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($enLavadero as $orden): ?>
                    <tr>
                        <td class="placa"><?= e(placa_formato($orden['placa'])) ?></td>
                        <td>
                            <?= e($orden['servicios']) ?>
                            <span class="text-secondary small">· <?= e($orden['tipo']) ?></span>
                        </td>
                        <td><?= e($orden['lavador'] ?: 'Sin asignar') ?></td>
                        <td><?= e(date('H:i', strtotime($orden['fecha_ingreso']))) ?></td>
                        <td><?= estado_badge($orden['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Datos de la gráfica para JavaScript (JSON seguro dentro de HTML) -->
<script id="datosSemana" type="application/json">
<?= json_encode($semana['grafica'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
