<?php
/**
 * Fragmento: pestañas "Hoy | Historial" de la caja.
 *
 * @var string $pestana
 */
?>
<ul class="nav pestanas mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $pestana === 'hoy' ? 'active' : '' ?>" href="<?= url('caja') ?>">
            <i class="bi bi-cash-stack"></i> Caja de hoy
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $pestana === 'historial' ? 'active' : '' ?>" href="<?= url('caja', 'historial') ?>">
            <i class="bi bi-clock-history"></i> Historial
        </a>
    </li>
</ul>
