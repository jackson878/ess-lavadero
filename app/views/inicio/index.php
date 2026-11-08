<?php
/**
 * Vista: bienvenida para Cajero y Lavador.
 *
 * @var array $usuarioActual
 * @var array $opciones
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <div class="ceja"><?= e(rol_texto($usuarioActual['rol'])) ?></div>
    <h1 class="titulo-pagina">Hola, <?= e(explode(' ', $usuarioActual['nombre'])[0]) ?></h1>
    <p class="text-secondary mt-2 mb-0">Hoy es <?= e(fecha_larga()) ?>. Estas son sus opciones:</p>
</div>

<div class="row g-3">
    <?php foreach ($opciones as $opcion): ?>
        <div class="col-sm-6 col-xl-4">
            <a class="tarjeta tarjeta-enlace d-block" href="<?= url($opcion['c']) ?>">
                <div class="tarjeta-titulo mb-1"><?= e($opcion['texto']) ?></div>
                <span class="text-secondary small">Abrir <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
