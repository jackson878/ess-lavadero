<?php
/**
 * Vista: cierre de caja — total esperado vs. contado y diferencia (RF-05).
 *
 * @var array $caja
 * @var array $totales
 * @var float $esperado    base + ventas en efectivo
 * @var int   $pendientes  órdenes terminadas sin cobrar
 * @var array $datos
 * @var array $errores
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="mb-4">
    <a href="<?= url('caja') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a la caja</a>
    <div class="ceja mt-2">Cierre de caja</div>
    <h1 class="titulo-pagina"><?= e(fecha_larga($caja['fecha'])) ?></h1>
</div>

<?php if ($pendientes): ?>
    <div class="aviso aviso-alerta mb-3">
        Atención: hay <?= $pendientes ?> <?= $pendientes === 1 ? 'orden terminada' : 'órdenes terminadas' ?> sin cobrar.
        Si cierra ahora, deberá cobrarlas en la caja de mañana.
    </div>
<?php endif; ?>

<form method="post" action="<?= url('caja', 'cerrar', ['id' => $caja['id']]) ?>" class="row g-3"
      data-confirmar="¿Cerrar la caja? Después de cerrarla no se pueden registrar más cobros en ella.">
    <?= Csrf::campo() ?>

    <div class="col-lg-6">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Ventas del día</h2>
            <table class="table tabla-ess">
                <tbody>
                <?php foreach (Caja::METODOS as $m): ?>
                    <tr>
                        <td><i class="bi <?= metodo_icono($m) ?>"></i> <?= e(metodo_texto($m)) ?></td>
                        <td class="text-end"><?= pesos($totales[$m]) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="fw-bold">Total ventas (<?= (int) $totales['cantidad'] ?> <?= (int) $totales['cantidad'] === 1 ? 'cobro' : 'cobros' ?>)</td>
                    <td class="text-end fw-bold"><?= pesos($totales['total']) ?></td>
                </tr>
                </tbody>
            </table>

            <h2 class="tarjeta-titulo mt-4">Efectivo esperado</h2>
            <table class="table tabla-ess">
                <tbody>
                <tr><td>Base inicial</td><td class="text-end"><?= pesos($caja['base_inicial']) ?></td></tr>
                <tr><td>+ Ventas en efectivo</td><td class="text-end"><?= pesos($totales['efectivo']) ?></td></tr>
                <tr>
                    <td class="fw-bold">= Debe haber en caja</td>
                    <td class="text-end fw-bold fs-5"><?= pesos($esperado) ?></td>
                </tr>
                </tbody>
            </table>
            <p class="small text-secondary mb-0">Nequi/transferencias y tarjeta no entran a la caja física.</p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="tarjeta resumen-orden">
            <h2 class="tarjeta-titulo">Conteo</h2>
            <label for="efectivo_contado" class="form-label">Efectivo contado en caja *</label>
            <div class="input-group input-group-lg has-validation">
                <span class="input-group-text">$</span>
                <input type="text" class="form-control<?= campo_invalido($errores, 'efectivo_contado') ?>" id="efectivo_contado"
                       name="efectivo_contado" value="<?= e($datos['efectivo_contado']) ?>" inputmode="numeric"
                       data-formato-pesos data-esperado="<?= (int) $esperado ?>" autofocus required>
                <?= campo_error($errores, 'efectivo_contado') ?>
            </div>

            <div class="diferencia mt-3" id="diferencia">
                <span class="text-secondary">Diferencia</span>
                <span class="valor">—</span>
            </div>

            <label for="observaciones" class="form-label mt-3">Observaciones</label>
            <textarea class="form-control<?= campo_invalido($errores, 'observaciones') ?>" id="observaciones"
                      name="observaciones" rows="2" maxlength="255"
                      placeholder="Obligatorio si hay diferencia"><?= e($datos['observaciones']) ?></textarea>
            <?= campo_error($errores, 'observaciones') ?>

            <button type="submit" class="btn btn-ess btn-lg w-100 mt-3"><i class="bi bi-lock"></i> Cerrar caja</button>
        </div>
    </div>
</form>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
