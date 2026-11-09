<?php
/**
 * Vista: formulario de insumo (crear / editar).
 * Al crear se pide el stock inicial; al editar el stock NO se cambia
 * aquí (se usa una entrada o salida para que quede en el historial).
 *
 * @var array    $datos
 * @var array    $errores
 * @var int|null $id
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('insumo', 'editar', ['id' => $id]) : url('insumo', 'crear');
$volver   = $editando ? url('insumo', 'ver', ['id' => $id]) : url('insumo');
$num      = fn($v) => $v === '' || $v === null ? '' : cantidad_formato($v);
?>

<div class="mb-4">
    <a href="<?= $volver ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar insumo' : 'Nuevo insumo' ?></h1>
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
                       value="<?= e($datos['nombre']) ?>" maxlength="100" placeholder="Shampoo para carros" required>
                <?= campo_error($errores, 'nombre') ?>
            </div>
            <div class="col-md-4">
                <label for="unidad" class="form-label">Unidad *</label>
                <select class="form-select<?= campo_invalido($errores, 'unidad') ?>" id="unidad" name="unidad">
                    <?php foreach (Insumo::UNIDADES as $u): ?>
                        <option value="<?= e($u) ?>" <?= $datos['unidad'] === $u ? 'selected' : '' ?>><?= e(ucfirst($u)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= campo_error($errores, 'unidad') ?>
            </div>

            <?php if (!$editando): ?>
                <div class="col-md-4">
                    <label for="stock_inicial" class="form-label">Stock inicial</label>
                    <input type="text" class="form-control<?= campo_invalido($errores, 'stock_inicial') ?>" id="stock_inicial"
                           name="stock_inicial" value="<?= e($num($datos['stock_inicial'])) ?>" inputmode="decimal" placeholder="0">
                    <?= campo_error($errores, 'stock_inicial') ?: '<div class="form-text">Se registra como una entrada.</div>' ?>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <div class="form-label">Stock actual</div>
                    <div class="fs-5 fw-bold"><?= e(cantidad_formato($datos['stock_actual'])) ?> <?= e($datos['unidad']) ?></div>
                    <div class="form-text">Cámbielo con una entrada o salida.</div>
                </div>
            <?php endif; ?>

            <div class="col-md-4">
                <label for="stock_minimo" class="form-label">Stock mínimo *</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'stock_minimo') ?>" id="stock_minimo"
                       name="stock_minimo" value="<?= e($num($datos['stock_minimo'])) ?>" inputmode="decimal" placeholder="5" required>
                <?= campo_error($errores, 'stock_minimo') ?: '<div class="form-text">Por debajo de este valor se muestra la alerta.</div>' ?>
            </div>

            <div class="col-md-4">
                <label for="costo_unitario" class="form-label">Costo unitario</label>
                <div class="input-group has-validation">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control<?= campo_invalido($errores, 'costo_unitario') ?>" id="costo_unitario"
                           name="costo_unitario" value="<?= (int) $datos['costo_unitario'] ? e(number_format((float) $datos['costo_unitario'], 0, ',', '.')) : '' ?>"
                           inputmode="numeric" data-formato-pesos placeholder="18.000">
                    <?= campo_error($errores, 'costo_unitario') ?>
                </div>
            </div>

            <?php if ($editando): ?>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                               <?= (int) $datos['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Activo (si ya no se usa, desactívelo para quitar su alerta)</label>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Agregar insumo' ?></button>
            <a href="<?= $volver ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
