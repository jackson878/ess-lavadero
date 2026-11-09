<?php
/**
 * Vista: ficha del insumo con registro de entradas/salidas e historial.
 *
 * @var array $insumo
 * @var array $movimientos
 * @var array $mov       valores del formulario de movimiento
 * @var array $errores
 */
require VIEWS_PATH . 'layouts/header.php';
$bajo = (int) $insumo['bajo_minimo'] && (int) $insumo['activo'];
?>

<div class="mb-4">
    <a href="<?= url('insumo') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a insumos</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="ceja">Insumo</div>
            <h1 class="titulo-pagina"><?= e($insumo['nombre']) ?></h1>
        </div>
        <a class="btn btn-ess-borde" href="<?= url('insumo', 'editar', ['id' => $insumo['id']]) ?>"><i class="bi bi-pencil"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="tarjeta <?= $bajo ? 'tarjeta-alerta' : '' ?>">
            <div class="kpi-etiqueta">Stock actual</div>
            <div class="kpi-valor <?= $bajo ? 'text-danger' : '' ?>"><?= e(cantidad_unidad($insumo['stock_actual'], $insumo['unidad'])) ?></div>
            <div class="kpi-nota <?= $bajo ? 'baja' : 'sube' ?>"><?= $bajo ? '▼ Por debajo del mínimo' : '✓ Sobre el mínimo' ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Stock mínimo</div>
            <div class="kpi-valor"><?= e(cantidad_unidad($insumo['stock_minimo'], $insumo['unidad'])) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Valor en inventario</div>
            <div class="kpi-valor"><?= pesos((float) $insumo['stock_actual'] * (float) $insumo['costo_unitario']) ?></div>
            <div class="kpi-nota neutra"><?= pesos($insumo['costo_unitario']) ?> por <?= e($insumo['unidad']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Registrar movimiento -->
    <div class="col-lg-5">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Registrar movimiento</h2>
            <form method="post" action="<?= url('insumo', 'movimiento') ?>" novalidate>
                <?= Csrf::campo() ?>
                <input type="hidden" name="insumo_id" value="<?= (int) $insumo['id'] ?>">

                <div class="btn-group w-100 mb-3" role="group">
                    <input type="radio" class="btn-check" name="tipo" id="tipo-entrada" value="entrada" <?= $mov['tipo'] !== 'salida' ? 'checked' : '' ?>>
                    <label class="btn btn-ess-borde selector-tipo" for="tipo-entrada"><i class="bi bi-box-arrow-in-down"></i> Entrada (compra)</label>
                    <input type="radio" class="btn-check" name="tipo" id="tipo-salida" value="salida" <?= $mov['tipo'] === 'salida' ? 'checked' : '' ?>>
                    <label class="btn btn-ess-borde selector-tipo" for="tipo-salida"><i class="bi bi-box-arrow-up"></i> Salida (consumo)</label>
                </div>

                <label for="cantidad" class="form-label">Cantidad (<?= e($insumo['unidad']) ?>) *</label>
                <input type="text" class="form-control form-control-lg<?= campo_invalido($errores, 'cantidad') ?>" id="cantidad"
                       name="cantidad" value="<?= e($mov['cantidad']) ?>" inputmode="decimal" placeholder="Ej.: 5 o 1,5" autofocus required>
                <?= campo_error($errores, 'cantidad') ?>

                <label for="motivo" class="form-label mt-3">Motivo</label>
                <input type="text" class="form-control" id="motivo" name="motivo" value="<?= e($mov['motivo']) ?>" maxlength="150"
                       placeholder="Compra a proveedor / consumo de la semana">

                <button type="submit" class="btn btn-ess w-100 mt-3">Registrar</button>
            </form>
        </div>
    </div>

    <!-- Historial -->
    <div class="col-lg-7">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Movimientos</h2>
            <?php if (!$movimientos): ?>
                <p class="text-secondary mb-0">Sin movimientos registrados.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla-ess">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th><th>Motivo</th><th>Usuario</th></tr></thead>
                        <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($m['fecha_hora']))) ?></td>
                                <td>
                                    <?php if ($m['tipo'] === 'entrada'): ?>
                                        <span class="text-success fw-semibold"><i class="bi bi-arrow-down-circle"></i> Entrada</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold"><i class="bi bi-arrow-up-circle"></i> Salida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold"><?= $m['tipo'] === 'entrada' ? '+' : '−' ?><?= e(cantidad_formato($m['cantidad'])) ?></td>
                                <td><?= e($m['motivo'] ?? '—') ?></td>
                                <td><?= e(explode(' ', $m['usuario'])[0]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
