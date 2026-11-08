<?php
/**
 * Vista: formulario de vehículo. El dueño puede ser un cliente existente
 * o uno nuevo que se registra en el mismo paso.
 *
 * @var array    $datos
 * @var array    $errores
 * @var int|null $id
 * @var array    $clientes  lista para el <select>
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('vehiculo', 'editar', ['id' => $id]) : url('vehiculo', 'crear');
$volver   = $datos['cliente_id'] ? url('cliente', 'ver', ['id' => $datos['cliente_id']]) : url('vehiculo');
$esNuevo  = $datos['modo_cliente'] === 'nuevo';
?>

<div class="mb-4">
    <a href="<?= $volver ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar vehículo' : 'Nuevo vehículo' ?></h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
<?php endif; ?>

<div class="tarjeta formulario-ancho">
    <form method="post" action="<?= $accion ?>" novalidate>
        <?= Csrf::campo() ?>

        <h2 class="h6 fw-bold mb-3">Datos del vehículo</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label d-block">Tipo *</label>
                <div class="btn-group w-100" role="group" aria-label="Tipo de vehículo">
                    <?php foreach (Vehiculo::TIPOS as $tipo): ?>
                        <input type="radio" class="btn-check" name="tipo" id="tipo-<?= $tipo ?>" value="<?= $tipo ?>"
                               <?= $datos['tipo'] === $tipo ? 'checked' : '' ?>>
                        <label class="btn btn-ess-borde selector-tipo" for="tipo-<?= $tipo ?>">
                            <i class="bi <?= $tipo === 'moto' ? 'bi-scooter' : 'bi-car-front' ?>"></i> <?= ucfirst($tipo) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= isset($errores['tipo']) ? '<div class="text-danger small mt-1">' . e($errores['tipo']) . '</div>' : '' ?>
            </div>

            <div class="col-md-6">
                <label for="placa" class="form-label">Placa *</label>
                <input type="text" class="form-control campo-placa<?= campo_invalido($errores, 'placa') ?>" id="placa"
                       name="placa" value="<?= e($datos['placa']) ?>" maxlength="8" placeholder="ABC123"
                       autocomplete="off" required <?= $editando ? '' : 'autofocus' ?>
                       data-placa-aviso="#avisoPlaca"
                       data-excluir-id="<?= (int) $id ?>"
                       data-url="<?= url('vehiculo', 'buscar') ?>"
                       data-url-editar="<?= url('vehiculo', 'editar') ?>">
                <?= campo_error($errores, 'placa') ?>
                <div id="avisoPlaca"></div>
            </div>

            <div class="col-md-6">
                <label for="marca" class="form-label">Marca y línea</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'marca') ?>" id="marca"
                       name="marca" value="<?= e($datos['marca'] ?? '') ?>" maxlength="50" placeholder="Chevrolet Spark">
                <?= campo_error($errores, 'marca') ?>
            </div>

            <div class="col-md-6">
                <label for="color" class="form-label">Color</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'color') ?>" id="color"
                       name="color" value="<?= e($datos['color'] ?? '') ?>" maxlength="30" placeholder="Rojo">
                <?= campo_error($errores, 'color') ?>
            </div>
        </div>

        <hr class="my-4">

        <h2 class="h6 fw-bold mb-3">Dueño</h2>
        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="modo_cliente" id="modoExistente" value="existente"
                       data-mostrar="#bloqueExistente" data-ocultar="#bloqueNuevo" <?= $esNuevo ? '' : 'checked' ?>>
                <label class="form-check-label" for="modoExistente">Cliente registrado</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="modo_cliente" id="modoNuevo" value="nuevo"
                       data-mostrar="#bloqueNuevo" data-ocultar="#bloqueExistente" <?= $esNuevo ? 'checked' : '' ?>>
                <label class="form-check-label" for="modoNuevo">Cliente nuevo</label>
            </div>
        </div>

        <!-- Cliente existente -->
        <div id="bloqueExistente" <?= $esNuevo ? 'hidden' : '' ?>>
            <label for="cliente_id" class="form-label">Cliente *</label>
            <select class="form-select<?= campo_invalido($errores, 'cliente_id') ?>" id="cliente_id" name="cliente_id">
                <option value="0">Seleccione el cliente…</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $datos['cliente_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?> — <?= e(telefono_formato($c['telefono'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= campo_error($errores, 'cliente_id') ?>
        </div>

        <!-- Cliente nuevo (se crea junto con el vehículo) -->
        <div id="bloqueNuevo" class="row g-3" <?= $esNuevo ? '' : 'hidden' ?>>
            <div class="col-md-7">
                <label for="cliente_nombre" class="form-label">Nombre del cliente *</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'cliente_nombre') ?>"
                       id="cliente_nombre" name="cliente_nombre" value="<?= e($datos['cliente_nombre']) ?>" maxlength="100">
                <?= campo_error($errores, 'cliente_nombre') ?>
            </div>
            <div class="col-md-5">
                <label for="cliente_telefono" class="form-label">Celular *</label>
                <input type="tel" class="form-control<?= campo_invalido($errores, 'cliente_telefono') ?>"
                       id="cliente_telefono" name="cliente_telefono" value="<?= e($datos['cliente_telefono']) ?>"
                       maxlength="13" inputmode="numeric" placeholder="3001234567">
                <?= campo_error($errores, 'cliente_telefono') ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Registrar vehículo' ?></button>
            <a href="<?= $volver ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
