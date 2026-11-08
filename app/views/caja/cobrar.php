<?php
/**
 * Vista: cobrar una orden terminada.
 *
 * @var array  $orden
 * @var array  $detalle
 * @var array  $datos    [metodo, referencia, entregar]
 * @var array  $errores
 * @var string $volver   'caja' u 'orden' (a dónde regresar después de cobrar)
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <a href="<?= url('caja') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a la caja</a>
    <div class="ceja mt-2">Cobrar orden #<?= (int) $orden['id'] ?></div>
    <h1 class="titulo-pagina"><?= e(placa_formato($orden['placa'])) ?> · <?= e($orden['cliente']) ?></h1>
</div>

<form method="post" action="<?= url('caja', 'cobrar', ['id' => $orden['id']]) ?>" class="row g-3" id="formCobro">
    <?= Csrf::campo() ?>
    <input type="hidden" name="volver" value="<?= $volver === 'orden' ? 'orden' : 'caja' ?>">

    <div class="col-lg-7">
        <div class="tarjeta mb-3">
            <h2 class="paso"><span>1</span> Medio de pago</h2>
            <div class="row g-2">
                <?php foreach (Caja::METODOS as $m): ?>
                    <div class="col-sm-4">
                        <input type="radio" class="btn-check" name="metodo" id="metodo-<?= $m ?>" value="<?= $m ?>"
                               <?= $datos['metodo'] === $m ? 'checked' : '' ?>>
                        <label class="opcion-servicio text-center py-3" for="metodo-<?= $m ?>">
                            <i class="bi <?= metodo_icono($m) ?> fs-3 d-block"></i>
                            <span class="fw-bold"><?= e(metodo_texto($m)) ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= isset($errores['metodo']) ? '<div class="text-danger small mt-2">' . e($errores['metodo']) . '</div>' : '' ?>

            <!-- Efectivo: calculadora de vueltas (solo ayuda, no se guarda) -->
            <div id="bloqueEfectivo" class="row g-3 mt-2">
                <div class="col-sm-6">
                    <label for="recibido" class="form-label">Recibido</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="recibido" inputmode="numeric" data-formato-pesos
                               placeholder="<?= number_format((float) $orden['total'], 0, ',', '.') ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-label">Vueltas</div>
                    <div class="fs-4 fw-bold" id="vueltas" data-total="<?= (int) $orden['total'] ?>">$ 0</div>
                </div>
            </div>

            <!-- Transferencia / tarjeta: número de comprobante -->
            <div id="bloqueReferencia" class="mt-3" hidden>
                <label for="referencia" class="form-label">Número de comprobante</label>
                <input type="text" class="form-control<?= campo_invalido($errores, 'referencia') ?>" id="referencia"
                       name="referencia" value="<?= e($datos['referencia']) ?>" maxlength="50" placeholder="Opcional">
                <?= campo_error($errores, 'referencia') ?>
            </div>
        </div>

        <div class="tarjeta">
            <div class="form-check form-switch fs-5">
                <input class="form-check-input" type="checkbox" role="switch" id="entregar" name="entregar" value="1"
                       <?= $datos['entregar'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="entregar">Entregar el vehículo ahora</label>
            </div>
            <p class="small text-secondary mb-0 mt-1">Si el cliente paga pero recoge el vehículo después, desmarque esta opción.</p>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="tarjeta resumen-orden">
            <h2 class="tarjeta-titulo">Resumen</h2>
            <ul class="list-unstyled mb-3">
                <?php foreach ($detalle as $d): ?>
                    <li class="d-flex justify-content-between"><span><?= e($d['nombre']) ?></span><span><?= pesos($d['precio']) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mb-3">
                <span class="text-secondary">Total a cobrar</span>
                <span class="kpi-valor mb-0"><?= pesos($orden['total']) ?></span>
            </div>
            <button type="submit" class="btn btn-ess btn-lg w-100">Cobrar <?= pesos($orden['total']) ?></button>
        </div>
    </div>
</form>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
