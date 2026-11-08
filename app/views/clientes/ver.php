<?php
/**
 * Vista: ficha del cliente con sus vehículos y últimas visitas.
 *
 * @var array $cliente
 * @var array $vehiculos
 * @var array $resumen   [visitas, total_pagado, ultima_visita]
 * @var array $ordenes
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <a href="<?= url('cliente') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a clientes</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="ceja">Cliente</div>
            <h1 class="titulo-pagina"><?= e($cliente['nombre']) ?></h1>
            <p class="text-secondary mb-0 mt-1">
                <i class="bi bi-telephone"></i> <?= e(telefono_formato($cliente['telefono'])) ?>
                · Cliente desde <?= e(date('d/m/Y', strtotime($cliente['creado_en']))) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-ess-borde" href="<?= url('cliente', 'editar', ['id' => $cliente['id']]) ?>">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a class="btn btn-ess" href="<?= url('vehiculo', 'crear', ['cliente_id' => $cliente['id']]) ?>">
                + Agregar vehículo
            </a>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Visitas</div>
            <div class="kpi-valor"><?= (int) $resumen['visitas'] ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Total pagado</div>
            <div class="kpi-valor"><?= pesos($resumen['total_pagado']) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="tarjeta">
            <div class="kpi-etiqueta">Última visita</div>
            <div class="kpi-valor">
                <?= $resumen['ultima_visita'] ? e(date('d/m/Y', strtotime($resumen['ultima_visita']))) : '—' ?>
            </div>
        </div>
    </div>
</div>

<!-- Vehículos -->
<div class="tarjeta mb-3">
    <h2 class="tarjeta-titulo">Vehículos (<?= count($vehiculos) ?>)</h2>
    <?php if (!$vehiculos): ?>
        <p class="text-secondary mb-0">
            Este cliente aún no tiene vehículos.
            <a href="<?= url('vehiculo', 'crear', ['cliente_id' => $cliente['id']]) ?>">Agregar el primero</a>.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-ess">
                <thead>
                <tr>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Color</th>
                    <th>Visitas</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($vehiculos as $v): ?>
                    <tr>
                        <td class="placa"><?= e(placa_formato($v['placa'])) ?></td>
                        <td><?= tipo_badge($v['tipo']) ?></td>
                        <td><?= e($v['marca'] ?? '—') ?></td>
                        <td><?= e($v['color'] ?? '—') ?></td>
                        <td><?= (int) $v['visitas'] ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-ess-borde" href="<?= url('vehiculo', 'editar', ['id' => $v['id']]) ?>">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if ((int) $v['visitas'] === 0): ?>
                                <form method="post" action="<?= url('vehiculo', 'eliminar') ?>" class="d-inline"
                                      data-confirmar="¿Eliminar el vehículo <?= e(placa_formato($v['placa'])) ?>?">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-ess-borde text-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Últimas visitas -->
<div class="tarjeta mb-3">
    <h2 class="tarjeta-titulo">Últimas visitas</h2>
    <?php if (!$ordenes): ?>
        <p class="text-secondary mb-0">Sin visitas registradas.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-ess">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Placa</th>
                    <th>Servicios</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ordenes as $o): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($o['fecha_ingreso']))) ?></td>
                        <td class="placa"><?= e(placa_formato($o['placa'])) ?></td>
                        <td><?= e($o['servicios']) ?></td>
                        <td class="text-end text-nowrap"><?= pesos($o['total']) ?></td>
                        <td><?= estado_badge($o['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Eliminar: solo si no tiene vehículos -->
<?php if (!$vehiculos): ?>
    <form method="post" action="<?= url('cliente', 'eliminar') ?>"
          data-confirmar="¿Eliminar al cliente <?= e($cliente['nombre']) ?>? Esta acción no se puede deshacer.">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
        <button type="submit" class="btn btn-ess-borde text-danger">
            <i class="bi bi-trash"></i> Eliminar cliente
        </button>
    </form>
<?php endif; ?>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
