<?php
/**
 * Vista: equipos con fecha del último y próximo mantenimiento (RF-09).
 *
 * @var array $equipos
 * @var array $conteo  ['vencido' => n, 'proximo' => n]
 */
require VIEWS_PATH . 'layouts/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="ceja">Inventario</div>
        <h1 class="titulo-pagina">Equipos y mantenimiento</h1>
    </div>
    <a class="btn btn-ess" href="<?= url('equipo', 'crear') ?>">+ Nuevo equipo</a>
</div>

<?php if ($conteo['vencido'] || $conteo['proximo']): ?>
    <div class="aviso aviso-alerta mb-3">
        <i class="bi bi-tools"></i>
        <?php if ($conteo['vencido']): ?>
            <?= $conteo['vencido'] ?> <?= $conteo['vencido'] === 1 ? 'equipo tiene' : 'equipos tienen' ?> el mantenimiento vencido.
        <?php endif; ?>
        <?php if ($conteo['proximo']): ?>
            <?= $conteo['proximo'] ?> <?= $conteo['proximo'] === 1 ? 'equipo necesita' : 'equipos necesitan' ?> mantenimiento en los próximos <?= Equipo::DIAS_AVISO ?> días.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($equipos as $eq): ?>
        <?php $alerta = Equipo::alerta($eq['dias_para_mant']); ?>
        <div class="col-md-6 col-xl-4">
            <a class="tarjeta tarjeta-enlace d-block equipo-<?= e($alerta) ?>" href="<?= url('equipo', 'ver', ['id' => $eq['id']]) ?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-bold fs-5"><?= e($eq['nombre']) ?></div>
                        <div class="small text-secondary">
                            <?= e(equipo_tipo_texto($eq['tipo'])) ?><?= $eq['marca'] ? ' · ' . e($eq['marca']) : '' ?>
                            <?= $eq['serial'] ? ' · ' . e($eq['serial']) : '' ?>
                        </div>
                    </div>
                    <span class="estado-equipo estado-equipo-<?= e($eq['estado']) ?>"><?= e(equipo_estado_texto($eq['estado'])) ?></span>
                </div>

                <dl class="row mb-0 mt-3 small datos">
                    <dt class="col-6">Último mantenimiento</dt>
                    <dd class="col-6 text-end"><?= $eq['fecha_ultimo_mant'] ? e(date('d/m/Y', strtotime($eq['fecha_ultimo_mant']))) : '—' ?></dd>
                    <dt class="col-6">Próximo</dt>
                    <dd class="col-6 text-end"><?= $eq['fecha_proximo_mant'] ? e(date('d/m/Y', strtotime($eq['fecha_proximo_mant']))) : '—' ?></dd>
                    <dt class="col-6">Mantenimientos</dt>
                    <dd class="col-6 text-end"><?= (int) $eq['total_mant'] ?> · <?= pesos($eq['costo_mant']) ?></dd>
                </dl>
                <div class="mt-2"><?= mantenimiento_badge($eq['dias_para_mant']) ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require VIEWS_PATH . 'layouts/footer.php'; ?>
