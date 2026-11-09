<?php
/**
 * PDF: ventas por rango de fechas (horizontal).
 *
 * @var string $titulo
 * @var array  $ventas     cada cobro
 * @var array  $porDia     totales por día y medio
 * @var array  $porMetodo  ['efectivo' => .., 'transferencia' => .., 'tarjeta' => ..]
 * @var float  $total
 */
require VIEWS_PATH . 'reportes/pdf/_encabezado.php';
?>

<table class="tarjetas">
    <tr>
        <td><div class="etiqueta">Total ventas</div><div class="valor"><?= pesos($total) ?></div></td>
        <td><div class="etiqueta">Cobros</div><div class="valor"><?= count($ventas) ?></div></td>
        <td><div class="etiqueta">Ticket promedio</div><div class="valor"><?= pesos(count($ventas) ? $total / count($ventas) : 0) ?></div></td>
        <td><div class="etiqueta">Días con ventas</div><div class="valor"><?= count($porDia) ?></div></td>
    </tr>
</table>

<h2>Por medio de pago</h2>
<table class="datos">
    <tr><th>Medio de pago</th><th class="der">Valor</th><th class="der">Participación</th></tr>
    <?php foreach ($porMetodo as $m => $valor): ?>
        <tr>
            <td><?= e(metodo_texto($m)) ?></td>
            <td class="der"><?= pesos($valor) ?></td>
            <td class="der"><?= number_format($total > 0 ? $valor / $total * 100 : 0, 1, ',', '.') ?> %</td>
        </tr>
    <?php endforeach; ?>
    <tr class="total"><td>Total</td><td class="der"><?= pesos($total) ?></td><td class="der">100 %</td></tr>
</table>

<h2>Resumen por día</h2>
<table class="datos">
    <tr><th>Fecha</th><th class="der">Cobros</th><th class="der">Efectivo</th><th class="der">Nequi / transferencia</th><th class="der">Tarjeta</th><th class="der">Total</th></tr>
    <?php foreach ($porDia as $d): ?>
        <tr>
            <td><?= e(ucfirst(DIAS_SEMANA[(int) date('w', strtotime($d['dia']))])) ?> <?= e(date('d/m/Y', strtotime($d['dia']))) ?></td>
            <td class="der"><?= (int) $d['cobros'] ?></td>
            <td class="der"><?= pesos($d['efectivo']) ?></td>
            <td class="der"><?= pesos($d['transferencia']) ?></td>
            <td class="der"><?= pesos($d['tarjeta']) ?></td>
            <td class="der"><?= pesos($d['total']) ?></td>
        </tr>
    <?php endforeach; ?>
    <tr class="total">
        <td>Total</td><td class="der"><?= count($ventas) ?></td>
        <td class="der"><?= pesos($porMetodo['efectivo']) ?></td><td class="der"><?= pesos($porMetodo['transferencia']) ?></td>
        <td class="der"><?= pesos($porMetodo['tarjeta']) ?></td><td class="der"><?= pesos($total) ?></td>
    </tr>
</table>

<h2>Detalle de cobros</h2>
<table class="datos">
    <tr><th>Fecha y hora</th><th>Orden</th><th>Placa</th><th>Cliente</th><th>Servicios</th><th>Lavador</th><th>Medio</th><th class="der">Valor</th></tr>
    <?php foreach ($ventas as $v): ?>
        <tr>
            <td><?= e(date('d/m/Y H:i', strtotime($v['fecha_hora']))) ?></td>
            <td>#<?= (int) $v['orden_id'] ?></td>
            <td><?= e(placa_formato($v['placa'])) ?></td>
            <td><?= e($v['cliente']) ?></td>
            <td><?= e($v['servicios']) ?></td>
            <td><?= e(explode(' ', $v['lavador'])[0]) ?></td>
            <td><?= e(metodo_texto($v['metodo'])) ?></td>
            <td class="der"><?= pesos($v['valor']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$ventas): ?>
        <tr><td colspan="8" class="centro">No hay cobros en este periodo.</td></tr>
    <?php endif; ?>
    <tr class="total"><td colspan="7">Total (<?= count($ventas) ?> cobros)</td><td class="der"><?= pesos($total) ?></td></tr>
</table>
</body>
</html>
