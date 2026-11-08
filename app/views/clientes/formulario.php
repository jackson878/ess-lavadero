<?php
/**
 * Vista: formulario para crear o editar un cliente.
 *
 * @var array    $datos
 * @var array    $errores
 * @var int|null $id
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('cliente', 'editar', ['id' => $id]) : url('cliente', 'crear');
$volver   = $editando ? url('cliente', 'ver', ['id' => $id]) : url('cliente');
?>

<div class="mb-4">
    <a href="<?= $volver ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar cliente' : 'Nuevo cliente' ?></h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
<?php endif; ?>

<div class="tarjeta formulario-ancho">
    <form method="post" action="<?= $accion ?>" novalidate>
        <?= Csrf::campo() ?>

        <div class="row g-3">
            <div class="col-md-7">
                <label for="nombre" class="form-label">Nombre completo *</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'nombre') ?>" id="nombre"
                       name="nombre" value="<?= e($datos['nombre']) ?>" maxlength="100" required autofocus>
                <?= campo_error($errores, 'nombre') ?>
            </div>

            <div class="col-md-5">
                <label for="telefono" class="form-label">Celular *</label>
                <input type="tel" class="form-control<?= campo_invalido($errores, 'telefono') ?>" id="telefono"
                       name="telefono" value="<?= e($datos['telefono']) ?>" maxlength="13" inputmode="numeric"
                       placeholder="3001234567" required>
                <?= campo_error($errores, 'telefono') ?: '<div class="form-text">Se usa para avisarle por WhatsApp.</div>' ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Registrar cliente' ?></button>
            <a href="<?= $volver ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
