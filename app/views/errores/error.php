<?php
/**
 * Vista: página de error (403 acceso denegado, 404 no encontrada, 500 error interno).
 *
 * @var int    $codigo
 * @var string $titulo
 * @var string $mensaje
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="tarjeta text-center py-5" style="max-width: 560px; margin: 3rem auto;">
    <div class="error-codigo"><?= (int) $codigo ?></div>
    <h1 class="h3 fw-bold mb-3"><?= e($titulo) ?></h1>
    <p class="text-secondary mb-4"><?= e($mensaje) ?></p>
    <a href="<?= url() ?>" class="btn btn-ess">Ir al inicio</a>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
