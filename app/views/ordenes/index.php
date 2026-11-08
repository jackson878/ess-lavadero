<?php
/**
 * Vista: tablero de servicios (vehículos en el lavadero) + historial con filtros.
 *
 * @var array $activas    órdenes en espera, en lavado o terminadas sin entregar
 * @var array $historial  órdenes del rango filtrado
 * @var array $conteo     [estado => cantidad] del historial
 * @var array $filtros    [desde, hasta, estado]
 * @var array $estados
 */
require VIEWS_PATH . 'layouts/header.php';

// Se agrupan las órdenes activas por estado para las tres columnas
$columnas = ['en_espera' => [], 'en_lavado' => [], 'terminado' => []];
foreach ($activas as $o) {
    $columnas[$o['estado']][] = $o;
}
$esHoy = $filtros['desde'] === date('Y-m-d') && $filtros['hasta'] === date('Y-m-d');
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Servicios</div>
        <h1 class="titulo-pagina">Vehículos en el lavadero</h1>
    </div>
    <a class="btn btn-ess btn-lg" href="<?= url('orden', 'crear') ?>">+ Nuevo servicio</a>
</div>

<!-- Columnas por estado -->
<div class="row g-3 mb-4">
    <?php foreach ($columnas as $estado => $ordenes): ?>
        <div class="col-lg-4">
            <div class="columna-estado">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <?= estado_badge($estado) ?>
                    <span class="fw-bold text-secondary"><?= count($ordenes) ?></span>
                </div>
                <?php if (!$ordenes): ?>
                    <p class="text-secondary small text-center py-4 mb-0">Sin vehículos</p>
                <?php endif; ?>
                <?php foreach ($ordenes as $o): ?>
                    <?php require VIEWS_PATH . 'ordenes/_tarjeta.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Historial con filtros -->
<div class="tarjeta">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <h2 class="tarjeta-titulo mb-0">
            Historial <?= $esHoy ? 'de hoy' : '' ?>
            <span class="text-secondary fw-normal fs-6">(<?= count($historial) ?> órdenes)</span>
        </h2>
        <form class="d-flex flex-wrap gap-2 align-items-end" method="get" action="<?= url() ?>">
            <input type="hidden" name="c" value="orden">
            <div>
                <label class="form-label small mb-1" for="desde">Desde</label>
                <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
            </div>
            <div>
                <label class="form-label small mb-1" for="hasta">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
            </div>
            <div>
                <label class="form-label small mb-1" for="estado">Estado</label>
                <select class="form-select form-select-sm" id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= e($est) ?>" <?= $filtros['estado'] === $est ? 'selected' : '' ?>><?= e(estado_texto($est)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-ess">Filtrar</button>
            <?php if (!$esHoy || $filtros['estado']): ?>
                <a class="btn btn-sm btn-ess-borde" href="<?= url('orden') ?>">Hoy</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Contadores por estado -->
    <div class="d-flex flex-wrap gap-3 mb-3 small">
        <?php foreach ($conteo as $est => $n): ?>
            <?php if ($n): ?><span><?= estado_badge($est) ?> <strong><?= $n ?></strong></span><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (!$historial): ?>
        <p class="text-secondary mb-0">No hay órdenes en este periodo.</p>
    <?php else: ?>
        <div class="buscador mb-3">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Buscar placa, cliente o lavador"
                   data-buscar="#tablaHistorial" aria-label="Buscar en el historial">
        </div>
        <div class="table-responsive">
            <table class="table tabla-ess" id="tablaHistorial">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Ingreso</th>
                    <th>Placa</th>
                    <th>Cliente</th>
                    <th>Servicios</th>
                    <th>Lavador</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $o): ?>
                    <tr>
                        <td><a href="<?= url('orden', 'ver', ['id' => $o['id']]) ?>"><?= (int) $o['id'] ?></a></td>
                        <td class="text-nowrap"><?= e(date($esHoy ? 'H:i' : 'd/m H:i', strtotime($o['fecha_ingreso']))) ?></td>
                        <td class="placa"><?= e(placa_formato($o['placa'])) ?><span class="d-none"><?= e($o['placa']) ?></span></td>
                        <td><?= e($o['cliente']) ?></td>
                        <td><?= e($o['servicios']) ?></td>
                        <td><?= e(explode(' ', $o['lavador'])[0]) ?></td>
                        <td class="text-end text-nowrap"><?= pesos($o['total']) ?></td>
                        <td><?= estado_badge($o['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
