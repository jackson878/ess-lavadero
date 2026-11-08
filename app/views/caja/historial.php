<?php
/**
 * Vista: historial de cajas (apertura, ventas y diferencia de cada día).
 *
 * @var array  $cajas
 * @var string $pestana
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <div class="ceja">Caja diaria</div>
    <h1 class="titulo-pagina">Historial de caja</h1>
</div>

<?php require VIEWS_PATH . 'caja/_pestanas.php'; ?>

<div class="tarjeta">
    <div class="table-responsive">
        <table class="table tabla-ess">
            <thead>
            <tr>
                <th>Fecha</th>
                <th class="text-end">Base</th>
                <th class="text-end">Ventas</th>
                <th class="text-end">Efectivo esperado</th>
                <th class="text-end">Contado</th>
                <th class="text-end">Diferencia</th>
                <th>Estado</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cajas as $c): ?>
                <?php $cerrada = $c['estado'] === 'cerrada'; ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= e(date('d/m/Y', strtotime($c['fecha']))) ?>
                        <div class="small text-secondary fw-normal"><?= e(ucfirst(DIAS_SEMANA[(int) date('w', strtotime($c['fecha']))])) ?></div>
                    </td>
                    <td class="text-end"><?= pesos($c['base_inicial']) ?></td>
                    <td class="text-end fw-semibold"><?= pesos($cerrada ? $c['total_ventas'] : $c['ventas_actuales']) ?></td>
                    <td class="text-end"><?= $cerrada ? pesos($c['efectivo_esperado']) : '—' ?></td>
                    <td class="text-end"><?= $cerrada ? pesos($c['efectivo_contado']) : '—' ?></td>
                    <td class="text-end fw-bold <?= !$cerrada ? '' : ((float) $c['diferencia'] < 0 ? 'text-danger' : ((float) $c['diferencia'] > 0 ? 'text-warning-emphasis' : 'text-success')) ?>">
                        <?= $cerrada ? pesos($c['diferencia']) : '—' ?>
                    </td>
                    <td>
                        <span class="rol <?= $cerrada ? 'rol-lavador' : 'rol-cajero' ?>"><?= $cerrada ? 'Cerrada' : 'Abierta' ?></span>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('caja', 'ver', ['id' => $c['id']]) ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
