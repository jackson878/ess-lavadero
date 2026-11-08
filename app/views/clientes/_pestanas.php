<?php
/**
 * Fragmento: pestañas "Clientes | Vehículos" (se incluye en ambas listas).
 *
 * @var string $pestana  'clientes' o 'vehiculos'
 */
?>
<ul class="nav pestanas mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $pestana === 'clientes' ? 'active' : '' ?>" href="<?= url('cliente') ?>">
            <i class="bi bi-people"></i> Clientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $pestana === 'vehiculos' ? 'active' : '' ?>" href="<?= url('vehiculo') ?>">
            <i class="bi bi-car-front"></i> Vehículos
        </a>
    </li>
</ul>
