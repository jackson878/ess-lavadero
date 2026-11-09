<?php
/**
 * Vista: reportes en PDF/Excel (RF-08) y respaldos de la base de datos (RNF-06).
 *
 * @var array  $cajas         últimas cajas (para el reporte de cierre)
 * @var array  $respaldos     respaldos existentes en /backups
 * @var bool   $hayLibrerias  ¿está instalado Composer (Dompdf / PhpSpreadsheet)?
 * @var string $hoy
 * @var string $hace30
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <div class="ceja">Administración</div>
    <h1 class="titulo-pagina">Reportes y respaldos</h1>
</div>

<?php if (!$hayLibrerias): ?>
    <div class="alert alerta-error">
        Faltan las librerías de reportes. En la carpeta del proyecto ejecute: <code>composer install</code>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <!-- Reporte 1: ventas -->
    <div class="col-lg-4">
        <form class="tarjeta" method="get" action="<?= url() ?>" target="_blank">
            <input type="hidden" name="c" value="reporte">
            <input type="hidden" name="a" value="ventas">
            <div class="reporte-icono"><i class="bi bi-graph-up-arrow"></i></div>
            <h2 class="tarjeta-titulo mb-1">Ventas por rango de fechas</h2>
            <p class="text-secondary small">Cobros recibidos: resumen por día y medio de pago, y el detalle de cada cobro.</p>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small" for="v-desde">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="v-desde" name="desde" value="<?= e($hace30) ?>" max="<?= e($hoy) ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label small" for="v-hasta">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="v-hasta" name="hasta" value="<?= e($hoy) ?>" max="<?= e($hoy) ?>" required>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="formato" value="pdf" class="btn btn-ess flex-fill"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button type="submit" name="formato" value="excel" class="btn btn-ess-borde flex-fill"><i class="bi bi-file-earmark-excel"></i> Excel</button>
            </div>
        </form>
    </div>

    <!-- Reporte 2: cierre de caja -->
    <div class="col-lg-4">
        <form class="tarjeta" method="get" action="<?= url() ?>" target="_blank">
            <input type="hidden" name="c" value="reporte">
            <input type="hidden" name="a" value="cierre">
            <div class="reporte-icono"><i class="bi bi-cash-stack"></i></div>
            <h2 class="tarjeta-titulo mb-1">Cierre de caja</h2>
            <p class="text-secondary small">Base, ventas por medio de pago, efectivo esperado vs. contado, diferencia y cobros del día.</p>
            <label class="form-label small" for="caja-id">Día</label>
            <select class="form-select form-select-sm mb-3" id="caja-id" name="id" required>
                <?php if (!$cajas): ?><option value="">No hay cajas registradas</option><?php endif; ?>
                <?php foreach ($cajas as $c): ?>
                    <option value="<?= (int) $c['id'] ?>">
                        <?= e(date('d/m/Y', strtotime($c['fecha']))) ?> · <?= e(ucfirst(DIAS_SEMANA[(int) date('w', strtotime($c['fecha']))])) ?>
                        <?= $c['estado'] === 'abierta' ? '(abierta)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="d-flex gap-2">
                <button type="submit" name="formato" value="pdf" class="btn btn-ess flex-fill" <?= $cajas ? '' : 'disabled' ?>><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button type="submit" name="formato" value="excel" class="btn btn-ess-borde flex-fill" <?= $cajas ? '' : 'disabled' ?>><i class="bi bi-file-earmark-excel"></i> Excel</button>
            </div>
        </form>
    </div>

    <!-- Reporte 3: servicios por lavador -->
    <div class="col-lg-4">
        <form class="tarjeta" method="get" action="<?= url() ?>" target="_blank">
            <input type="hidden" name="c" value="reporte">
            <input type="hidden" name="a" value="lavadores">
            <div class="reporte-icono"><i class="bi bi-people"></i></div>
            <h2 class="tarjeta-titulo mb-1">Servicios por lavador</h2>
            <p class="text-secondary small">Servicios, carros y motos, lo facturado y el tiempo promedio de cada lavador.</p>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small" for="l-desde">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="l-desde" name="desde" value="<?= e($hace30) ?>" max="<?= e($hoy) ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label small" for="l-hasta">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="l-hasta" name="hasta" value="<?= e($hoy) ?>" max="<?= e($hoy) ?>" required>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="formato" value="pdf" class="btn btn-ess flex-fill"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button type="submit" name="formato" value="excel" class="btn btn-ess-borde flex-fill"><i class="bi bi-file-earmark-excel"></i> Excel</button>
            </div>
        </form>
    </div>
</div>

<!-- Respaldo de la base de datos (RNF-06) -->
<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="tarjeta-titulo mb-1"><i class="bi bi-database-down"></i> Respaldo de la base de datos</h2>
            <p class="text-secondary small mb-0">
                Genera una copia completa (mysqldump) en la carpeta <code>backups/</code>. Descárguela y guárdela en otro lugar
                (USB o la nube). También puede ejecutar <code>database\respaldo.bat</code> o programarlo en Windows.
            </p>
        </div>
        <form method="post" action="<?= url('reporte', 'respaldar') ?>">
            <?= Csrf::campo() ?>
            <button type="submit" class="btn btn-ess"><i class="bi bi-database-add"></i> Crear respaldo ahora</button>
        </form>
    </div>

    <?php if (!$respaldos): ?>
        <p class="text-secondary mb-0">Aún no hay respaldos.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-ess">
                <thead><tr><th>Archivo</th><th>Fecha</th><th class="text-end">Tamaño</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($respaldos as $r): ?>
                    <tr>
                        <td><i class="bi bi-file-earmark-code"></i> <?= e($r['nombre']) ?></td>
                        <td><?= e($r['fecha']) ?></td>
                        <td class="text-end"><?= e($r['tamano']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-ess-borde" href="<?= url('reporte', 'descargar', ['archivo' => $r['nombre']]) ?>">
                                <i class="bi bi-download"></i> Descargar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
