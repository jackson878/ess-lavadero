<?php
/**
 * PDF: servicios por lavador.
 *
 * @var string $titulo
 * @var array  $lavadores  resumen por lavador
 * @var array  $detalle    servicios de cada lavador
 * @var int    $totalServ
 * @var float  $totalFact
 */
require VIEWS_PATH . 'reportes/pdf/_encabezado.php';
?>

<table class="tarjetas">
    <tr>
        <td><div class="etiqueta">Servicios</div><div class="valor"><?= (int) $totalServ ?></div></td>
        <td><div class="etiqueta">Facturado</div><div class="valor"><?= pesos($totalFact) ?></div></td>
        <td><div class="etiqueta">Lavadores</div><div class="valor"><?= count($lavadores) ?></div></td>
        <td><div class="etiqueta">Promedio por lavador</div><div class="valor"><?= number_format(count($lavadores) ? $totalServ / count($lavadores) : 0, 1, ',', '.') ?></div></td>
    </tr>
</table>

<h2>Resumen por lavador</h2>
<table class="datos">
    <tr><th>Lavador</th><th class="der">Servicios</th><th class="der">Carros</th><th class="der">Motos</th>
        <th class="der">Facturado</th><th class="der">Tiempo promedio</th><th class="der">% del total</th></tr>
    <?php foreach ($lavadores as $l): ?>
        <tr>
            <td><?= e($l['nombre']) ?></td>
            <td class="der"><?= (int) $l['servicios'] ?></td>
            <td class="der"><?= (int) $l['carros'] ?></td>
            <td class="der"><?= (int) $l['motos'] ?></td>
            <td class="der"><?= pesos($l['facturado']) ?></td>
            <td class="der"><?= $l['tiempo'] === null ? '—' : (int) round($l['tiempo']) . ' min' ?></td>
            <td class="der"><?= number_format($totalServ ? $l['servicios'] / $totalServ * 100 : 0, 1, ',', '.') ?> %</td>
        </tr>
    <?php endforeach; ?>
    <tr class="total"><td>Total</td><td class="der"><?= (int) $totalServ ?></td><td></td><td></td>
        <td class="der"><?= pesos($totalFact) ?></td><td></td><td class="der">100 %</td></tr>
</table>
<p class="nota">Tiempo promedio: minutos entre "en espera" y "terminado". Meta del negocio: <?= META_TIEMPO_ATENCION_MIN ?> min.
    Facturado: valor de las órdenes asignadas (cobradas o no).</p>

<h2>Detalle por servicio</h2>
<table class="datos">
    <tr><th>Lavador</th><th>Servicio</th><th>Vehículo</th><th class="der">Cantidad</th><th class="der">Facturado</th></tr>
    <?php foreach ($detalle as $d): ?>
        <tr>
            <td><?= e($d['lavador']) ?></td>
            <td><?= e($d['servicio']) ?></td>
            <td><?= e(ucfirst($d['tipo_vehiculo'])) ?></td>
            <td class="der"><?= (int) $d['cantidad'] ?></td>
            <td class="der"><?= pesos($d['facturado']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$detalle): ?>
        <tr><td colspan="5" class="centro">No hay servicios en este periodo.</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
