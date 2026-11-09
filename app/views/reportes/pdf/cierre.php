<?php
/**
 * PDF: cierre de caja de un día, con espacio para firmas.
 *
 * @var string $titulo
 * @var array  $caja
 * @var array  $totales   [efectivo, transferencia, tarjeta, total, cantidad]
 * @var array  $pagos
 * @var float  $esperado
 * @var bool   $cerrada
 */
require VIEWS_PATH . 'reportes/pdf/_encabezado.php';
$dif = (float) $caja['diferencia'];
?>

<table class="tarjetas">
    <tr>
        <td><div class="etiqueta">Total ventas</div><div class="valor"><?= pesos($totales['total']) ?></div></td>
        <td><div class="etiqueta">Efectivo esperado</div><div class="valor"><?= pesos($esperado) ?></div></td>
        <td><div class="etiqueta">Efectivo contado</div><div class="valor"><?= $cerrada ? pesos($caja['efectivo_contado']) : '—' ?></div></td>
        <td>
            <div class="etiqueta">Diferencia</div>
            <div class="valor <?= $cerrada ? ($dif < 0 ? 'rojo' : ($dif == 0 ? 'verde' : '')) : '' ?>">
                <?= $cerrada ? pesos($dif) : '—' ?>
            </div>
        </td>
    </tr>
</table>

<h2>Resumen del día</h2>
<table class="datos">
    <tr><th>Concepto</th><th class="der">Valor</th></tr>
    <tr><td>Estado de la caja</td><td class="der"><?= $cerrada ? 'Cerrada' : 'Abierta (sin cerrar)' ?></td></tr>
    <tr><td>Apertura</td><td class="der"><?= e($caja['abrio']) ?> · <?= e(date('H:i', strtotime($caja['fecha_apertura']))) ?></td></tr>
    <tr><td>Cierre</td><td class="der"><?= $cerrada ? e($caja['cerro']) . ' · ' . e(date('H:i', strtotime($caja['fecha_cierre']))) : '—' ?></td></tr>
    <tr><td>Base inicial</td><td class="der"><?= pesos($caja['base_inicial']) ?></td></tr>
    <tr><td>+ Ventas en efectivo</td><td class="der"><?= pesos($totales['efectivo']) ?></td></tr>
    <tr class="total"><td>= Efectivo esperado en caja</td><td class="der"><?= pesos($esperado) ?></td></tr>
    <tr><td>Efectivo contado</td><td class="der"><?= $cerrada ? pesos($caja['efectivo_contado']) : '—' ?></td></tr>
    <tr class="total">
        <td>Diferencia <?= $cerrada ? ($dif < 0 ? '(faltante)' : ($dif > 0 ? '(sobrante)' : '(cuadra)')) : '' ?></td>
        <td class="der"><?= $cerrada ? pesos($dif) : '—' ?></td>
    </tr>
</table>

<h2>Ventas por medio de pago</h2>
<table class="datos">
    <tr><th>Medio de pago</th><th class="der">Valor</th></tr>
    <?php foreach (Caja::METODOS as $m): ?>
        <tr><td><?= e(metodo_texto($m)) ?></td><td class="der"><?= pesos($totales[$m]) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td>Total (<?= (int) $totales['cantidad'] ?> cobros)</td><td class="der"><?= pesos($totales['total']) ?></td></tr>
</table>
<p class="nota">Nequi/transferencias y tarjeta no entran a la caja física; por eso no suman al efectivo esperado.</p>

<?php if ($caja['observaciones']): ?>
    <h2>Observaciones</h2>
    <p><?= e($caja['observaciones']) ?></p>
<?php endif; ?>

<h2>Cobros del día</h2>
<table class="datos">
    <tr><th>Hora</th><th>Orden</th><th>Placa</th><th>Cliente</th><th>Medio</th><th>Referencia</th><th class="der">Valor</th></tr>
    <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= e(date('H:i', strtotime($p['fecha_hora']))) ?></td>
            <td>#<?= (int) $p['orden_id'] ?></td>
            <td><?= e(placa_formato($p['placa'])) ?></td>
            <td><?= e($p['cliente']) ?></td>
            <td><?= e(metodo_texto($p['metodo'])) ?></td>
            <td><?= e($p['referencia'] ?? '') ?></td>
            <td class="der"><?= pesos($p['valor']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$pagos): ?>
        <tr><td colspan="7" class="centro">No hubo cobros este día.</td></tr>
    <?php endif; ?>
</table>

<table class="firma">
    <tr>
        <td><div class="linea">Cajero</div></td>
        <td><div class="linea">Dueño / administrador</div></td>
    </tr>
</table>
</body>
</html>
