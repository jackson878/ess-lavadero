<?php
/**
 * Vista: caja de hoy.
 *   - Sin abrir   -> formulario de apertura con base inicial.
 *   - Abierta     -> totales por medio de pago, órdenes por cobrar y cobros del día.
 *   - Cerrada     -> resumen del cierre.
 *
 * @var array|null $caja       caja de hoy
 * @var array|null $anterior   caja de un día anterior que quedó abierta
 * @var array|null $totales
 * @var array      $pagos
 * @var array      $porCobrar
 * @var string     $pestana
 */
require VIEWS_PATH . 'layouts/header.php';
$abierta = $caja && $caja['estado'] === 'abierta';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Caja diaria</div>
        <h1 class="titulo-pagina">Hoy, <?= e(fecha_larga()) ?></h1>
    </div>
    <?php if ($abierta): ?>
        <a class="btn btn-ess btn-lg" href="<?= url('caja', 'cerrar') ?>"><i class="bi bi-lock"></i> Cerrar caja</a>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . 'caja/_pestanas.php'; ?>

<?php if ($anterior): ?>
    <div class="aviso aviso-alerta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>La caja del <?= e(date('d/m/Y', strtotime($anterior['fecha']))) ?> quedó abierta. Ciérrela antes de abrir la de hoy.</span>
        <a class="btn btn-sm btn-ess" href="<?= url('caja', 'cerrar', ['id' => $anterior['id']]) ?>">Cerrar esa caja</a>
    </div>
<?php endif; ?>

<?php if (!$caja): ?>
    <!-- Apertura -->
    <div class="tarjeta formulario-ancho">
        <h2 class="tarjeta-titulo">Abrir la caja</h2>
        <p class="text-secondary">Cuente el dinero con el que empieza el día (base para dar vueltas) y escríbalo aquí.</p>
        <form method="post" action="<?= url('caja', 'abrir') ?>" class="row g-3 align-items-end">
            <?= Csrf::campo() ?>
            <div class="col-sm-6">
                <label for="base_inicial" class="form-label">Base inicial *</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control" id="base_inicial" name="base_inicial" value="100.000"
                           inputmode="numeric" data-formato-pesos required <?= $anterior ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="col-sm-6">
                <button type="submit" class="btn btn-ess btn-lg w-100" <?= $anterior ? 'disabled' : '' ?>>
                    <i class="bi bi-unlock"></i> Abrir caja
                </button>
            </div>
        </form>
        <?php if ($porCobrar): ?>
            <p class="small text-secondary mt-3 mb-0">
                Hay <?= count($porCobrar) ?> <?= count($porCobrar) === 1 ? 'orden terminada esperando' : 'órdenes terminadas esperando' ?> cobro.
            </p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Totales del día -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="tarjeta">
                <div class="kpi-etiqueta">Ventas del día</div>
                <div class="kpi-valor"><?= pesos($totales['total']) ?></div>
                <div class="kpi-nota neutra"><?= (int) $totales['cantidad'] ?> <?= (int) $totales['cantidad'] === 1 ? 'cobro' : 'cobros' ?></div>
            </div>
        </div>
        <?php foreach (Caja::METODOS as $m): ?>
            <div class="col-6 col-xl-3">
                <div class="tarjeta">
                    <div class="kpi-etiqueta"><i class="bi <?= metodo_icono($m) ?>"></i> <?= e(metodo_texto($m)) ?></div>
                    <div class="kpi-valor"><?= pesos($totales[$m]) ?></div>
                    <?php if ($m === 'efectivo'): ?>
                        <div class="kpi-nota neutra">En caja: <?= pesos((float) $caja['base_inicial'] + $totales['efectivo']) ?> (con base)</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$abierta): ?>
        <div class="aviso aviso-ok mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>La caja de hoy ya se cerró a las <?= e(date('H:i', strtotime($caja['fecha_cierre']))) ?>.
                Diferencia: <?= pesos($caja['diferencia']) ?>.</span>
            <a class="btn btn-sm btn-ess-borde" href="<?= url('caja', 'ver', ['id' => $caja['id']]) ?>">Ver cierre</a>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Órdenes por cobrar -->
        <div class="col-lg-5">
            <div class="tarjeta">
                <h2 class="tarjeta-titulo">Por cobrar <span class="text-secondary fw-normal">(<?= count($porCobrar) ?>)</span></h2>
                <?php if (!$porCobrar): ?>
                    <p class="text-secondary mb-0">No hay vehículos terminados pendientes de cobro.</p>
                <?php endif; ?>
                <?php foreach ($porCobrar as $o): ?>
                    <div class="fila-cobro">
                        <div>
                            <div class="placa"><?= e(placa_formato($o['placa'])) ?></div>
                            <div class="small text-secondary"><?= e($o['servicios']) ?> · <?= e($o['cliente']) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?= pesos($o['total']) ?></div>
                            <?php if ($abierta): ?>
                                <a class="btn btn-sm btn-acento mt-1" href="<?= url('caja', 'cobrar', ['id' => $o['id']]) ?>">Cobrar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cobros registrados -->
        <div class="col-lg-7">
            <div class="tarjeta">
                <h2 class="tarjeta-titulo">Cobros de hoy</h2>
                <?php if (!$pagos): ?>
                    <p class="text-secondary mb-0">Aún no hay cobros. Base inicial: <?= pesos($caja['base_inicial']) ?>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table tabla-ess">
                            <thead>
                            <tr><th>Hora</th><th>Placa</th><th>Medio</th><th class="text-end">Valor</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <td><?= e(date('H:i', strtotime($p['fecha_hora']))) ?></td>
                                    <td>
                                        <a class="placa text-reset text-decoration-none" href="<?= url('orden', 'ver', ['id' => $p['orden_id']]) ?>"><?= e(placa_formato($p['placa'])) ?></a>
                                        <div class="small text-secondary"><?= e($p['cliente']) ?></div>
                                    </td>
                                    <td class="text-nowrap">
                                        <i class="bi <?= metodo_icono($p['metodo']) ?>"></i> <?= e(metodo_texto($p['metodo'])) ?>
                                        <?php if ($p['referencia']): ?><div class="small text-secondary">Ref. <?= e($p['referencia']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold text-nowrap"><?= pesos($p['valor']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
