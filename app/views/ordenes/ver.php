<?php
/**
 * Vista: detalle de una orden con su historial de estados.
 *
 * @var array $orden
 * @var array $detalle    servicios con precio cobrado
 * @var array $historial  [estado, fecha_hora, usuario]
 * @var array $lavadores
 */
require VIEWS_PATH . 'layouts/header.php';

$siguiente = texto_siguiente_estado($orden['estado']);
$whatsapp  = $orden['estado'] === 'terminado' ? whatsapp_url($orden['telefono'], $orden['placa']) : null;
$editable  = in_array($orden['estado'], Orden::CANCELABLES, true);
?>

<div class="mb-4">
    <a href="<?= url('orden') ?>" class="enlace-volver"><i class="bi bi-arrow-left"></i> Volver a servicios</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="ceja">Orden #<?= (int) $orden['id'] ?></div>
            <h1 class="titulo-pagina">
                <?= e(placa_formato($orden['placa'])) ?> <?= estado_badge($orden['estado']) ?>
            </h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($whatsapp): ?>
                <a class="btn btn-whatsapp btn-lg" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i> Avisar por WhatsApp
                </a>
            <?php endif; ?>
            <?php if ($siguiente): ?>
                <form method="post" action="<?= url('orden', 'estado') ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $orden['id'] ?>">
                    <input type="hidden" name="volver" value="ver">
                    <button type="submit" class="btn btn-ess btn-lg"><?= e($siguiente) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <!-- Servicios -->
        <div class="tarjeta mb-3">
            <h2 class="tarjeta-titulo">Servicios</h2>
            <table class="table tabla-ess">
                <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td><?= e($d['nombre']) ?> <span class="small text-secondary">· <?= e(duracion_texto((int) $d['duracion_min'])) ?></span></td>
                        <td class="text-end"><?= pesos($d['precio']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="fw-bold">Total</td>
                    <td class="text-end fw-bold fs-5"><?= pesos($orden['total']) ?></td>
                </tr>
                </tbody>
            </table>
            <?php if ($orden['observaciones']): ?>
                <p class="mb-0 mt-3"><i class="bi bi-chat-left-text"></i> <?= e($orden['observaciones']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Vehículo y cliente -->
        <div class="tarjeta mb-3">
            <h2 class="tarjeta-titulo">Vehículo y cliente</h2>
            <dl class="row mb-0 datos">
                <dt class="col-sm-4">Vehículo</dt>
                <dd class="col-sm-8"><?= tipo_badge($orden['tipo']) ?> <?= e(trim(($orden['marca'] ?? '') . ' · ' . ($orden['color'] ?? ''), ' ·')) ?></dd>
                <dt class="col-sm-4">Cliente</dt>
                <dd class="col-sm-8">
                    <a href="<?= url('cliente', 'ver', ['id' => $orden['cliente_id']]) ?>"><?= e($orden['cliente']) ?></a>
                    · <?= e(telefono_formato($orden['telefono'])) ?>
                </dd>
                <dt class="col-sm-4">Registró</dt>
                <dd class="col-sm-8"><?= e($orden['creado_por']) ?></dd>
                <dt class="col-sm-4">Lavador</dt>
                <dd class="col-sm-8">
                    <?php if ($editable): ?>
                        <!-- Reasignar lavador mientras la orden no esté terminada -->
                        <form method="post" action="<?= url('orden', 'lavador') ?>" class="d-flex gap-2">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $orden['id'] ?>">
                            <input type="hidden" name="volver" value="ver">
                            <select name="lavador_id" class="form-select form-select-sm" style="max-width: 240px" aria-label="Lavador">
                                <?php foreach ($lavadores as $l): ?>
                                    <option value="<?= (int) $l['id'] ?>" <?= (int) $l['id'] === (int) $orden['lavador_id'] ? 'selected' : '' ?>>
                                        <?= e($l['nombre']) ?> (<?= (int) $l['pendientes'] ?> en cola)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-ess-borde">Cambiar</button>
                        </form>
                    <?php else: ?>
                        <?= e($orden['lavador']) ?>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>
    </div>

    <!-- Historial de estados (hora de cada cambio) -->
    <div class="col-lg-5">
        <div class="tarjeta">
            <h2 class="tarjeta-titulo">Historial</h2>
            <ol class="linea-tiempo">
                <?php foreach ($historial as $i => $h): ?>
                    <?php
                    // Minutos que duró el paso anterior
                    $anterior = $historial[$i - 1]['fecha_hora'] ?? null;
                    $minutos  = $anterior ? (int) round((strtotime($h['fecha_hora']) - strtotime($anterior)) / 60) : null;
                    ?>
                    <li class="estado-borde-<?= e($h['estado']) ?>">
                        <div class="d-flex justify-content-between">
                            <strong><?= e(estado_texto($h['estado'])) ?></strong>
                            <span class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($h['fecha_hora']))) ?></span>
                        </div>
                        <div class="small text-secondary">
                            <?= e($h['usuario']) ?><?= $minutos !== null ? ' · ' . e(duracion_texto($minutos)) . ' después' : '' ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
            <?php if ($orden['estado'] === 'terminado' && !$whatsapp): ?>
                <p class="small text-secondary mb-0">El cliente no tiene celular registrado para avisarle por WhatsApp.</p>
            <?php endif; ?>
        </div>

        <?php if ($editable): ?>
            <form method="post" action="<?= url('orden', 'cancelar') ?>" class="mt-3"
                  data-confirmar="¿Cancelar la orden #<?= (int) $orden['id'] ?> de <?= e(placa_formato($orden['placa'])) ?>?">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= (int) $orden['id'] ?>">
                <input type="hidden" name="volver" value="ver">
                <button type="submit" class="btn btn-ess-borde text-danger"><i class="bi bi-x-circle"></i> Cancelar orden</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
