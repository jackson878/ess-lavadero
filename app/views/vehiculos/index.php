<?php
/**
 * Vista: lista de vehículos con consulta rápida por placa.
 *
 * @var array  $vehiculos
 * @var string $pestana
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Clientes y vehículos</div>
        <h1 class="titulo-pagina">Vehículos</h1>
    </div>
    <a class="btn btn-ess" href="<?= url('vehiculo', 'crear') ?>">+ Nuevo vehículo</a>
</div>

<?php require VIEWS_PATH . 'clientes/_pestanas.php'; ?>

<!-- Consulta rápida: al escribir la placa se muestra el vehículo y su dueño (RF-02) -->
<div class="tarjeta mb-3">
    <div class="row g-3 align-items-center">
        <div class="col-md-5">
            <label for="consultaPlaca" class="form-label">Consulta rápida por placa</label>
            <input type="text" class="form-control form-control-lg campo-placa" id="consultaPlaca"
                   placeholder="ABC123" maxlength="8" autocomplete="off"
                   data-placa-consulta="#resultadoPlaca"
                   data-url="<?= url('vehiculo', 'buscar') ?>"
                   data-url-cliente="<?= url('cliente', 'ver') ?>"
                   data-url-nuevo="<?= url('vehiculo', 'crear') ?>">
        </div>
        <div class="col-md-7">
            <div id="resultadoPlaca" class="resultado-placa text-secondary">
                Escriba una placa para ver de quién es el vehículo.
            </div>
        </div>
    </div>
</div>

<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="tarjeta-titulo mb-0"><?= count($vehiculos) ?> vehículos</h2>
        <div class="buscador">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar por placa, marca o dueño"
                   data-buscar="#tablaVehiculos" aria-label="Buscar vehículos">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table tabla-ess" id="tablaVehiculos">
            <thead>
            <tr>
                <th>Placa</th>
                <th>Tipo</th>
                <th>Marca / color</th>
                <th>Dueño</th>
                <th>Visitas</th>
                <th>Última visita</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($vehiculos as $v): ?>
                <tr>
                    <td class="placa">
                        <?= e(placa_formato($v['placa'])) ?><span class="d-none"><?= e($v['placa']) ?></span>
                    </td>
                    <td><?= tipo_badge($v['tipo']) ?></td>
                    <td><?= e(trim(($v['marca'] ?? '') . ' · ' . ($v['color'] ?? ''), ' ·')) ?: '—' ?></td>
                    <td>
                        <a class="enlace-fila" href="<?= url('cliente', 'ver', ['id' => $v['cliente_id']]) ?>"><?= e($v['cliente']) ?></a>
                        <div class="small text-secondary"><?= e(telefono_formato($v['telefono'])) ?></div>
                    </td>
                    <td><?= (int) $v['visitas'] ?></td>
                    <td class="text-nowrap">
                        <?= $v['ultima_visita'] ? e(date('d/m/Y', strtotime($v['ultima_visita']))) : '<span class="text-secondary">—</span>' ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-ess-borde" href="<?= url('vehiculo', 'editar', ['id' => $v['id']]) ?>">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
