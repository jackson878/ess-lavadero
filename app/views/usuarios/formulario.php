<?php
/**
 * Vista: formulario para crear o editar un usuario (solo Dueño).
 *
 * @var array    $datos    valores del formulario
 * @var array    $errores  [campo => mensaje]
 * @var int|null $id       null = crear, número = editar
 * @var array    $usuarioActual
 */
require VIEWS_PATH . 'layouts/header.php';

$editando = $id !== null;
$accion   = $editando ? url('usuario', 'editar', ['id' => $id]) : url('usuario', 'crear');
$esYo     = $editando && $id === $usuarioActual['id'];

/** Clase de Bootstrap para marcar en rojo un campo con error */
$invalido = fn(string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
/** Mensaje de error debajo del campo */
$mensaje  = fn(string $campo): string => isset($errores[$campo])
    ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>' : '';
?>

<div class="mb-4">
    <a href="<?= url('usuario') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a usuarios</a>
    <h1 class="titulo-pagina mt-2"><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
</div>

<?php if ($errores): ?>
    <div class="alert alerta-error">Revise los campos marcados en rojo.</div>
<?php endif; ?>

<div class="tarjeta formulario-ancho">
    <form method="post" action="<?= $accion ?>" novalidate>
        <?= Csrf::campo() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre completo *</label>
                <input type="text" class="form-control<?= $invalido('nombre') ?>" id="nombre" name="nombre"
                       value="<?= e($datos['nombre']) ?>" maxlength="100" required>
                <?= $mensaje('nombre') ?>
            </div>

            <div class="col-md-6">
                <label for="usuario" class="form-label">Usuario para ingresar *</label>
                <input type="text" class="form-control<?= $invalido('usuario') ?>" id="usuario" name="usuario"
                       value="<?= e($datos['usuario']) ?>" maxlength="50" autocapitalize="none" required>
                <?= $mensaje('usuario') ?: '<div class="form-text">Minúsculas, números, punto o guion bajo.</div>' ?>
            </div>

            <div class="col-md-6">
                <label for="rol" class="form-label">Rol *</label>
                <select class="form-select<?= $invalido('rol') ?>" id="rol" name="rol" <?= $esYo ? 'disabled' : '' ?>>
                    <?php foreach (Usuario::ROLES as $rol): ?>
                        <option value="<?= e($rol) ?>" <?= $datos['rol'] === $rol ? 'selected' : '' ?>>
                            <?= e(rol_texto($rol)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($esYo): ?>
                    <!-- Un select deshabilitado no se envía: se manda el valor en un campo oculto -->
                    <input type="hidden" name="rol" value="<?= e($datos['rol']) ?>">
                    <div class="form-text">No puede cambiar su propio rol.</div>
                <?php endif; ?>
                <?= $mensaje('rol') ?>
            </div>

            <div class="col-md-6">
                <label for="telefono" class="form-label">Celular</label>
                <input type="tel" class="form-control<?= $invalido('telefono') ?>" id="telefono" name="telefono"
                       value="<?= e($datos['telefono'] ?? '') ?>" maxlength="10" inputmode="numeric"
                       placeholder="3001234567">
                <?= $mensaje('telefono') ?>
            </div>

            <div class="col-12">
                <hr class="my-2">
                <p class="fw-semibold mb-0">Contraseña</p>
                <?php if ($editando): ?>
                    <p class="form-text mt-0">Déjela en blanco si no desea cambiarla.</p>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Contraseña <?= $editando ? 'nueva' : '*' ?></label>
                <input type="password" class="form-control<?= $invalido('password') ?>" id="password" name="password"
                       autocomplete="new-password" minlength="8">
                <?= $mensaje('password') ?: '<div class="form-text">Mínimo 8 caracteres.</div>' ?>
            </div>

            <div class="col-md-6">
                <label for="password2" class="form-label">Repetir contraseña <?= $editando ? '' : '*' ?></label>
                <input type="password" class="form-control<?= $invalido('password2') ?>" id="password2" name="password2"
                       autocomplete="new-password">
                <?= $mensaje('password2') ?>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input<?= $invalido('activo') ?>" type="checkbox" role="switch"
                           id="activo" name="activo" value="1" <?= (int) $datos['activo'] ? 'checked' : '' ?>
                           <?= $esYo ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="activo">Usuario activo (puede iniciar sesión)</label>
                    <?php if ($esYo): ?>
                        <input type="hidden" name="activo" value="1">
                    <?php endif; ?>
                    <?= $mensaje('activo') ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-ess"><?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
            <a href="<?= url('usuario') ?>" class="btn btn-ess-borde">Cancelar</a>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
