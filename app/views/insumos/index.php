<?php
/**
 * Vista: inventario de insumos con alerta visual de stock mínimo (RF-07).
 *
 * @var array $insumos
 * @var array $bajos   insumos activos por debajo del mínimo
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Inventario</div>
        <h1 class="titulo-pagina">Insumos</h1>
    </div>
    <a class="btn btn-ess" href="<?= url('insumo', 'crear') ?>">+ Nuevo insumo</a>
</div>

<?php if ($bajos): ?>
    <div class="aviso aviso-alerta mb-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= count($bajos) ?> <?= count($bajos) === 1 ? 'insumo está' : 'insumos están' ?> por debajo del stock mínimo:
        <?= e(implode(', ', array_column($bajos, 'nombre'))) ?>. Haga el pedido al proveedor.
    </div>
<?php else: ?>
    <div class="aviso aviso-ok mb-3"><i class="bi bi-check-circle"></i> Todos los insumos están sobre el stock mínimo.</div>
<?php endif; ?>

<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="tarjeta-titulo mb-0"><?= count($insumos) ?> insumos</h2>
        <div class="buscador">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar insumo" data-buscar="#tablaInsumos" aria-label="Buscar insumos">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tabla-ess" id="tablaInsumos">
            <thead>
            <tr>
                <th>Insumo</th>
                <th>Stock actual</th>
                <th class="text-end">Mínimo</th>
                <th class="text-end">Costo unitario</th>
                <th>Último movimiento</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($insumos as $i): ?>
                <?php
                $bajo = (int) $i['activo'] && (int) $i['bajo_minimo'];
                // Barra: qué tan lleno está respecto al doble del mínimo (100 % = 2 veces el mínimo)
                $referencia = max((float) $i['stock_minimo'] * 2, 0.01);
                $porcentaje = min(100, round((float) $i['stock_actual'] / $referencia * 100));
                ?>
                <tr class="<?= $bajo ? 'fila-alerta' : '' ?> <?= (int) $i['activo'] ? '' : 'fila-inactiva' ?>">
                    <td class="fw-semibold">
                        <a class="enlace-fila" href="<?= url('insumo', 'ver', ['id' => $i['id']]) ?>"><?= e($i['nombre']) ?></a>
                        <?php if (!(int) $i['activo']): ?><span class="small text-secondary">(inactivo)</span><?php endif; ?>
                    </td>
                    <td style="min-width: 180px">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold <?= $bajo ? 'text-danger' : '' ?>">
                                <?= e(cantidad_unidad($i['stock_actual'], $i['unidad'])) ?>
                            </span>
                            <?php if ($bajo): ?><span class="badge-bajo">Bajo mínimo</span><?php endif; ?>
                        </div>
                        <div class="pista mt-1">
                            <div class="pista-relleno <?= $bajo ? 'relleno-alerta' : '' ?>" style="width: <?= $porcentaje ?>%"></div>
                        </div>
                    </td>
                    <td class="text-end"><?= e(cantidad_formato($i['stock_minimo'])) ?></td>
                    <td class="text-end"><?= pesos($i['costo_unitario']) ?></td>
                    <td class="text-nowrap">
                        <?= $i['ultimo_movimiento'] ? e(date('d/m/Y', strtotime($i['ultimo_movimiento']))) : '<span class="text-secondary">—</span>' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('insumo', 'ver', ['id' => $i['id'], 'tipo' => 'entrada']) ?>" title="Registrar entrada">
                            <i class="bi bi-box-arrow-in-down"></i> Entrada
                        </a>
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('insumo', 'ver', ['id' => $i['id'], 'tipo' => 'salida']) ?>" title="Registrar salida">
                            <i class="bi bi-box-arrow-up"></i> Salida
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
