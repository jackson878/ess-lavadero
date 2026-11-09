<?php
/**
 * Vista: formulario de equipo (crear / editar).
 *
 * @var array    $datos
 * @var array    $errores
 * @var int|null $id
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('equipo', 'editar', ['id' => $id]) : url('equipo', 'crear');
$volver   = $editando ? url('equipo', 'ver', ['id' => $id]) : url('equipo');
?>

<div class="mb-4">
    <a href="<?= $volver ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar equipo' : 'Nuevo equipo' ?></h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
<?php endif; ?>

<div class="tarjeta formulario-ancho">
    <form method="post" action="<?= $accion ?>" novalidate>
        <?= Csrf::campo() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'nombre') ?>" id="nombre" name="nombre"
                       value="<?= e($datos['nombre']) ?>" maxlength="100" placeholder="Hidrolavadora principal" required>
                <?= campo_error($errores, 'nombre') ?>
            </div>
            <div class="col-md-4">
                <label for="tipo" class="form-label">Tipo *</label>
                <select class="form-select<?= campo_invalido($errores, 'tipo') ?>" id="tipo" name="tipo">
                    <?php foreach (Equipo::TIPOS as $t): ?>
                        <option value="<?= e($t) ?>" <?= $datos['tipo'] === $t ? 'selected' : '' ?>><?= e(equipo_tipo_texto($t)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= campo_error($errores, 'tipo') ?>
            </div>
            <div class="col-md-4">
                <label for="marca" class="form-label">Marca</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'marca') ?>" id="marca" name="marca"
                       value="<?= e($datos['marca'] ?? '') ?>" maxlength="50">
                <?= campo_error($errores, 'marca') ?>
            </div>
            <div class="col-md-4">
                <label for="serial" class="form-label">Serial</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'serial') ?>" id="serial" name="serial"
                       value="<?= e($datos['serial'] ?? '') ?>" maxlength="50">
                <?= campo_error($errores, 'serial') ?>
            </div>
            <div class="col-md-4">
                <label for="estado" class="form-label">Estado *</label>
                <select class="form-select<?= campo_invalido($errores, 'estado') ?>" id="estado" name="estado">
                    <?php foreach (Equipo::ESTADOS as $s): ?>
                        <option value="<?= e($s) ?>" <?= $datos['estado'] === $s ? 'selected' : '' ?>><?= e(equipo_estado_texto($s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= campo_error($errores, 'estado') ?>
            </div>
            <div class="col-md-6">
                <label for="fecha_compra" class="form-label">Fecha de compra</label>
                <input type="date" class="form-control<?= campo_invalido($errores, 'fecha_compra') ?>" id="fecha_compra"
                       name="fecha_compra" value="<?= e($datos['fecha_compra'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                <?= campo_error($errores, 'fecha_compra') ?>
            </div>
            <div class="col-md-6">
                <label for="fecha_proximo_mant" class="form-label">Próximo mantenimiento</label>
                <input type="date" class="form-control<?= campo_invalido($errores, 'fecha_proximo_mant') ?>" id="fecha_proximo_mant"
                       name="fecha_proximo_mant" value="<?= e($datos['fecha_proximo_mant'] ?? '') ?>">
                <?= campo_error($errores, 'fecha_proximo_mant') ?: '<div class="form-text">También se actualiza al registrar un mantenimiento.</div>' ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Registrar equipo' ?></button>
            <a href="<?= $volver ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
