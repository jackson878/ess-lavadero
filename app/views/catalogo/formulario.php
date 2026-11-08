<?php
/**
 * Vista: formulario de servicio del catálogo (solo Dueño).
 *
 * @var array    $datos
 * @var array    $errores
 * @var int|null $id
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('catalogo', 'editar', ['id' => $id]) : url('catalogo', 'crear');
$precio   = (int) $datos['precio'] > 0 ? number_format((float) $datos['precio'], 0, ',', '.') : '';
?>

<div class="mb-4">
    <a href="<?= url('catalogo') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver al catálogo</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar servicio' : 'Nuevo servicio' ?></h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
<?php endif; ?>

<div class="tarjeta formulario-ancho">
    <form method="post" action="<?= $accion ?>" novalidate>
        <?= Csrf::campo() ?>

        <div class="row g-3">
            <div class="col-md-7">
                <label for="nombre" class="form-label">Nombre del servicio *</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'nombre') ?>" id="nombre"
                       name="nombre" value="<?= e($datos['nombre']) ?>" maxlength="100"
                       placeholder="Lavado completo" required>
                <?= campo_error($errores, 'nombre') ?>
            </div>

            <div class="col-md-5">
                <label class="form-label d-block">Tipo de vehículo *</label>
                <div class="btn-group w-100" role="group">
                    <?php foreach (Vehiculo::TIPOS as $tipo): ?>
                        <input type="radio" class="btn-check" name="tipo_vehiculo" id="tipo-<?= $tipo ?>" value="<?= $tipo ?>"
                               <?= $datos['tipo_vehiculo'] === $tipo ? 'checked' : '' ?>>
                        <label class="btn btn-ess-borde selector-tipo" for="tipo-<?= $tipo ?>">
                            <i class="bi <?= $tipo === 'moto' ? 'bi-scooter' : 'bi-car-front' ?>"></i> <?= ucfirst($tipo) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= isset($errores['tipo_vehiculo']) ? '<div class="text-danger small mt-1">' . e($errores['tipo_vehiculo']) . '</div>' : '' ?>
            </div>

            <div class="col-md-6">
                <label for="precio" class="form-label">Precio *</label>
                <div class="input-group has-validation">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control<?= campo_invalido($errores, 'precio') ?>" id="precio"
                           name="precio" value="<?= e($precio) ?>" inputmode="numeric" placeholder="25.000"
                           data-formato-pesos required>
                    <?= campo_error($errores, 'precio') ?>
                </div>
            </div>

            <div class="col-md-6">
                <label for="duracion_min" class="form-label">Duración estimada *</label>
                <div class="input-group has-validation">
                    <input type="number" class="form-control<?= campo_invalido($errores, 'duracion_min') ?>"
                           id="duracion_min" name="duracion_min" value="<?= (int) $datos['duracion_min'] ?>"
                           min="5" max="480" step="5" required>
                    <span class="input-group-text">minutos</span>
                    <?= campo_error($errores, 'duracion_min') ?>
                </div>
            </div>

            <div class="col-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control<?= campo_invalido($errores, 'descripcion') ?>" id="descripcion"
                          name="descripcion" rows="2" maxlength="255"
                          placeholder="Qué incluye el servicio"><?= e($datos['descripcion'] ?? '') ?></textarea>
                <?= campo_error($errores, 'descripcion') ?>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                           <?= (int) $datos['activo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activo">Activo (aparece al registrar órdenes)</label>
                </div>
            </div>
        </div>

        <?php if ($editando): ?>
            <p class="form-text mt-3 mb-0">
                <i class="bi bi-info-circle"></i> Cambiar el precio no altera las órdenes ya registradas:
                cada orden guarda el precio con que se cobró.
            </p>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Agregar al catálogo' ?></button>
            <a href="<?= url('catalogo') ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
