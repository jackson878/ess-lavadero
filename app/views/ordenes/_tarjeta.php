<?php
/**
 * Fragmento: tarjeta de una orden activa en el tablero de servicios.
 *
 * @var array $o  fila de Orden::activas()
 */
$siguiente = texto_siguiente_estado($o['estado']);
$whatsapp  = $o['estado'] === 'terminado' ? whatsapp_url($o['telefono'], $o['placa']) : null;
?>
<div class="orden-tarjeta estado-borde-<?= e($o['estado']) ?>">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
            <a class="placa fs-5 text-decoration-none text-reset" href="<?= url('orden', 'ver', ['id' => $o['id']]) ?>">
                <?= e(placa_formato($o['placa'])) ?>
            </a>
            <div class="small text-secondary"><?= e(ucfirst($o['tipo'])) ?> · <?= e($o['marca'] ?? '') ?></div>
        </div>
        <span class="small text-secondary text-nowrap" title="Tiempo en este estado">
            <i class="bi bi-clock"></i> <?= e(tiempo_desde($o['fecha_estado'] ?? $o['fecha_ingreso'])) ?>
        </span>
    </div>

    <div class="mt-2"><?= e($o['servicios']) ?></div>
    <div class="small text-secondary">
        <?= e($o['cliente']) ?> · <i class="bi bi-person-badge"></i> <?= e(explode(' ', $o['lavador'])[0]) ?>
    </div>
    <div class="fw-bold mt-1"><?= pesos($o['total']) ?></div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <?php if ($o['estado'] === 'terminado' && !(int) $o['pagado']): ?>
            <!-- Terminada sin pagar: primero se cobra (al cobrar se puede entregar en el mismo paso) -->
            <a class="btn btn-sm btn-acento" href="<?= url('caja', 'cobrar', ['id' => $o['id'], 'volver' => 'orden']) ?>">
                <i class="bi bi-cash-coin"></i> Cobrar
            </a>
        <?php elseif ($siguiente): ?>
            <form method="post" action="<?= url('orden', 'estado') ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <button type="submit" class="btn btn-sm btn-ess"><?= e($siguiente) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($whatsapp): ?>
            <a class="btn btn-sm btn-whatsapp" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">
                <i class="bi bi-whatsapp"></i> Avisar
            </a>
        <?php endif; ?>
        <a class="btn btn-sm btn-ess-borde" href="<?= url('orden', 'ver', ['id' => $o['id']]) ?>">Ver</a>
    </div>
</div>
