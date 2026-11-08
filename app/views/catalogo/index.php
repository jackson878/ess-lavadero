<?php
/**
 * Vista: catálogo de servicios (solo Dueño).
 *
 * @var array $servicios
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Administración</div>
        <h1 class="titulo-pagina">Catálogo de servicios</h1>
    </div>
    <a class="btn btn-ess" href="<?= url('catalogo', 'crear') ?>">+ Nuevo servicio</a>
</div>

<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="tarjeta-titulo mb-0"><?= count($servicios) ?> servicios</h2>
        <div class="buscador">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar servicio, carro o moto"
                   data-buscar="#tablaServicios" aria-label="Buscar servicios">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tabla-ess" id="tablaServicios">
            <thead>
            <tr>
                <th>Servicio</th>
                <th>Vehículo</th>
                <th class="text-end">Precio</th>
                <th>Duración</th>
                <th class="text-end">Vendidos (30 días)</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
                <tr class="<?= (int) $s['activo'] ? '' : 'fila-inactiva' ?>">
                    <td>
                        <div class="fw-semibold"><?= e($s['nombre']) ?></div>
                        <?php if ($s['descripcion']): ?>
                            <div class="small text-secondary"><?= e($s['descripcion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= tipo_badge($s['tipo_vehiculo']) ?></td>
                    <td class="text-end fw-semibold text-nowrap"><?= pesos($s['precio']) ?></td>
                    <td class="text-nowrap"><?= e(duracion_texto((int) $s['duracion_min'])) ?></td>
                    <td class="text-end"><?= (int) $s['vendidos_30'] ?></td>
                    <td class="text-nowrap">
                        <?php if ((int) $s['activo']): ?>
                            <span class="punto punto-activo"></span>Activo
                        <?php else: ?>
                            <span class="punto punto-inactivo"></span>Inactivo
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('catalogo', 'editar', ['id' => $s['id']]) ?>">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="post" action="<?= url('catalogo', 'estado') ?>" class="d-inline"
                              data-confirmar="¿<?= (int) $s['activo'] ? 'Desactivar' : 'Activar' ?> el servicio <?= e($s['nombre']) ?> (<?= e($s['tipo_vehiculo']) ?>)?">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-ess-borde <?= (int) $s['activo'] ? 'text-danger' : 'text-success' ?>">
                                <?= (int) $s['activo'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
