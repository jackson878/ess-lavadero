<?php
/**
 * Vista: detalle de una caja (apertura, cierre y cobros).
 *
 * @var array $caja
 * @var array $totales
 * @var array $pagos
 */
require VIEWS_PATH . 'layouts/header.php';

$cerrada    = $caja['estado'] === 'cerrada';
$diferencia = (float) $caja['diferencia'];
?>

<div class="mb-4">
    <a href="<?= url('caja', 'historial') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Historial de caja</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="ceja">Caja <?= $cerrada ? 'cerrada' : 'abierta' ?></div>
            <h1 class="titulo-pagina"><?= e(ucfirst(fecha_larga($caja['fecha']))) ?> de <?= e(date('Y', strtotime($caja['fecha']))) ?></h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (($usuarioActual['rol'] ?? '') === 'dueno'): ?>
                <!-- Reporte de cierre (RF-08): solo el Dueño ve los reportes -->
                <a class="btn btn-ess-borde" target="_blank" href="<?= url('reporte', 'cierre', ['id' => $caja['id'], 'formato' => 'pdf']) ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <a class="btn btn-ess-borde" href="<?= url('reporte', 'cierre', ['id' => $caja['id'], 'formato' => 'excel']) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            <?php endif; ?>
            <?php if (!$cerrada): ?>
                <a class="btn btn-ess" href="<?= url('caja', 'cerrar', ['id' => $caja['id']]) ?>"><i class="bi bi-lock"></i> Cerrar caja</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Ventas</div>
            <div class="kpi-valor"><?= pesos($totales['total']) ?></div>
            <div class="kpi-nota neutra"><?= (int) $totales['cantidad'] ?> <?= (int) $totales['cantidad'] === 1 ? 'cobro' : 'cobros' ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Efectivo esperado</div>
            <div class="kpi-valor"><?= pesos($cerrada ? $caja['efectivo_esperado'] : (float) $caja['base_inicial'] + $totales['efectivo']) ?></div>
            <div class="kpi-nota neutra">Base <?= pesos($caja['base_inicial']) ?> + efectivo <?= pesos($totales['efectivo']) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Efectivo contado</div>
            <div class="kpi-valor"><?= $cerrada ? pesos($caja['efectivo_contado']) : '—' ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Diferencia</div>
            <?php if ($cerrada): ?>
                <div class="kpi-valor <?= $diferencia < 0 ? 'text-danger' : ($diferencia > 0 ? 'text-warning-emphasis' : 'text-success') ?>">
                    <?= pesos($diferencia) ?>
                </div>
                <div class="kpi-nota neutra"><?= $diferencia < 0 ? 'Faltante' : ($diferencia > 0 ? 'Sobrante' : 'Cuadró exacto') ?></div>
            <?php else: ?>
                <div class="kpi-valor">—</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Resumen</h2>
            <dl class="row mb-0 datos">
                <?php foreach (Caja::METODOS as $m): ?>
                    <dt class="col-7"><i class="bi <?= metodo_icono($m) ?>"></i> <?= e(metodo_texto($m)) ?></dt>
                    <dd class="col-5 text-end"><?= pesos($totales[$m]) ?></dd>
                <?php endforeach; ?>
                <dt class="col-5">Abrió</dt>
                <dd class="col-7 text-end"><?= e($caja['abrio']) ?> · <?= e(date('H:i', strtotime($caja['fecha_apertura']))) ?></dd>
                <?php if ($cerrada): ?>
                    <dt class="col-5">Cerró</dt>
                    <dd class="col-7 text-end"><?= e($caja['cerro']) ?> · <?= e(date('H:i', strtotime($caja['fecha_cierre']))) ?></dd>
                <?php endif; ?>
            </dl>
            <?php if ($caja['observaciones']): ?>
                <div class="aviso aviso-alerta mt-2"><?= e($caja['observaciones']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Cobros (<?= count($pagos) ?>)</h2>
            <div class="table-responsive">
                <table class="table tabla-ess">
                    <thead><tr><th>Hora</th><th>Orden</th><th>Placa</th><th>Medio</th><th class="text-end">Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= e(date('H:i', strtotime($p['fecha_hora']))) ?></td>
                            <td><a href="<?= url('orden', 'ver', ['id' => $p['orden_id']]) ?>">#<?= (int) $p['orden_id'] ?></a></td>
                            <td class="placa"><?= e(placa_formato($p['placa'])) ?></td>
                            <td class="text-nowrap"><i class="bi <?= metodo_icono($p['metodo']) ?>"></i> <?= e(metodo_texto($p['metodo'])) ?></td>
                            <td class="text-end fw-semibold"><?= pesos($p['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
