<?php
/**
 * Vista: cola del lavador (diseñada para el celular, RNF-03).
 * Botones grandes para tocar con el dedo; la página se actualiza sola cada minuto.
 *
 * @var array $pendientes  en espera / en lavado
 * @var array $terminados  terminadas o entregadas hoy
 * @var array $usuarioActual
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-3">
    <div class="ceja">Mi cola</div>
    <h1 class="titulo-pagina">Hola, <?= e(explode(' ', $usuarioActual['nombre'])[0]) ?></h1>
    <p class="text-secondary mb-0">
        <?= count($pendientes) ?> <?= count($pendientes) === 1 ? 'vehículo pendiente' : 'vehículos pendientes' ?>
        · <?= count($terminados) ?> terminados hoy
    </p>
</div>

<?php if (!$pendientes): ?>
    <div class="tarjeta text-center py-5 mb-3">
        <i class="bi bi-check2-circle fs-1 text-success"></i>
        <p class="fs-5 fw-semibold mb-0 mt-2">No tiene vehículos pendientes</p>
        <p class="text-secondary small mb-0">Esta página se actualiza sola.</p>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($pendientes as $o): ?>
        <div class="col-md-6 col-xl-4">
            <div class="tarjeta tarjeta-lavador estado-borde-<?= e($o['estado']) ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="placa placa-grande"><?= e(placa_formato($o['placa'])) ?></div>
                    <?= estado_badge($o['estado']) ?>
                </div>
                <div class="text-secondary mt-1">
                    <?= tipo_badge($o['tipo']) ?> <?= e(trim(($o['marca'] ?? '') . ' · ' . ($o['color'] ?? ''), ' ·')) ?>
                </div>

                <div class="fs-5 fw-semibold mt-3"><?= e($o['servicios']) ?></div>
                <div class="text-secondary small">
                    <i class="bi bi-clock"></i> Estimado <?= e(duracion_texto((int) $o['duracion_estimada'])) ?>
                    · <?= $o['estado'] === 'en_lavado' ? 'lavando hace' : 'esperando hace' ?>
                    <?= e(tiempo_desde($o['fecha_estado'] ?? $o['fecha_ingreso'])) ?>
                </div>
                <?php if ($o['observaciones']): ?>
                    <div class="aviso aviso-alerta mt-2"><i class="bi bi-chat-left-text"></i> <?= e($o['observaciones']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= url('lavador', 'avanzar') ?>" class="mt-3">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                    <?php if ($o['estado'] === 'en_espera'): ?>
                        <button type="submit" class="btn btn-ess btn-lg w-100 boton-lavador">
                            <i class="bi bi-play-fill"></i> Empezar lavado
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-exito btn-lg w-100 boton-lavador">
                            <i class="bi bi-check-lg"></i> Marcar terminado
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($terminados): ?>
    <div class="tarjeta">
        <h2 class="tarjeta-titulo">Terminados hoy</h2>
        <ul class="list-unstyled mb-0 lista-terminados">
            <?php foreach ($terminados as $o): ?>
                <li class="d-flex justify-content-between align-items-center">
                    <span>
                        <span class="placa"><?= e(placa_formato($o['placa'])) ?></span>
                        <span class="text-secondary small d-block"><?= e($o['servicios']) ?></span>
                    </span>
                    <?= estado_badge($o['estado']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
