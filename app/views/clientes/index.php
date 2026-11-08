<?php
/**
 * Vista: lista de clientes.
 *
 * @var array  $clientes
 * @var string $pestana
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Clientes y vehículos</div>
        <h1 class="titulo-pagina">Clientes</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-ess-borde" href="<?= url('vehiculo', 'crear') ?>">+ Vehículo</a>
        <a class="btn btn-ess" href="<?= url('cliente', 'crear') ?>">+ Nuevo cliente</a>
    </div>
</div>

<?php require VIEWS_PATH . 'clientes/_pestanas.php'; ?>

<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="tarjeta-titulo mb-0"><?= count($clientes) ?> clientes</h2>
        <div class="buscador">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar por nombre, teléfono o placa"
                   data-buscar="#tablaClientes" aria-label="Buscar clientes">
        </div>
    </div>

    <?php if (!$clientes): ?>
        <p class="text-secondary mb-0">Aún no hay clientes registrados.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla-ess" id="tablaClientes">
                <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Vehículos</th>
                    <th>Última visita</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td class="fw-semibold">
                            <a class="enlace-fila" href="<?= url('cliente', 'ver', ['id' => $c['id']]) ?>"><?= e($c['nombre']) ?></a>
                        </td>
                        <!-- El teléfono se escribe también sin espacios para que la búsqueda lo encuentre -->
                        <td class="text-nowrap">
                            <?= e(telefono_formato($c['telefono'])) ?>
                            <span class="d-none"><?= e($c['telefono']) ?></span>
                        </td>
                        <td>
                            <?php if ($c['placas']): ?>
                                <?php foreach (explode(', ', $c['placas']) as $placa): ?>
                                    <span class="placa-chip"><?= e(placa_formato($placa)) ?><span class="d-none"><?= e($placa) ?></span></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-secondary">Sin vehículos</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?= $c['ultima_visita'] ? e(date('d/m/Y', strtotime($c['ultima_visita']))) : '<span class="text-secondary">—</span>' ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-ess-borde" href="<?= url('cliente', 'ver', ['id' => $c['id']]) ?>">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                            <a class="btn btn-sm btn-ess-borde" href="<?= url('cliente', 'editar', ['id' => $c['id']]) ?>">
                                <i class="bi bi-pencil"></i> Editar
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
